<?php

namespace Tests\Feature;

use App\Exceptions\ExamException;
use App\Models\ModellTest;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAnswer;
use App\Services\Exam\AnswerService;
use App\Services\Exam\ExamService;
use Database\Seeders\LevelTrackSeeder;
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
            LevelTrackSeeder::class,
        ]);

        $role = Role::where('slug', 'candidate')->first();
        $this->user = User::factory()->create(['role_id' => $role->id]);
    }

    private function c1Test(): ModellTest
    {
        return ModellTest::where('number', LevelTrackSeeder::C1_TEST_NUMBER)->firstOrFail();
    }

    /** Démarre le test C1 et sa première tâche QCM chronométrée. */
    private function readingTask(): array
    {
        $engine = app(ExamService::class);

        $attempt = $engine->startAttempt($this->user, $this->c1Test());
        $reading = $attempt->attemptExercises()->orderBy('position')->first();

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

    /**
     * Bug utilisateur : après avoir terminé une partie (WEITER), le client
     * était envoyé vers la page résultats au lieu de la partie suivante.
     * Cause : l'endpoint complete renvoyait `redirect: null` quand une
     * tâche suivante existait, et le JS replombait sur le fallback
     * "results.show".
     */
    public function test_completing_a_part_redirects_to_the_next_part_not_the_results(): void
    {
        $engine = app(ExamService::class);
        $attempt = $engine->startAttempt($this->user, $this->c1Test());

        $first = $attempt->attemptExercises()->orderBy('position')->first();
        $engine->startExercise($attempt->fresh(), $first->exercise_id);
        $first = $first->fresh();

        $response = $this->actingAs($this->user)
            ->postJson(route('exam.complete', [$attempt, $first]));

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('finished', false);

        // `redirect` doit TOUJOURS être défini et pointer vers la page
        // d'examen (la partie suivante), jamais vers les résultats tant
        // qu'une tâche reste à faire.
        $this->assertSame(route('exam.show', $attempt), $response->json('redirect'));
        $this->assertNotSame(route('results.show', $attempt), $response->json('redirect'));
        $this->assertNotNull($response->json('next.exercise_id'));

        // La tâche suivante est bien activée (la page d'examen l'affiche).
        $next = $attempt->fresh()->currentExercise();
        $this->assertNotNull($next);
        $this->assertNotSame($first->exercise_id, $next->exercise_id);
        $this->assertSame(\App\Enums\ExerciseState::Available, $next->status);

        // La page d'examen rend la partie suivante (pas la page résultats).
        $this->actingAs($this->user)
            ->get(route('exam.show', $attempt))
            ->assertOk()
            ->assertSee('Aufgabe');
    }

    /**
     * Cas de la tentative #6 : une tâche expire (temps serveur) mais la
     * suivante n'a pas été activée et le pointeur courant pointe encore sur
     * la tâche clôturée. Au rechargement, restore() doit réparer l'état.
     */
    public function test_stale_pointer_after_task_expiry_is_repaired_on_restore(): void
    {
        $engine = app(ExamService::class);
        $attempt = $engine->startAttempt($this->user, $this->c1Test());

        $tasks = $attempt->attemptExercises()->orderBy('position')->get();
        $this->assertGreaterThanOrEqual(3, $tasks->count());
        $first = $tasks->first();

        $engine->startExercise($attempt->fresh(), $first->exercise_id);
        $this->assertSame($first->exercise_id, $attempt->fresh()->current_exercise_id);

        // Simule l'expiration serveur SANS activation de la suivante
        // (TimerService::sync clôturait la tâche, pointeur non remis à jour).
        $first->fresh()->update([
            'status' => \App\Enums\ExerciseState::Expired->value,
            'completed_at' => now(),
        ]);

        $engine->restore($attempt->fresh());

        // currentExercise() ne renvoie plus la tâche expirée : c'est la
        // suivante, activée par restore().
        $current = $attempt->fresh()->currentExercise();
        $this->assertNotNull($current);
        $this->assertNotSame($first->exercise_id, $current->exercise_id);
        $this->assertSame(\App\Enums\ExerciseState::Available, $current->status);
        $this->assertSame($current->exercise_id, $attempt->fresh()->current_exercise_id);
    }
}