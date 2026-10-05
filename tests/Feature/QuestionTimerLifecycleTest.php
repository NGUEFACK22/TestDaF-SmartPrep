<?php

namespace Tests\Feature;

use App\Enums\ExerciseState;
use App\Enums\Skill;
use App\Exceptions\ExamException;
use App\Models\Attempt;
use App\Models\ModellTest;
use App\Models\Role;
use App\Models\User;
use App\Services\Exam\AnswerService;
use App\Services\Exam\ExamService;
use App\Services\Exam\QuestionTimerService;
use Database\Seeders\ModellTestSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QuestionTimerLifecycleTest — chronomètre par question (autorité serveur).
 *
 * Scénarios : initialisation au démarrage de la tâche, avancement séquentiel
 * (SUIVANT), expiration forcée, clôture de la tâche sur la dernière question,
 * anti-cheat (réponse après expiration / question future / tâche close) et
 * immunité des tâches productives (Schreiben/Sprechen).
 */
class QuestionTimerLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Attempt $attempt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, SettingSeeder::class, ModellTestSeeder::class]);

        $role = Role::where('slug', 'candidate')->first();
        $this->user = User::factory()->create(['role_id' => $role->id]);
        $this->attempt = app(ExamService::class)
            ->startAttempt($this->user, ModellTest::where('number', 1)->first());
    }

    /** Première tâche (Lesen QCM chronométré) démarrée côté serveur. */
    private function startedFirstExercise(): \App\Models\AttemptExercise
    {
        $engine = app(ExamService::class);
        $first = $this->attempt->attemptExercises()->orderBy('position')->first();

        return $engine->startExercise($this->attempt, $first->exercise_id)->fresh();
    }

    // ------------------------------------------------------------- démarrage

    public function test_exercise_start_initializes_the_question_timer(): void
    {
        $ae = $this->startedFirstExercise();
        $questions = app(QuestionTimerService::class)->display($ae);

        $this->assertSame(0, (int) $ae->current_question_index);
        $this->assertNotNull($ae->current_question_expires_at);

        // Limite = durée de la question (300 s), plafonnée par la tâche (600 s).
        $this->assertTrue($ae->current_question_expires_at->greaterThan(now()->addSeconds(250)));
        $this->assertTrue($ae->current_question_expires_at->lessThanOrEqualTo($ae->expires_at));

        $this->assertTrue($questions['enabled']);
        $this->assertSame(0, $questions['index']);
        $this->assertSame(2, $questions['total']);
        $this->assertSame(300, $questions['duration_seconds']);
    }

    // ------------------------------------------------------------- avancement

    public function test_next_route_advances_to_the_next_question(): void
    {
        $engine = app(ExamService::class);
        $answers = app(AnswerService::class);
        $ae = $this->startedFirstExercise();

        $form = $ae->formQuestions();
        $this->assertCount(2, $form);

        $answers->save($this->attempt, $ae, $form->first(), ['A']);

        $response = $this->actingAs($this->user)->postJson(
            route('exam.next', [$this->attempt, $ae]),
            []
        );

        $response->assertOk()->assertJsonPath('outcome', 'advanced');

        $ae = $ae->fresh();
        $this->assertSame(1, (int) $ae->current_question_index);
        $this->assertNotSame($form->first()->id, $ae->currentQuestion()->id);

        // Nouveau délai de question, toujours plafonné par la tâche.
        $this->assertNotNull($ae->current_question_expires_at);
        $this->assertTrue($ae->current_question_expires_at->lessThanOrEqualTo($ae->expires_at));
    }

    public function test_advancing_on_the_last_question_completes_the_exercise(): void
    {
        $engine = app(ExamService::class);
        $answers = app(AnswerService::class);
        $ae = $this->startedFirstExercise();

        $form = $ae->formQuestions();
        foreach ($form as $question) {
            $answers->save($this->attempt, $ae->fresh(), $question, ['A']);
            $result = $engine->advanceQuestion($this->attempt->fresh(), $ae->fresh());
        }

        $this->assertSame('exercise', $result['outcome']);

        $ae = $ae->fresh();
        $this->assertSame(ExerciseState::Completed, $ae->state());
        $this->assertTrue((bool) $ae->answers_locked);

        // La tâche suivante est activée (navigation séquentielle).
        $next = $this->attempt->fresh()->currentExercise();
        $this->assertNotSame($ae->exercise_id, $next->exercise_id);
        $this->assertSame(ExerciseState::Available, $next->state());
    }

    // ------------------------------------------------------------- expiration

    public function test_sync_closes_the_exercise_when_the_last_question_times_out(): void
    {
        $engine = app(ExamService::class);
        $ae = $this->startedFirstExercise();

        // Simule : le candidat est sur la dernière question et son temps a échappé.
        $ae->update([
            'current_question_index' => $ae->formQuestions()->count() - 1,
            'current_question_expires_at' => now()->subSeconds(5),
        ]);

        $result = $engine->advanceQuestion($this->attempt->fresh(), $ae->fresh());

        $this->assertContains($result['outcome'], ['exercise', 'finished']);
        $this->assertTrue($ae->fresh()->state()->isFinal());
    }

    public function test_answer_after_question_expiry_is_rejected(): void
    {
        $answers = app(AnswerService::class);
        $ae = $this->startedFirstExercise();

        $current = $ae->currentQuestion();
        $ae->update(['current_question_expires_at' => now()->subSeconds(5)]);

        try {
            $answers->save($this->attempt, $ae->fresh(), $current, ['A']);
            $this->fail('La réponse tardive aurait dû être refusée.');
        } catch (ExamException $e) {
            $this->assertSame('question_locked', $e->reason());
        }

        // Le verrou est également signalé via l'API (JSON machine).
        $response = $this->actingAs($this->user)->postJson(
            route('api.answers.store', $this->attempt),
            ['question_id' => $current->id, 'answer' => ['A']]
        );
        $response->assertStatus(423)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('reason', 'question_locked');
    }

    public function test_future_question_answer_is_rejected_over_http(): void
    {
        $ae = $this->startedFirstExercise();
        $form = $ae->formQuestions();
        $future = $form->get(1);

        $response = $this->actingAs($this->user)->postJson(
            route('api.answers.store', $this->attempt),
            ['question_id' => $future->id, 'answer' => ['A']]
        );

        $response->assertStatus(423)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('reason', 'question_locked');
    }

    public function test_answer_after_exercise_completion_is_still_rejected(): void
    {
        $engine = app(ExamService::class);
        $answers = app(AnswerService::class);
        $ae = $this->startedFirstExercise();

        $engine->completeExercise($this->attempt, $ae->exercise_id);
        $question = $ae->formQuestions()->first();

        try {
            $answers->save($this->attempt, $ae->fresh(), $question, ['A']);
            $this->fail('Réponse après clôture refusée attendue.');
        } catch (ExamException $e) {
            $this->assertSame(423, $e->getCode());
        }
    }

    public function test_index_never_moves_backwards(): void
    {
        $engine = app(ExamService::class);
        $ae = $this->startedFirstExercise();

        // Manipulation directe de la base (indice sauté) : l'avancement
        // n'existe que dans le sens SUIVANT.
        $ae->update(['current_question_index' => 1]);

        $result = $engine->advanceQuestion($this->attempt->fresh(), $ae->fresh());
        $this->assertContains($result['outcome'], ['exercise', 'finished']);
        $this->assertGreaterThanOrEqual(1, (int) $ae->fresh()->current_question_index);
    }

    // ------------------------------------------------------------- intégration

    public function test_timer_endpoint_returns_server_authoritative_question_state(): void
    {
        $ae = $this->startedFirstExercise();

        $response = $this->actingAs($this->user)->getJson(route('exam.timer', $this->attempt));

        $response->assertOk()
            ->assertJsonPath('finished', false)
            ->assertJsonPath('question_timer.enabled', true)
            ->assertJsonPath('question.id', $ae->currentQuestion()->id)
            ->assertJsonStructure([
                'timer' => ['remaining_seconds', 'expires_at', 'server_now'],
                'question_timer' => [
                    'enabled', 'index', 'total', 'duration_seconds',
                    'remaining_seconds', 'expires_at', 'server_now',
                ],
                'question' => ['id', 'type', 'prompt', 'time_limit_seconds'],
            ]);

        // Le temps restant de question est borné par le temps de la tâche.
        $this->assertLessThanOrEqual(
            $response->json('timer.remaining_seconds'),
            $response->json('question_timer.remaining_seconds') + 5
        );
    }

    public function test_productive_tasks_are_outside_the_question_timer(): void
    {
        $engine = app(ExamService::class);
        $questionTimer = app(QuestionTimerService::class);

        // Navigation séquentielle : on franchit d'abord les tâches réceptives.
        foreach ($this->attempt->attemptExercises()->orderBy('position')->get() as $task) {
            if ($task->exercise->skill === Skill::Schreiben) {
                break;
            }
            $engine->startExercise($this->attempt->fresh(), $task->exercise_id);
            $engine->completeExercise($this->attempt->fresh(), $task->exercise_id);
        }

        $writing = $this->attempt->attemptExercises()->get()
            ->first(fn ($ae) => $ae->exercise->skill === Skill::Schreiben);

        $engine->startExercise($this->attempt->fresh(), $writing->exercise_id);
        $writing = $writing->fresh();

        $display = $questionTimer->display($writing);
        $this->assertFalse($display['enabled']);
        $this->assertNull($display['duration_seconds']);
        $this->assertNull($writing->current_question_expires_at);

        // advanceQuestion ne fait pas avancer sur une tâche productive.
        $result = $engine->advanceQuestion($this->attempt->fresh(), $writing->fresh());
        $this->assertSame('exercise', $result['outcome']);
        $this->assertFalse($writing->fresh()->state()->isFinal());
    }
}