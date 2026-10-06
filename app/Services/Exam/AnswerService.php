<?php

namespace App\Services\Exam;

use App\Exceptions\ExamException;
use App\Models\Attempt;
use App\Models\AttemptExercise;
use App\Models\ExamLog;
use App\Models\Question;
use App\Models\UserAnswer;
use Illuminate\Support\Facades\DB;

/**
 * AnswerService — enregistrement / sauvegarde automatique des réponses.
 *
 * Refuse systématiquement toute écriture lorsque la tâche est verrouillée,
 * terminée, expirée ou que le délai serveur est dépassé.
 */
class AnswerService
{
    public function __construct(private TimerService $timer) {}

    /**
     * Enregistre (ou met à jour) la réponse d'un candidat.
     *
     * @throws ExamException si la tâche n'accepte plus de réponses.
     */
    public function save(
        Attempt $attempt,
        AttemptExercise $attemptExercise,
        Question $question,
        mixed $answer,
        bool $autosave = false
    ): UserAnswer {
        // 1) Synchronise l'état avec l'heure serveur (anti-contournement).
        $this->timer->sync($attemptExercise);

        // 2) Contrôles d'autorisation (serveur = autorité).
        if ($question->exercise_id !== $attemptExercise->exercise_id) {
            throw new ExamException('La question n\'appartient pas à cet exercice.', 422);
        }

        // 2b) La question doit faire partie de la forme de CETTE tentative
        //     (pas de réponse sur des questions du pool non tirées).
        $formIds = $attemptExercise->formIds();
        if ($formIds !== null && ! in_array((int) $question->id, $formIds, true)) {
            throw new ExamException('Cette question ne fait pas partie de la tâche en cours.', 422);
        }

        // 2c) La question courante seule accepte une réponse (verrouillage
        //     séquentiel + timer par question, autorité serveur).
        $this->timer->sync($attemptExercise->refresh());

        if (! $attemptExercise->acceptsAnswerFor($question)) {
            // Tolérance aux doublons de sauvegarde (autosave + bouton) : si la
            // réponse existe déjà, on la retourne au lieu d'une erreur 423 —
            // un simple doublon ne doit jamais être interprété par le client
            // comme un verrouillage (sinon il "avançait" par erreur).
            $existing = UserAnswer::query()
                ->where('attempt_id', $attempt->id)
                ->where('question_id', $question->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            throw ExamException::locked(
                'Cette question est verrouillée : répondez à la question en cours dans le temps imparti.',
                'question_locked'
            );
        }
        if ($attemptExercise->state()->isFinal()) {
            throw ExamException::locked('Cet exercice est terminé : les réponses ne peuvent plus être modifiées.');
        }

        // Tolérance réseau : un autosave en vol (≤ grace_seconds après
        // l'expiration) est accepté, un envoi manuel est refusé.
        // Le serveur reste l'autorité : au-delà de la grâce, tout est verrouillé.
        if ($attemptExercise->hasExpired()) {
            if (! ($autosave && $this->withinGrace($attemptExercise))) {
                throw ExamException::expired();
            }
        }

        if (! $attemptExercise->acceptsAnswers()) {
            // Même tolérance pour acceptsAnswers() (verrou + délai).
            if (! ($autosave && $this->withinGrace($attemptExercise))) {
                throw ExamException::locked();
            }
        }

        return DB::transaction(function () use ($attempt, $attemptExercise, $question, $answer, $autosave) {
            $userAnswer = UserAnswer::updateOrCreate(
                [
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                ],
                [
                    'user_id' => $attempt->user_id,
                    'attempt_exercise_id' => $attemptExercise->id,
                    'answer' => is_string($answer) ? $answer : json_encode($answer),
                    'answered_at' => now(),
                    'correction_status' => 'pending',
                ]
            );

            if (! $autosave) {
                ExamLog::create([
                    'user_id' => $attempt->user_id,
                    'attempt_id' => $attempt->id,
                    'event' => 'answer_saved',
                    'payload' => ['question_id' => $question->id],
                    'ip' => request()->ip(),
                ]);
            }

            return $userAnswer;
        });
    }

    /** Sauvegarde en lot (autosave d'un exercice complet). */
    public function saveMany(Attempt $attempt, AttemptExercise $attemptExercise, array $answers): array
    {
        // Pré-charge les questions en une requête (évite N+1 sur autosave).
        $questions = Question::whereIn('id', array_keys($answers))->get()->keyBy('id');

        $saved = [];
        foreach ($answers as $questionId => $value) {
            $question = $questions->get($questionId);
            if (! $question) {
                continue;
            }
            $saved[] = $this->save($attempt, $attemptExercise, $question, $value, autosave: true);
        }

        return $saved;
    }

    /** Sommes-nous dans la fenêtre de grâce réseau après expiration ? */
    private function withinGrace(AttemptExercise $attemptExercise): bool
    {
        $expiresAt = $attemptExercise->expires_at;

        if (! $expiresAt) {
            return false;
        }

        $grace = (int) config('testdaf.exam.grace_seconds', 2);

        return now()->lessThanOrEqualTo($expiresAt->copy()->addSeconds($grace));
    }
}
