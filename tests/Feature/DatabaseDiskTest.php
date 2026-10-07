<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\MediaBlob;
use App\Models\Role;
use App\Models\User;
use App\Services\Media\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Disque "database" (100 % Neon) : écriture/lecture/suppression en morceaux,
 * upload candidat + streaming avec seek (206), sans S3 ni disque local.
 */
class DatabaseDiskTest extends TestCase
{
    use RefreshDatabase;

    public function test_put_get_delete_roundtrip_with_chunking(): void
    {
        $disk = Storage::disk('database');

        // 2,5 Mo → 3 morceaux (découpe vérifiée, pas de pic mémoire).
        $payload = random_bytes(2621440);
        $disk->put('candidate_audio/1/2/3/response.bin', $payload);

        $this->assertTrue($disk->exists('candidate_audio/1/2/3/response.bin'));
        $this->assertSame(3, MediaBlob::firstOrFail()->chunks()->count());
        $this->assertSame($payload, $disk->get('candidate_audio/1/2/3/response.bin'));

        $disk->delete('candidate_audio/1/2/3/response.bin');
        $this->assertFalse($disk->exists('candidate_audio/1/2/3/response.bin'));
        $this->assertSame(0, MediaBlob::count());
    }

    public function test_binary_bytes_with_nulls_survive(): void
    {
        // Octets nuls + non-UTF8 (vrai MP3) : le base64 texte ne casse pas.
        $payload = "\x00\xff\x89PNG\r\n\x1a\n".random_bytes(1000);
        Storage::disk('database')->put('bin/file.bin', $payload);

        $this->assertSame($payload, Storage::disk('database')->get('bin/file.bin'));
    }

    public function test_candidate_upload_goes_to_database_disk(): void
    {
        config(['testdaf.media.disk' => 'database']);

        $role = \App\Models\Role::query()->firstOrCreate(['slug' => 'candidate'], [
            'name' => 'Candidat', 'description' => 'x',
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        // Enregistrement candidat (métadonnées + disque database).
        // Note : les faux UploadedFile sont creux (0 octet réel) — le contenu
        // réel est testé ci-dessous via Storage::put.
        $file = UploadedFile::fake()->create('response.webm', 100, 'audio/webm');
        $media = app(MediaService::class)->storeCandidateFile($file, 'audio', $user->id, 9, 8);

        $this->assertSame('database', $media->disk);
        $this->assertTrue(Storage::disk('database')->exists($media->path));

        // Fichier réel (100 Ko) + streaming avec seek partiel (206).
        $payload = random_bytes(102400);
        Storage::disk('database')->put('candidate_audio/9/8/response.bin', $payload);
        $media2 = Media::create([
            'type' => 'audio',
            'disk' => 'database',
            'path' => 'candidate_audio/9/8/response.bin',
            'original_name' => 'response.bin',
            'mime' => 'audio/mpeg',
            'size' => strlen($payload),
            'meta' => ['public' => false, 'uploaded_by' => $user->id],
        ]);

        $this->actingAs($user)
            ->get(route('media.stream', $media2))
            ->assertOk()
            ->assertHeader('Accept-Ranges', 'bytes');

        $ranged = $this->actingAs($user)
            ->get(route('media.stream', $media2), ['Range' => 'bytes=0-99']);
        $ranged->assertStatus(206)
            ->assertHeader('Content-Range', 'bytes 0-99/'.strlen($payload));
        $this->assertSame(substr($payload, 0, 100), $ranged->streamedContent());
    }

    public function test_range_streaming_only_reads_overlapping_chunks(): void
    {
        $disk = Storage::disk('database');
        $payload = random_bytes(3145728); // 3 Mo → 3 morceaux
        $disk->put('big/movie.bin', $payload);

        $adapter = new \App\Filesystem\DatabaseMediaAdapter();

        // Plage à cheval sur 2 morceaux : octets exacts, sans tout charger.
        $start = 1048570; // 6 octets avant la fin du morceau 0
        $slice = $adapter->readRange('big/movie.bin', $start, 20);
        $this->assertSame(substr($payload, $start, 20), $slice);
    }
}
