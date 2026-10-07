<?php

namespace Tests\Unit;

use App\Enums\AttemptStatus;
use App\Enums\ExerciseState;
use App\Enums\Skill;
use App\Models\Attempt;
use App\Models\AttemptExercise;
use App\Models\Exercise;
use App\Models\ModellTest;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QuestionTimerTest — aides du chronomètre par question (modèle).
 *
 * Vérifie la sémantique côté modèle : index courant borné, question courante
 * selon la forme active, temps restant plafonné par la tâche, détection
 * d'expiration et verrouillage séquentiel (anti-cheat).
 */
class QuestionTimerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Attempt $attempt;

    private Exercise $exercise;

    private array $questionIds;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $test = ModellTest::create([
            'number' => 99,
            'title' => 'Test timer unitaire',
            'status' => 'published',
        ]);

        $this->exercise = Exercise::create([
            'skill' => Skill::Lesen,
            'type' => 'multiple_choice',
            'title' => 'Tâche chronométrée',
            'duration_seconds' => 600,
            'points' => 2,
            'position' => 0,
            'status' => 'published',
        ]);

        // Trois questions chronométrées (300 s chacune).
        $this->questionIds = [];
        foreach (['Q1', 'Q2', 'Q3'] as $i => $label) {
            $question = Question::create([
                'exercise_id' => $this->exercise->id,
                'type' => 'single_choice',
                'position' => $i,
                'prompt' => $label,
                'points' => 1,
                'time_limit_seconds' => 300,
            ]);
            $this->questionIds[] = $question->id;
        }

        $this->attempt = Attempt::create([
            'user_id' => $this->user->id,
            'modell_test_id' => $test->id,
            'mode' => 'exam',
            'status' => AttemptStatus::InProgress,
            'started_at' => now(),
        ]);
    }

    /** Crée une tâche de tentative en cours sur l'exercice chronométré. */
    private function attemptExercise(array $overrides = []): AttemptExercise
    {
        return AttemptExercise::create(array_merge([
            'attempt_id' => $this->attempt->id,
            'exercise_id' => $this->exercise->id,
            'position' => 0,
            'status' => ExerciseState::Started,
            'started_at' => now()->subMinute(),
            'expires_at' => now()->addMinutes(9),
            'question_form' => $this->questionIds,
        ], $overrides));
    }

    // ------------------------------------------------------------- index

    public function test_question_index_follows_the_active_form(): void
    {
        $ae = $this->attemptExercise();

        $this->assertSame(0, $ae->questionIndex());
        $this->assertSame($this->questionIds[0], (int) $ae->currentQuestion()->id);

        $ae->update(['current_question_index' => 2]);
        $this->assertSame(2, $ae->fresh()->questionIndex());
        $this->assertSame($this->questionIds[2], (int) $ae->fresh()->currentQuestion()->id);

        // Index hors forme : borné à la dernière question.
        $ae->update(['current_question_index' => 99]);
        $this->assertSame(2, $ae->fresh()->questionIndex());
    }

    public function test_current_question_uses_form_order_not_pool_order(): void
    {
        // La forme retire la question du milieu : l'index 1 pointe sur Q3.
        $ae = $this->attemptExercise(['question_form' => [$this->questionIds[0], $this->questionIds[2]]]);

        $this->assertSame($this->questionIds[0], (int) $ae->currentQuestion()->id);

        $ae->update(['current_question_index' => 1]);
        $this->assertSame($this->questionIds[2], (int) $ae->fresh()->currentQuestion()->id);
    }

    // ------------------------------------------------------------- temps

    public function test_remaining_seconds_fall_back_to_exercise_when_unset(): void
    {
        $ae = $this->attemptExercise(['expires_at' => now()->addSeconds(45)]);

        // Pas encore de limite de question → le temps de la tâche s'applique.
        $this->assertGreaterThanOrEqual(40, $ae->currentQuestionRemainingSeconds());

        $ae->update(['current_question_expires_at' => now()->addSeconds(120)]);
        $this->assertGreaterThanOrEqual(30, $ae->fresh()->currentQuestionRemainingSeconds());
    }

    public function test_current_question_expiry_detection(): void
    {
        $ae = $this->attemptExercise();

        $this->assertFalse($ae->currentQuestionExpired());

        $ae->update(['current_question_expires_at' => now()->addSeconds(30)]);
        $this->assertFalse($ae->fresh()->currentQuestionExpired());

        $ae->update(['current_question_expires_at' => now()->subSecond()]);
        $this->assertTrue($ae->fresh()->currentQuestionExpired());
    }

    // ------------------------------------------------------------- verrou

    public function test_only_the_current_timed_question_accepts_answers(): void
    {
        $ae = $this->attemptExercise(['current_question_index' => 1]);
        $ae->update(['current_question_expires_at' => now()->addMinute()]);
        $ae = $ae->fresh();

        [$q1, $q2, $q3] = Question::whereIn('id', $this->questionIds)->get()->all();

        $this->assertTrue($ae->acceptsAnswerFor($q2));   // question courante
        $this->assertFalse($ae->acceptsAnswerFor($q1));  // question passée
        $this->assertFalse($ae->acceptsAnswerFor($q3));  // question future
    }

    public function test_expired_current_question_rejects_answers(): void
    {
        $ae = $this->attemptExercise(['current_question_expires_at' => now()->subSecond()]);
        $current = $ae->currentQuestion();

        $this->assertFalse($ae->acceptsAnswerFor($current));
    }

    public function test_un_timed_receptive_exercise_keeps_the_full_form(): void
    {
        // Pool legacy sans time_limit_seconds : toutes les questions acceptent
        // la réponse jusqu'à la fin de la tâche (pas de verrou par question).
        Question::query()->whereIn('id', $this->questionIds)->update(['time_limit_seconds' => null]);

        $ae = $this->attemptExercise();
        $future = Question::find($this->questionIds[2]);

        $this->assertTrue($ae->acceptsAnswerFor($future));
        $this->assertFalse($ae->currentQuestionExpired());
    }

    public function test_lesen_exercise_is_never_productive(): void
    {
        // Plateforme 100 % QCM écrit : plus aucune tâche productive.
        $ae = $this->attemptExercise();

        $this->assertFalse($ae->isProductiveExercise());
        $this->assertFalse(Skill::Lesen->isProductive());
    }
}