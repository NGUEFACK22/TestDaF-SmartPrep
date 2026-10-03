<?php

namespace Tests\Feature;

use App\Enums\Skill;
use App\Models\Attempt;
use App\Models\Media;
use App\Models\ModellTest;
use App\Models\User;
use App\Services\Exam\ExamService;
use Database\Seeders\ModellTestMediaSeeder;
use Database\Seeders\ModellTestSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MediaContentTest — médias de contenu d'examen (Hören audio, Schreiben chart).
 *
 * Vérifie la chaîne complète : seed → stockage privé → page d'examen →
 * streaming authentifié. La clé de couverture du P1 « médias d'examen ».
 */
class MediaContentTest extends TestCase
{
    use RefreshDatabase;

    private User $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            SettingSeeder::class,
            ModellTestSeeder::class,
            ModellTestMediaSeeder::class,
        ]);

        $this->candidate = User::factory()->create();
    }

    /**
     * Fait progresser l'attempt jusqu'à (sans inclure) la tâche de compétence
     * donnée — les tâches précédentes sont démarrées puis complétées.
     */
    private function advanceToSkill(Attempt $attempt, Skill $skill): void
    {
        $engine = app(ExamService::class);

        foreach ($attempt->attemptExercises()->orderBy('position')->get() as $ae) {
            if ($ae->exercise->skill === $skill) {
                break;
            }

            $engine->startExercise($attempt, $ae->exercise_id);
            $engine->completeExercise($attempt, $ae->exercise_id);
        }

        $attempt->refresh();
    }

    public function test_hoeren_task_renders_audio_player(): void
    {
        $engine = app(ExamService::class);
        $attempt = $engine->startAttempt($this->candidate, ModellTest::where('number', 1)->first());

        $this->advanceToSkill($attempt, Skill::Hoeren);

        $media = $attempt->currentExercise()
            ->exercise
            ->media()
            ->where('type', 'audio')
            ->first();

        $this->assertNotNull($media, 'L\'exercice Hören du Modelltest 1 doit porter un média audio.');

        $this->actingAs($this->candidate)
            ->get(route('exam.show', $attempt))
            ->assertOk()
            ->assertSee('<audio', false)
            ->assertSee(route('media.stream', $media), false);
    }

    public function test_schreiben_task_renders_chart(): void
    {
        $engine = app(ExamService::class);
        $attempt = $engine->startAttempt($this->candidate, ModellTest::where('number', 1)->first());

        $this->advanceToSkill($attempt, Skill::Schreiben);

        $chart = $attempt->currentExercise()->exercise->content['chart'] ?? null;
        $this->assertIsArray($chart, 'L\'exercice Schreiben du Modelltest 1 doit porter des données chart.');

        $this->actingAs($this->candidate)
            ->get(route('exam.show', $attempt))
            ->assertOk()
            ->assertSee('data-chart="bar"', false)
            ->assertSee((string) $chart['title'], false);
    }

    public function test_content_media_streams_for_authenticated_candidate(): void
    {
        $media = $this->seededAudioMedia();

        $response = $this->actingAs($this->candidate)
            ->get(route('media.stream', $media));

        $response->assertOk();

        $contentType = strtolower((string) $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('audio/', $contentType, 'Le flux doit être servi avec un Content-Type audio.');
    }

    public function test_content_media_forbidden_for_guest(): void
    {
        $media = $this->seededAudioMedia();

        $this->get(route('media.stream', $media))
            ->assertRedirect();
    }

    public function test_candidate_cannot_stream_another_candidates_recording(): void
    {
        $other = User::factory()->create();

        $recording = Media::create([
            'name' => 'recording.webm',
            'type' => 'audio',
            'mime' => 'audio/webm',
            'size' => 42,
            'path' => 'submissions/recording.webm',
            'disk' => 'local',
            'meta' => ['uploaded_by' => $other->id],
        ]);

        $this->actingAs($this->candidate)
            ->get(route('media.stream', $recording))
            ->assertForbidden();
    }

    private function seededAudioMedia(): Media
    {
        return Media::where('type', 'audio')->where('meta->public', true)->firstOrFail();
    }
}