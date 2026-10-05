<?php

namespace App\Services\Exam;

use App\Enums\ExerciseState;
use App\Models\Attempt;
use App\Models\AttemptExercise;
use App\Models\ExamLog;

/**
 * TimerService — chronométrage autoritaire côté serveur.
 *
 * Le temps d'un exercice est TOUJOURS calculé à partir de :
 *   expires_at = started_at + duration_seconds (stocké en base)
 * et vérifié avec l'heure serveur (now()). Le JavaScript ne sert qu'à
 * rafraîchir l'affichage et ne peut jamais prolonger le temps.
 */
class TimerService
{
    /** Démarre le chronomètre propre à la tâche. */
    public function start(AttemptExercise $attemptExercise): AttemptExercise
    {
        if ($attemptExercise->started_at && ! $attemptExercise->state()->isFinal()) {
            return $attemptExercise; // déjà démarré
        }

        $duration = (int) $attemptExercise->exercise->duration_seconds;
        $startedAt = now();

        $attemptExercise->update([
            'status' => ExerciseState::Started,
            'started_at' => $startedAt,
            'expires_at' => $startedAt->copy()->addSeconds($duration),
            'answers_locked' => false,
        ]);

        $this->log($attemptExercise->attempt, 'exercise_started', [
            'exercise_id' => $attemptExercise->exercise_id,
            'duration_seconds' => $duration,
            'expires_at' => $attemptExercise->expires_at?->toIso8601String(),
        ]);

        return $attemptExercise->refresh();
    }

    /** Arrête le chronomètre (fin normale ou expiration). */
    public function stop(AttemptExercise $attemptExercise, bool $expired = false): void
    {
        if ($attemptExercise->state()->isFinal()) {
            return;
        }

        $timeSpent = $this->elapsedSeconds($attemptExercise);
        $attemptExercise->update([
            'status' => $expired ? ExerciseState::Expired : ExerciseState::Completed,
            'completed_at' => now(),
            'answers_locked' => true,
            'time_spent' => $timeSpent,
        ]);

        $this->log($attemptExercise->attempt, $expired ? 'exercise_expired' : 'exercise_completed', [
            'exercise_id' => $attemptExercise->exercise_id,
            'time_spent' => $timeSpent,
        ]);
    }

    /** Temps restant (secondes) selon l'heure serveur. Jamais négatif. */
    public function remainingSeconds(AttemptExercise $attemptExercise): int
    {
        if (! $attemptExercise->expires_at) {
            return (int) $attemptExercise->exercise->duration_seconds;
        }

        return max(0, now()->diffInSeconds($attemptExercise->expires_at, false));
    }

    /** Temps écoulé (secondes), plafonné à la durée autorisée. */
    public function elapsedSeconds(AttemptExercise $attemptExercise): int
    {
        if (! $attemptExercise->started_at) {
            return 0;
        }

        $allowed = (int) $attemptExercise->exercise->duration_seconds;
        $elapsed = now()->diffInSeconds($attemptExercise->started_at, false);

        return (int) min($allowed, max(0, $elapsed));
    }

    /**
     * Synchronise l'état avec l'heure serveur : si le délai est dépassé et que
     * la tâche n'est pas encore finalisée, elle est automatiquement expirée.
     * Utilisé à chaque requête (anti-contournement + reprise après coupure).
     */
    public function sync(AttemptExercise $attemptExercise): AttemptExercise
    {
        if (! $attemptExercise->state()->isFinal() && $attemptExercise->hasExpired()) {
            $this->stop($attemptExercise, expired: true);
        }

        return $attemptExercise->refresh();
    }

    /** Indicateurs pour l'affichage (le JS consomme ces valeurs serveur). */
    public function display(AttemptExercise $attemptExercise): array
    {
        $allowed = (int) $attemptExercise->exercise->duration_seconds;
        $remaining = $this->remainingSeconds($attemptExercise);
        $elapsed = $allowed > 0 ? max(0, $allowed - $remaining) : 0;
        $percent = $allowed > 0 ? round(($elapsed / $allowed) * 100, 2) : 0;

        return [
            'started_at' => $attemptExercise->started_at?->toIso8601String(),
            'expires_at' => $attemptExercise->expires_at?->toIso8601String(),
            'server_now' => now()->toIso8601String(),
            'duration_seconds' => $allowed,
            'remaining_seconds' => $remaining,
            'elapsed_seconds' => $elapsed,
            'progress_percent' => $percent,
            'warning' => $remaining > 0
                && $remaining <= (int) config('testdaf.exam.warning_seconds'),
            'expired' => $attemptExercise->state()->isFinal() && $attemptExercise->hasExpired(),
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
