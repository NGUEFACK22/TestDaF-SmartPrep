<?php

namespace Tests\Feature;

use App\Models\AiChallenge;
use App\Models\ChallengeUnlock;
use App\Models\ModellTest;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\AI\GeminiService;
use App\Services\Challenge\ChallengeService;
use App\Services\Exam\AnswerService;
use App\Services\Exam\ExamService;
use Database\Seeders\LevelTrackSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChallengeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            SettingSeeder::class,
            LevelTrackSeeder::class,
        ]);

        $role = Role::where('slug', 'candidate')->first();
        $this->user = User::factory()->create(['role_id' => $role->id]);
    }

    private function c1Test(): ModellTest
    {
        return ModellTest::where('number', LevelTrackSeeder::C1_TEST_NUMBER)->firstOrFail();
    }

    /** Joue le test C1 en 100 % (toutes les réponses objectives correctes). */
    private function playPerfect(): void
    {
        $engine = app(ExamService::class);
        $answers = app(AnswerService::class);
        $attempt = $engine->startAttempt($this->user, $this->c1Test());

        foreach ($attempt->attemptExercises()->orderBy('position')->get() as $task) {
            $engine->startExercise($attempt->fresh(), $task->exercise_id);
            $task = $task->fresh();

            foreach ($task->formQuestions() as $question) {
                if (! $question->isObjective()) {
                    continue;
                }
                $answers->save($attempt->fresh(), $task->fresh(), $question, (array) $question->correct_answer);
                $engine->advanceQuestion($attempt->fresh(), $task->fresh());
            }

            $engine->completeExercise($attempt->fresh(), $task->exercise_id);
        }
    }

    /** 20 items IA valides et variés (difficultés mélangées). */
    private function fakeItems(int $n, int $offset = 0): array
    {
        $diffs = ['B2', 'C1', 'C1+'];
        $items = [];

        for ($i = 0; $i < $n; $i++) {
            $k = $offset + $i;
            $items[] = [
                'stimulus' => "Stimulus allemand original numero {$k} sur la vie universitaire et la recherche.",
                'prompt' => "Question inédite numero {$k} : quelle affirmation est correcte ?",
                'options' => ['a' => "Option A {$k}", 'b' => "Option B {$k}", 'c' => "Option C {$k}", 'd' => "Option D {$k}"],
                'correct' => ['a', 'b', 'c', 'd'][$k % 4],
                'explanation' => "Explication {$k}.",
                'difficulty' => $diffs[$k % 3],
            ];
        }

        return $items;
    }

    public function test_two_consecutive_perfect_scores_unlock_elite(): void
    {
        $this->playPerfect();
        $this->assertFalse(
            ChallengeUnlock::where('user_id', $this->user->id)->exists(),
            '1 seul 100 % : pas de déblocage.'
        );

        $this->playPerfect();

        $unlock = ChallengeUnlock::where('user_id', $this->user->id)
            ->where('modell_test_id', $this->c1Test()->id)
            ->first();
        $this->assertNotNull($unlock, '2× 100 % consécutifs : Espace Élite débloqué.');

        $this->assertTrue(
            $this->user->notifications()->where('data->type', 'challenge_unlocked')->exists(),
            'Notification de déblocage envoyée.'
        );
    }

    public function test_generation_creates_20_timed_questions_in_hidden_draft_test(): void
    {
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('enabled')->andReturn(true);
            $mock->shouldReceive('generateJson')->once()->andReturn($this->fakeItems(20));
        });

        ChallengeUnlock::create([
            'user_id' => $this->user->id,
            'modell_test_id' => $this->c1Test()->id,
            'unlocked_at' => now(),
        ]);

        $service = app(ChallengeService::class);
        $challenge = AiChallenge::create([
            'user_id' => $this->user->id,
            'modell_test_id' => $this->c1Test()->id,
            'skill' => 'lesen',
            'status' => 'generating',
            'level' => 'C1',
            'weak_snapshot' => [],
            'requested_at' => now(),
        ]);

        $service->generate($challenge);

        $challenge = $challenge->fresh();
        $this->assertSame('ready', $challenge->status);
        $this->assertNotNull($challenge->generated_test_id);

        $test = $challenge->generatedTest;
        $this->assertSame('draft', $test->status, 'Test généré invisible de la liste publique.');

        $questions = $test->sections->flatMap(fn ($s) => $s->exercises)
            ->flatMap(fn ($e) => $e->questions);
        $this->assertGreaterThanOrEqual(20, $questions->count(), 'Minimum 20 QCM.');

        // Minuteur par question selon la difficulté (90 / 120 / 150 s).
        $expected = ['B2' => 90, 'C1' => 120, 'C1+' => 150];
        foreach ($questions as $q) {
            $this->assertSame($expected[$q->difficulty], (int) $q->time_limit_seconds);
            $this->assertCount(4, $q->answerOptions);
        }

        // Jouer le défi passe par le moteur standard.
        $attempt = app(ExamService::class)->startAttempt($this->user, $test);
        $this->assertSame(1, $attempt->attemptExercises()->count());

        // Jouable via l'Espace Élite (brouillon, hors listes publiques).
        $this->actingAs($this->user)->get(route('challenges.play', $challenge->fresh()))
            ->assertRedirect(route('exam.show', $attempt->id));
    }

    public function test_level_generation_c2_without_unlock(): void
    {
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('enabled')->andReturn(true);
            $mock->shouldReceive('generateJson')->once()->andReturn($this->fakeItems(20, 500));
        });

        // C2 : aucune banque, génération directe sans condition de score.
        $challenge = app(ChallengeService::class)->requestLevelGeneration($this->user, 'C2');

        $this->assertSame('C2', $challenge->level);
        $this->assertNull($challenge->modell_test_id);

        app(ChallengeService::class)->generate($challenge->fresh());

        $challenge = $challenge->fresh();
        $this->assertSame('ready', $challenge->status);
        $this->assertSame('C2', $challenge->generatedTest->difficulty);

        // B1 n'a pas de génération IA.
        try {
            app(ChallengeService::class)->requestLevelGeneration($this->user, 'B1');
            $this->fail('B1 ne doit pas accepter de génération IA.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('C1 et C2', $e->getMessage());
        }
    }

    public function test_incomplete_generation_marks_failed(): void
    {
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('enabled')->andReturn(true);
            $mock->shouldReceive('generateJson')->twice()->andReturnValues([
                $this->fakeItems(5), $this->fakeItems(5, 100),
            ]);
        });

        $challenge = AiChallenge::create([
            'user_id' => $this->user->id,
            'modell_test_id' => $this->c1Test()->id,
            'skill' => 'lesen',
            'status' => 'generating',
            'level' => 'C1',
            'weak_snapshot' => [],
            'requested_at' => now(),
        ]);

        app(ChallengeService::class)->generate($challenge);

        $this->assertSame('failed', $challenge->fresh()->status);
        $this->assertNotNull($challenge->fresh()->error);
    }

    public function test_store_requires_unlock_and_quota(): void
    {
        // Sans déblocage → refusé.
        $this->actingAs($this->user)
            ->post(route('challenges.store'), ['modell_test_id' => $this->c1Test()->id])
            ->assertRedirect()
            ->assertSessionHas('error');

        // Débloqué mais quota à 0 → refusé.
        ChallengeUnlock::create([
            'user_id' => $this->user->id,
            'modell_test_id' => $this->c1Test()->id,
            'unlocked_at' => now(),
        ]);
        Setting::put('max_ai_requests', 0, 'int');

        $this->actingAs($this->user)
            ->post(route('challenges.store'), ['modell_test_id' => $this->c1Test()->id])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_index_and_dashboard_render_elite_space(): void
    {
        ChallengeUnlock::create([
            'user_id' => $this->user->id,
            'modell_test_id' => $this->c1Test()->id,
            'unlocked_at' => now(),
        ]);

        $this->actingAs($this->user)->get(route('challenges.index'))
            ->assertOk()
            ->assertSee('Espace Élite', false)
            ->assertSee('Générer mes 20 QCM inédits', false);

        $this->actingAs($this->user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Espace Élite', false);
    }

    public function test_play_is_owner_only(): void
    {
        $challenge = AiChallenge::create([
            'user_id' => $this->user->id,
            'modell_test_id' => $this->c1Test()->id,
            'skill' => 'lesen',
            'status' => 'ready',
            'level' => 'C1',
            'weak_snapshot' => [],
            'requested_at' => now(),
        ]);

        $other = User::factory()->create();
        $this->actingAs($other)->get(route('challenges.play', $challenge))->assertForbidden();
    }
}
