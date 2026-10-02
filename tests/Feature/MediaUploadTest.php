<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\ModellTest;
use App\Models\Role;
use App\Models\SpeakingSubmission;
use App\Models\User;
use App\Services\Exam\ExamService;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\ModellTestSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleSeeder::class,
            AdminUserSeeder::class,
            SettingSeeder::class,
            ModellTestSeeder::class,
        ]);
        Storage::fake('local');
    }

    public function test_audio_upload_is_stored_privately_and_streamed_controlled(): void
    {
        $user = User::where('email', 'candidat@testdaf.local')->first();
        $attempt = app(ExamService::class)
            ->startAttempt($user, ModellTest::where('number', 1)->first());

        // Avance jusqu'à la tâche Sprechen (positions 4 et 5).
        $engine = app(ExamService::class);
        foreach ($attempt->attemptExercises()->orderBy('position')->take(4)->get() as $ae) {
            $engine->startExercise($attempt, $ae->exercise_id);
            $engine->completeExercise($attempt, $ae->exercise_id);
        }

        $attempt->refresh();
        $speaking = $attempt->currentExercise();
        $this->assertSame('sprechen', $speaking->exercise->skill->value);

        $engine->startExercise($attempt, $speaking->exercise_id);

        $file = UploadedFile::fake()->create('response.webm', 100, 'audio/webm');

        $response = $this->actingAs($user)->post(route('api.speaking.store', $attempt), [
            'exercise_id' => $speaking->exercise_id,
            'audio' => $file,
            'duration_seconds' => 45,
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $path = SpeakingSubmission::latest()->first()->path;
        $this->assertStringStartsWith('candidate_audio/', $path);
        Storage::disk('local')->assertExists($path);

        // Un autre candidat ne peut pas lire le fichier.
        $other = User::factory()->create(['role_id' => Role::where('slug', 'candidate')->first()->id]);
        $this->actingAs($other)
            ->get(route('media.stream', Media::latest()->first()))
            ->assertForbidden();
    }
}
