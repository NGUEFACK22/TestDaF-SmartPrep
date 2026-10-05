<?php

namespace Tests\Feature;

use App\Exceptions\ExamException;
use App\Models\ModellTest;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAnswer;
use App\Services\Exam\AnswerService;
use App\Services\Exam\ExamService;
use Database\Seeders\HoerenDemoSeeder;
use Database\Seeders\LevelTestSeeder;
use Database\Seeders\ModellTestSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ExamSyncTest — robustesse aux doublons et aux requêtes en retard.
 *
 * Couvre le bug du test C1 (tentative #5) :
 * - re-sauvegarder une question déjà répondue renvoie 200 (pas de 423) ;
 * - `next` avec un `from_index` obsolète ne fait PAS avancer (resync) ;
 * - répondre à une question non courante jamais répondue reste un 423.
 */
class ExamSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            SettingSeeder::class,
            ModellTestSeeder::class,
            HoerenDemoSeeder::class,
            LevelTestSeeder::class,
        ]);

        $role = Role::where('slug', 'candidate')->first();
        $this->user = User::factory()->create(['role_id' => $role->id]);
    }

    private function c1Test(): ModellTest
    {
        return ModellTest::where('number', LevelTestSeeder::C1_TEST_NUMBER)->firstOrFail();
    }

    /**
     * Démarre le test C1 et fait passer la partie Hören (2 tâches)
     * pour que la tâche Lesen (questions chronométrées) soit courante.
     */
    private function readingTask(): array
    {
        $engine = app(ExamService::class);
        $answers = app(AnswerService::class);

        $attempt = $engine->startAttempt($this->user, $this->c1Test());

        foreach ($attempt->attemptExercises()->orderBy('position')->get() as $task) {
            if ($task->exercise->skill->value !== 'hoeren') {
                continue;
            }

            $engine->startExercise($attempt->fresh(), $task->exercise_id);

            foreach ($task->fresh()->formQuestions() as $question) {
                $answers->save($attempt->fresh(), $task->fresh(), $question, (array) $question->correct_answer);
                $engine->advanceQuestion($attempt->fresh(), $task->fresh());
            }
        }

        $reading = $attempt->fresh()
            ->attemptExercises
            ->first(fn ($ae) => $ae->exercise->skill->value === 'lesen');

        $engine->startExercise($attempt->fresh(), $reading->exercise_id);

        return [$attempt->fresh(), $reading->fresh()];
    }

    public function test_resaving_an_already_answered_question_is_a_duplicate_not_an_error(): void
    {
        $engine = app(ExamService::class);
        $answers = app(AnswerService::class);
        [$attempt, $task] = $this->readingTask();

        $q1 = $task->formQuestions()->first();

        // Première réponse (question courante, autorisée).
        $first = $answers->save($attempt->fresh(), $task->fresh(), $q1, ['b']);
        $this->assertSame(['b'], $first->decoded());

        // Le candidat valide → la question 1 est dépassée (plus autorisée).
        $engine->advanceQuestion($attempt->fresh(), $task->fresh());

        // Doublon client (autosave en retard) : la réponse existe déjà,
        // on la reçoit en 200 au lieu d'un 423 qui faisait "avancer" le test.
        $again = $answers->save($attempt->fresh(), $task->fresh(), $q1, ['b']);

        $this->assertSame(['b'], $again->decoded());
        $this->assertCount(1, UserAnswer::where('question_id', $q1->id)->get());
    }

    public function test_answering_a_not_current_question_that_was_never_answered_still_423(): void
    {
        $answers = app(AnswerService::class);
        [$attempt, $task] = $this->readingTask();

        $q2 = $task->formQuestions()->get(1);

        try {
            $answers->save($attempt->fresh(), $task->fresh(), $q2, ['a']);
            $this->fail('Une question future non répondue doit être refusée.');
        } catch (ExamException $e) {
            $this->assertSame(423, $e->getCode());
            $this->assertSame('question_locked', $e->reason());
        }

        $this->assertCount(0, UserAnswer::where('question_id', $q2->id)->get());
    }

    public function test_next_with_stale_from_index_does_not_advance_twice(): void
    {
        $engine = app(ExamService::class);
        [$attempt, $task] = $this->readingTask();

        $form = $task->formQuestions();
        $this->assertGreaterThanOrEqual(3, $form->count());

        // SUIVANT normal : question 0 → 1.
        $first = $engine->advanceQuestion($attempt->fresh(), $task->fresh(), 0);
        $this->assertSame('advanced', $first['outcome']);
        $this->assertSame(1, $task->fresh()->questionIndex());

        // SUIVANT en retard (le client affichait encore la question 0) :
        // le serveur n'avance PAS une 2ᵉ fois, il resynchronise.
        $stale = $engine->advanceQuestion($attempt->fresh(), $task->fresh(), 0);
        $this->assertSame('resync', $stale['outcome']);
        $this->assertSame(1, $task->fresh()->questionIndex());
        $this->assertSame($form->get(1)->id, $stale['question']['id']);
    }

    public function test_results_page_shows_the_final_grade_on_20(): void
    {
        $engine = app(ExamService::class);
        $answers = app(AnswerService::class);

        $attempt = $engine->startAttempt($this->user, $this->c1Test());

        foreach ($attempt->attemptExercises()->orderBy('position')->get() as $task) {
            $engine->startExercise($attempt->fresh(), $task->exercise_id);
            $task = $task->fresh();

            foreach ($task->formQuestions() as $question) {
                $answers->save($attempt->fresh(), $task->fresh(), $question, (array) $question->correct_answer);
                $engine->advanceQuestion($attempt->fresh(), $task->fresh());
            }
        }

        $attempt->refresh();
        $this->assertSame(\App\Enums\AttemptStatus::Completed, $attempt->status);

        $response = $this->actingAs($this->user)->get(route('results.show', $attempt));

        $response->assertOk()
            ->assertSee('Note : ')
            ->assertSee('/ 20')
            ->assertSee('moyenne des parties corrigées');
    }
}