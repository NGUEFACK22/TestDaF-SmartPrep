<?php

namespace App\Services\Exam;

use App\Enums\AttemptStatus;
use App\Enums\ExerciseState;
use App\Enums\Skill;
use App\Exceptions\ExamException;
use App\Jobs\ProcessSpeakingSubmission;
use App\Jobs\ProcessWritingSubmission;
use App\Models\Attempt;
use App\Models\AttemptExercise;
use App\Models\ExamLog;
use App\Models\ModellTest;
use App\Models\SpeakingSubmission;
use App\Models\User;
use App\Models\WritingSubmission;
use Illuminate\Support\Facades\DB;

/**
 * ExamService — moteur d'examen (autorité serveur).
 *
 * Responsable de : création/reprise d'une tentative, navigation séquentielle
 * imposée, autorisation d'accès aux tâches, finalisation des tâches et de la
 * tentative. Toute la logique de temps est déléguée à TimerService.
 */
class ExamService
{
    public function __construct(
        private TimerService $timer,
        private ScoringService $scoring,
        private FormService $forms,
    ) {}

    /** Démarre (ou reprend) une tentative sur un Modelltest. */
    public function startAttempt(User $user, ModellTest $test, string $mode = 'exam'): Attempt
    {
        $existing = $user->attempts()
            ->where('modell_test_id', $test->id)
            ->where('status', AttemptStatus::InProgress->value)
            ->latest('id')
            ->first();

        if ($existing) {
            return $this->restore($existing);
        }

        return DB::transaction(function () use ($user, $test, $mode) {
            $attempt = Attempt::create([
                'user_id' => $user->id,
                'modell_test_id' => $test->id,
                'mode' => $mode,
                'status' => AttemptStatus::InProgress,
                'started_at' => now(),
            ]);

            $position = 0;
            foreach (Skill::sequence() as $skill) {
                $section = $test->sections()->where('skill', $skill->value)->first();
                if (! $section) {
                    continue;
                }
                foreach ($section->exercises()->get() as $exercise) {
                    AttemptExercise::create([
                        'attempt_id' => $attempt->id,
                        'exercise_id' => $exercise->id,
                        'section_id' => $section->id,
                        'position' => $position++,
                        'status' => ExerciseState::Locked,
                    ]);
                }
            }

            // Forme de questions dynamique : contenu neuf à chaque
            // tentative, format constant, difficulté calibrée C1/C1+.
            $this->forms->buildForAttempt($attempt);

            $this->activateNext($attempt);
            $this->log($attempt, 'attempt_started', ['mode' => $mode]);

            return $attempt->refresh();
        });
    }

    /** Reprise après coupure : synchronise l'état selon l'heure serveur. */
    public function restore(Attempt $attempt): Attempt
    {
        $attempt->loadMissing('attemptExercises.exercise');

        if (! $attempt->isInProgress()) {
            return $attempt;
        }

        foreach ($attempt->attemptExercises as $ae) {
            if ($ae->state() === ExerciseState::Started) {
                $this->timer->sync($ae);
            }
        }

        $current = $attempt->fresh('attemptExercises.exercise')->currentExercise();
        if ($current && $current->state()->isFinal()) {
            $this->activateNext($attempt->refresh());
        }

        return $attempt->refresh();
    }

    /** Active la prochaine tâche verrouillée (ou termine la tentative). */
    public function activateNext(Attempt $attempt): ?AttemptExercise
    {
        $next = $attempt->attemptExercises()
            ->where('status', ExerciseState::Locked->value)
            ->orderBy('position')
            ->first();

        if (! $next) {
            $this->finishAttempt($attempt);

            return null;
        }

        $next->update(['status' => ExerciseState::Available]);
        $attempt->update([
            'current_section_id' => $next->section_id,
            'current_exercise_id' => $next->exercise_id,
        ]);

        return $next->refresh();
    }

    /**
     * Démarre le chronomètre de la tâche (interdiction de toute autre tâche).
     *
     * @throws ExamException si l'exercice n'est pas la tâche courante autorisée.
     */
    public function startExercise(Attempt $attempt, int $exerciseId): AttemptExercise
    {
        if (! $attempt->isInProgress()) {
            throw new ExamException('Cette tentative est terminée.', 409);
        }

        $ae = $attempt->attemptExercises()->where('exercise_id', $exerciseId)->first();

        if (! $ae) {
            throw ExamException::unauthorized('Cet exercice n\'appartient pas à cette tentative.');
        }

        // Navigation séquentielle : seule la tâche courante est autorisée.
        $current = $attempt->currentExercise();

        if (! $current || $current->state()->isFinal()) {
            throw ExamException::unauthorized('Aucune tâche courante disponible dans cette tentative.');
        }

        if ($current->id !== $ae->id) {
            throw ExamException::unauthorized(
                'Navigation séquentielle imposée : cet exercice n\'est pas accessible.'
            );
        }

        if ($ae->state()->isFinal()) {
            return $ae->refresh();
        }

        if ($ae->state() === ExerciseState::Locked) {
            $ae->update(['status' => ExerciseState::Available]);
        }

        return $this->timer->start($ae);
    }

    /**
     * Termine la tâche courante (fin manuelle "WEITER" ou expiration),
     * verrouille définitivement les réponses, corrige l'objectif et avance.
     */
    public function completeExercise(Attempt $attempt, int $exerciseId, bool $expired = false): ?AttemptExercise
    {
        $ae = $attempt->attemptExercises()->where('exercise_id', $exerciseId)->first();

        if (! $ae) {
            throw ExamException::unauthorized('Cet exercice n\'appartient pas à cette tentative.');
        }

        if ($ae->state()->isFinal()) {
            return $attempt->refresh()->currentExercise();
        }

        $this->timer->stop($ae, $expired);
        $this->scoring->scoreObjective($attempt, $ae->refresh());
        $this->finalizeProductive($attempt, $ae);
        $this->activateNext($attempt);

        return $attempt->refresh()->currentExercise();
    }

    /** Clôture la tentative : agrège les résultats et fige l'état. */
    public function finishAttempt(Attempt $attempt): void
    {
        if (! $attempt->isInProgress()) {
            return;
        }

        $this->scoring->computeResults($attempt);

        $attempt->update([
            'status' => AttemptStatus::Completed,
            'completed_at' => now(),
            'duration_seconds' => $attempt->started_at
                ? (int) $attempt->started_at->diffInSeconds(now())
                : null,
            'current_section_id' => null,
            'current_exercise_id' => null,
        ]);

        $this->log($attempt, 'attempt_completed', ['score' => (float) $attempt->score]);
    }

    /** Progression globale d'une tentative. */
    public function progress(Attempt $attempt): array
    {
        $attempt->loadMissing('attemptExercises');
        $total = $attempt->attemptExercises->count();
        $done = $attempt->attemptExercises->filter(fn ($ae) => $ae->state()->isFinal())->count();

        return [
            'total' => $total,
            'completed' => $done,
            'remaining' => $total - $done,
            'percent' => $total > 0 ? round(($done / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * Clôture les productions Schreiben/Sprechen à la fin de la tâche :
     * la soumission existante est figée (même à l'expiration) et l'analyse
     * IA est mise en file si elle est activée. Aucune réponse n'est perdue.
     */
    public function finalizeProductive(Attempt $attempt, AttemptExercise $ae): void
    {
        $skill = $ae->exercise->skill;

        if ($skill === Skill::Schreiben) {
            $submission = WritingSubmission::query()
                ->where('attempt_id', $attempt->id)
                ->where('attempt_exercise_id', $ae->id)
                ->latest('id')
                ->first();

            if ($submission && ! $submission->locked) {
                $submission->update([
                    'submitted_at' => $submission->submitted_at ?? now(),
                    'locked' => true,
                ]);
            }

            if ($submission && $this->aiAutoEvaluation()) {
                ProcessWritingSubmission::dispatch($submission->id);
            }
        }

        if ($skill === Skill::Sprechen) {
            $submission = SpeakingSubmission::query()
                ->where('attempt_id', $attempt->id)
                ->where('attempt_exercise_id', $ae->id)
                ->latest('id')
                ->first();

            if ($submission && ! $submission->locked) {
                $submission->update([
                    'submitted_at' => $submission->submitted_at ?? now(),
                    'locked' => true,
                ]);
            }

            if ($submission && $this->aiAutoEvaluation()) {
                ProcessSpeakingSubmission::dispatch($submission->id);
            }
        }
    }

    private function aiAutoEvaluation(): bool
    {
        return (bool) config('testdaf.ai.enabled')
            && (bool) config('testdaf.ai.auto_evaluation');
    }

    private function log(Attempt $attempt, string $event, array $payload = []): void
    {
        ExamLog::create([
            'user_id' => $attempt->user_id,
            'attempt_id' => $attempt->id,
            'event' => $event,
            'payload' => $payload,
            'ip' => request()->ip(),
        ]);
    }
}
