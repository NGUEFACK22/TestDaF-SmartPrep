<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Models\Attempt;
use App\Models\ModellTest;
use App\Models\Role;
use App\Models\User;
use App\Services\Exam\AnswerService;
use App\Services\Exam\ExamService;
use Database\Seeders\LevelTrackSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LevelTestFlowTest — entraînement QCM par niveau (A1 → C2).
 *
 * - La page Préparation liste les 6 niveaux (banques A1–C1 + IA C1–C2).
 * - Démarrer un niveau crée une tentative chronométrée (tirage sans remise).
 * - C1 (format TestDaF, 3 tâches) se joue en entier jusqu'aux résultats.
 */
class LevelTestFlowTest extends TestCase
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

    public function test_preparation_page_lists_all_levels_with_bank_and_ia(): void
    {
        $response = $this->actingAs($this->user)->get(route('preparation.index'));

        $response->assertOk();

        foreach (['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $level) {
            $response->assertSee($level);
        }

        $response->assertSee('Banque QCM', false);
        $response->assertSee('IA inédite', false);
        $response->assertSee('Espace Élite', false);
    }

    public function test_starting_a_bank_level_creates_a_timed_attempt(): void
    {
        $response = $this->actingAs($this->user)->post(route('preparation.start', 'A1'));

        $response->assertRedirect();

        $attempt = Attempt::latest('id')->first();
        $this->assertNotNull($attempt);
        $this->assertSame(ModellTest::where('number', 1)->first()->id, $attempt->modell_test_id);

        $tasks = $attempt->attemptExercises()->orderBy('position')->get();
        $this->assertCount(1, $tasks);
        $this->assertSame('lesen', $tasks->first()->exercise->skill->value);
    }

    public function test_unknown_levels_cannot_start_a_test(): void
    {
        // C2 n'a pas de banque : démarrage classique impossible (IA uniquement).
        $this->actingAs($this->user)
            ->post(route('preparation.start', 'C2'))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->post(route('preparation.start', 'Z9'))
            ->assertNotFound();
    }

    public function test_level_generation_is_limited_to_c1_and_c2(): void
    {
        $this->actingAs($this->user)
            ->post(route('preparation.generate', 'B1'))
            ->assertNotFound();

        // Sans clé IA : la demande est créée puis échoue proprement (job sync).
        $response = $this->actingAs($this->user)
            ->post(route('preparation.generate', 'C1'));
        $response->assertRedirect(route('challenges.index'));
    }

    public function test_c1_tasks_use_timed_forms(): void
    {
        $engine = app(ExamService::class);
        $attempt = $engine->startAttempt($this->user, $this->c1Test());

        $tasks = $attempt->attemptExercises()->orderBy('position')->get();
        $this->assertCount(3, $tasks);

        // Formats 3/1/3, toutes les questions chronométrées.
        $this->assertSame([3, 1, 3], $tasks->map(fn ($ae) => count($ae->question_form))->all());

        foreach ($tasks as $task) {
            $this->assertTrue($task->formQuestions()->every(
                fn ($q) => $q->time_limit_seconds > 0
            ));
        }
    }

    public function test_full_c1_run_scores_and_shows_results(): void
    {
        $engine = app(ExamService::class);
        $answers = app(AnswerService::class);

        $attempt = $engine->startAttempt($this->user, $this->c1Test());

        foreach ($attempt->attemptExercises()->orderBy('position')->get() as $task) {
            $engine->startExercise($attempt->fresh(), $task->exercise_id);
            $task = $task->fresh();

            $outcome = null;

            foreach ($task->formQuestions() as $question) {
                $answers->save($attempt->fresh(), $task->fresh(), $question, (array) $question->correct_answer);
                $outcome = $engine->advanceQuestion($attempt->fresh(), $task->fresh())['outcome'];
            }

            // La dernière question clôture la tâche (ou le test pour la fin).
            $this->assertContains($outcome, ['exercise', 'finished']);
        }

        $attempt = $attempt->fresh();
        $this->assertSame(AttemptStatus::Completed, $attempt->status);

        // Score Lesen : 3 (QCM) + 2 (trous) + 3 (vrai/faux) = 8 points.
        $results = $attempt->results()->get()->keyBy(fn ($r) => $r->skill->value);
        $this->assertArrayHasKey('lesen', $results);
        $this->assertEqualsWithDelta(8.0, (float) $results['lesen']->max_points, 0.01);
        $this->assertEqualsWithDelta(8.0, (float) $results['lesen']->points, 0.01);

        // Page résultats : score par partie + points à améliorer.
        $this->actingAs($this->user)
            ->get(route('results.show', $attempt))
            ->assertOk()
            ->assertSee('Score par partie')
            ->assertSee('Points à améliorer');
    }
}
