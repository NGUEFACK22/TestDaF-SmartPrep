<?php

namespace App\Services\Exam;

use App\Models\Attempt;
use App\Models\AttemptExercise;
use App\Models\ExamLog;

/**
 * QuestionTimerService — chronométrage par question (autorité serveur).
 *
 * Chaque question réceptive (Lesen/Hören) possède son temps propre stocké
 * en base (questions.time_limit_seconds). Le serveur calcule :
 *   current_question_expires_at = démarrage question + durée question
 * et plafonne par expires_at de la tâche. Une question dépassée ou validée
 * ne peut plus recevoir de réponse.
 */
class QuestionTimerService
{
    /** Démarre (ou reprend) le chronomètre de la question courante. */
    public function start(AttemptExercise $attemptExercise): ?\Carbon\CarbonInterface
    {
        $attemptExercise->loadMissing('exercise');

        if ($attemptExercise->isProductiveExercise()) {
            return $attemptExercise->expires_at;
        }

        $question = $attemptExercise->currentQuestion();

        if (! $question) {
            return $attemptExercise->expires_at;
        }

        $limit = $question->time_limit_seconds !== null ? (int) $question->time_limit_seconds : null;

        if ($limit === null || $limit <= 0) {
            $attemptExercise->update(['current_question_expires_at' => $attemptExercise->expires_at]);

            return $attemptExercise->expires_at;
        }

        if ($attemptExercise->current_question_expires_at) {
            return $attemptExercise->current_question_expires_at;
        }

        $expiresAt = now()->addSeconds($limit);

        if ($attemptExercise->expires_at && $expiresAt->greaterThan($attemptExercise->expires_at)) {
            $expiresAt = $attemptExercise->expires_at->copy();
        }

        $attemptExercise->update(['current_question_expires_at' => $expiresAt]);

        $this->log($attemptExercise->attempt, 'question_started', [
            'exercise_id' => $attemptExercise->exercise_id,
            'question_id' => $question->id,
            'question_index' => $attemptExercise->questionIndex(),
            'time_limit_seconds' => $limit,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return $attemptExercise->refresh()->current_question_expires_at;
    }

    /**
     * Synchronise avec l'heure serveur : avance l'index tant que la
     * question courante est expirée. Retourne true si l'index a changé.
     */
    public function sync(AttemptExercise $attemptExercise): bool
    {
        if ($attemptExercise->state()->isFinal() || $attemptExercise->isProductiveExercise()) {
            return false;
        }

        $question = $attemptExercise->currentQuestion();

        // Le verrouillage par question ne s'applique qu'aux questions
        // réellement chronométrées (time_limit_seconds > 0).
        if (! $question || $question->time_limit_seconds === null || (int) $question->time_limit_seconds <= 0) {
            return false;
        }

        $attemptExercise->loadMissing('exercise');
        $changed = false;

        while (! $attemptExercise->state()->isFinal() && $attemptExercise->currentQuestionExpired()) {
            $advanced = $this->advance($attemptExercise->refresh(), expired: true);
            $changed = true;

            if (! $advanced) {
                break;
            }
        }

        return $changed;
    }

    /**
     * Avance à la question suivante (validation manuelle ou expiration).
     * Retourne false si la tâche doit être finalisée (dernière question).
     */
    public function advance(AttemptExercise $attemptExercise, bool $expired = false): bool
    {
        $attemptExercise->loadMissing('exercise');

        if ($attemptExercise->isProductiveExercise()) {
            return false;
        }

        $questions = $attemptExercise->formQuestions();
        $next = $attemptExercise->questionIndex() + 1;

        $this->log($attemptExercise->attempt, $expired ? 'question_expired' : 'question_completed', [
            'exercise_id' => $attemptExercise->exercise_id,
            'question_index' => $attemptExercise->questionIndex(),
            'next_index' => $next,
        ]);

        if ($next >= $questions->count()) {
            return false;
        }

        $attemptExercise->update([
            'current_question_index' => $next,
            'current_question_expires_at' => null,
        ]);

        $this->start($attemptExercise->refresh());

        return true;
    }

    /** Réinitialise le pointeur (démarrage de la tâche). */
    public function reset(AttemptExercise $attemptExercise): void
    {
        $attemptExercise->update([
            'current_question_index' => 0,
            'current_question_expires_at' => null,
        ]);

        $this->start($attemptExercise->refresh());
    }

    /** Indicateurs d'affichage (le JS ne fait qu'afficher). */
    public function display(AttemptExercise $attemptExercise): array
    {
        $attemptExercise->loadMissing('exercise');
        $question = $attemptExercise->currentQuestion();
        $count = $attemptExercise->formQuestions()->count();

        $timed = $question !== null
            && $question->time_limit_seconds !== null
            && (int) $question->time_limit_seconds > 0
            && ! $attemptExercise->isProductiveExercise();

        if (! $timed) {
            return [
                'enabled' => false,
                'index' => 0,
                'total' => $count,
                'question_id' => $question?->getKey(),
                'duration_seconds' => null,
                'remaining_seconds' => null,
                'expires_at' => null,
                'server_now' => now()->toIso8601String(),
                'expired' => false,
            ];
        }

        $remaining = $attemptExercise->currentQuestionRemainingSeconds();
        $duration = (int) $question->time_limit_seconds;

        return [
            'enabled' => true,
            'index' => $attemptExercise->questionIndex(),
            'total' => $count,
            'question_id' => $question->getKey(),
            'duration_seconds' => $duration,
            'remaining_seconds' => $remaining,
            'expires_at' => $attemptExercise->current_question_expires_at?->toIso8601String(),
            'server_now' => now()->toIso8601String(),
            'expired' => $remaining <= 0,
        ];
    }

    private function log(?Attempt $attempt, string $event, array $payload = []): void
    {
        ExamLog::create([
            'user_id' => $attempt?->user_id,
            'attempt_id' => $attempt?->id,
            'event' => $event,
            'payload' => $payload,
            'ip' => request()->ip(),
        ]);
    }
}
