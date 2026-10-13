<?php

namespace App\Services\Exam;

use App\Enums\AttemptStatus;
use App\Enums\ExerciseState;
use App\Exceptions\ExamException;
use App\Models\Attempt;
use App\Models\AttemptExercise;
use App\Models\ExamLog;
use App\Models\ModellTest;
use App\Models\User;
use App\Services\Statistics\StatisticsService;
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
        private QuestionTimerService $questions,
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
            // Tours précédents : le brassage des options (graine = tentative +
            // question) suffit à faire varier les lettres. Le compteur est
            // conservé pour que les lettres déjà affichées lors des tentatives
            // antérieures restent recalculables à l'identique.
            $previousRounds = $user->attempts()
                ->where('modell_test_id', $test->id)
                ->count();

            $attempt = Attempt::create([
                'user_id' => $user->id,
                'modell_test_id' => $test->id,
                'mode' => $mode,
                'status' => AttemptStatus::InProgress,
                'started_at' => now(),
                'label_rotation' => $previousRounds,
            ]);

            // Ordre des parties = ordre des sections du test (position),
            // ce qui permet à un test de positionnement de définir sa propre
            // séquence (ex. Hören audio avant Lesen texte). Les Modelltests
            // standards conservent l'ordre Lesen → Hören → Schreiben → Sprechen.
            $position = 0;
            foreach ($test->sections()->orderBy('position')->get() as $section) {
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

        // Auto-réparation : si la tâche courante n'est pas en cours
        // (pointeur obsolète sur une tâche clôturée, tâche verrouillée dont
        // l'activation a manqué, ou toutes clôturées), on active la suivante.
        // activateNext finalise la tentative si plus aucune tâche ne reste.
        $current = $attempt->fresh('attemptExercises.exercise')->currentExercise();
        $inProgressState = $current && ! $current->state()->isFinal() && $current->state() !== ExerciseState::Locked;

        if (! $inProgressState) {
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

        $started = $this->timer->start($ae);

        // Chronomètre par question : question 0, temps propre initial.
        $this->questions->reset($started->fresh());

        return $started->fresh();
    }

    /**
     * Avance à la question suivante (ou finalise la tâche).
     *
     * Utilisé par le bouton SUIVANT et l'expiration du timer de question.
     * Le serveur vérifie que la question courante est répondu/expirée.
     * Retourne 'advanced' (nouvelle question), 'resync' (le serveur était
     * déjà passé à une question plus avancée que celle affichée),
     * 'exercise' (tâche suivante) ou 'finished' (fin du Modelltest).
     */
    public function advanceQuestion(Attempt $attempt, AttemptExercise $attemptExercise, ?int $fromIndex = null): array
    {
        abort_unless($attemptExercise->attempt_id === $attempt->id, 404);

        // Synchronisation temps serveur (peut expirer/clore la tâche).
        $attemptExercise = $this->timer->sync($attemptExercise->fresh());

        if ($attemptExercise->state()->isFinal()) {
            return $this->afterExerciseClosed($attempt, $attemptExercise);
        }

        if ($attemptExercise->isProductiveExercise()) {
            return ['outcome' => 'exercise'];
        }

        // Avance forcée si le temps de la question courante est écoulé.
        // Si sync() a déjà fait avancer l'index, l'action SUIVANT du
        // candidat est consommée par cet avancement (pas de double saut).
        $syncAdvanced = $this->questions->sync($attemptExercise);
        $attemptExercise = $attemptExercise->fresh();

        if ($attemptExercise->state()->isFinal()) {
            return $this->afterExerciseClosed($attempt, $attemptExercise);
        }

        // Dernière question dépassée : clôture immédiate de la tâche.
        $closed = $this->closeIfLastQuestionExpired($attempt, $attemptExercise);
        if ($closed !== null) {
            return $closed;
        }

        if ($syncAdvanced) {
            return [
                'outcome' => 'advanced',
                'question' => $this->questionPayload($attemptExercise, $attempt),
                'timer' => $this->timer->display($attemptExercise),
                'question_timer' => $this->questions->display($attemptExercise),
            ];
        }

        // Idempotence : si le client affiche encore une question que le
        // serveur a déjà dépassée (requête SUIVANT en retard), on n'avance
        // PLUS — on renvoie l'état courant pour que le client resynchronise
        // (pas de double saut de question).
        if ($fromIndex !== null && $attemptExercise->questionIndex() > $fromIndex) {
            return [
                'outcome' => 'resync',
                'question' => $this->questionPayload($attemptExercise, $attempt),
                'timer' => $this->timer->display($attemptExercise),
                'question_timer' => $this->questions->display($attemptExercise),
            ];
        }

        // Anti-passage accidentel : détecte si la question quittée est sans
        // réponse (vide = 0 point). Le serveur autorise le passage (un vrai
        // examen note 0 les blancs) mais le signale : le client affiche un
        // avertissement et journalise question_skipped pour l'audit.
        $leavingQuestion = $attemptExercise->currentQuestion();
        $skipped = $leavingQuestion
            && ! $attempt->answers()->where('question_id', $leavingQuestion->id)->exists()
            && ! $attemptExercise->currentQuestionExpired();

        $advanced = $this->questions->advance($attemptExercise);

        if (! $advanced) {
            if ($skipped) {
                $this->log($attempt, 'question_skipped', [
                    'exercise_id' => $attemptExercise->exercise_id,
                    'question_id' => $leavingQuestion->id,
                ]);
            }
            $this->completeExercise($attempt, $attemptExercise->exercise_id);

            return $this->afterExerciseClosed($attempt, $attemptExercise->fresh()) + ['skipped' => (bool) $skipped];
        }

        if ($skipped) {
            $this->log($attempt, 'question_skipped', [
                'exercise_id' => $attemptExercise->exercise_id,
                'question_id' => $leavingQuestion->id,
            ]);
        }

        $attemptExercise = $attemptExercise->fresh();

        return [
            'outcome' => 'advanced',
            'skipped' => (bool) $skipped,
            'question' => $this->questionPayload($attemptExercise, $attempt),
            'timer' => $this->timer->display($attemptExercise),
            'question_timer' => $this->questions->display($attemptExercise),
        ];
    }

    /**
     * Si la DERNIÈRE question est chronométrée et que son temps est écoulé,
     * la tâche est close (toutes les questions ont été consommées).
     * Retourne le résultat JSON de fin de tâche, ou null si rien à faire.
     */
    public function closeIfLastQuestionExpired(Attempt $attempt, AttemptExercise $ae): ?array
    {
        if ($ae->state()->isFinal() || $ae->isProductiveExercise()) {
            return null;
        }

        $count = $ae->formQuestions()->count();

        if ($ae->questionIndex() < $count - 1 || ! $ae->currentQuestionExpired()) {
            return null;
        }

        $this->completeExercise($attempt, $ae->exercise_id);

        return $this->afterExerciseClosed($attempt, $ae->fresh());
    }

    /** Résultat JSON uniforme après fermeture d'une tâche (complete/expire). */
    private function afterExerciseClosed(Attempt $attempt, AttemptExercise $attemptExercise): array
    {
        $next = $attempt->fresh()->currentExercise();

        if ($next === null) {
            $this->finishAttempt($attempt->fresh());

            return [
                'outcome' => 'finished',
                'redirect' => route('results.show', $attempt),
            ];
        }

        return [
            'outcome' => 'exercise',
            'exercise_id' => $next->exercise_id,
            'redirect' => route('exam.show', $attempt),
        ];
    }

    /**
     * Options affichées : contenu brassé par graine (positions différentes par
     * tentative/question), LETTRES toujours dans l'ordre du curseur (1re option
     * affichée = 'a', suivante = 'b', …).
     *
     * La lettre d'une option est celle de sa position à l'écran : la bonne
     * réponse porte donc la lettre de l'endroit où son contenu a atterri. Elle
     * varie d'une question à l'autre et d'une tentative à l'autre (graine
     * distincte), sans jamais désordonner l'alphabet. Correction et résultats
     * utilisent le même calcul — déterministe, stable pendant la tentative.
     */
    public function displayOptions(\App\Models\Question $question, string $seed, int $rotation = 0): array
    {
        return array_map(
            fn ($row) => ['id' => $row['id'], 'label' => $row['label'], 'text' => $row['text']],
            $this->computeDisplayed($question, $seed, $rotation)
        );
    }

    /**
     * Rotation effective des lettres pour une tentative (0 = libellés
     * d'origine). Le compteur de tours est persisté sur la tentative :
     * recalculable à tout moment (correction, résultats), sans dérive.
     */
    public function effectiveRotation(Attempt $attempt, \App\Models\Question $question): int
    {
        $question->loadMissing('answerOptions');
        $count = max(1, $question->answerOptions->count());

        return ((int) ($attempt->label_rotation ?? 0)) % $count;
    }

    /** Options affichées pour une tentative (graine + rotation du tour). */
    public function displayOptionsForAttempt(\App\Models\Question $question, Attempt $attempt): array
    {
        return array_map(
            fn ($row) => ['id' => $row['id'], 'label' => $row['label'], 'text' => $row['text']],
            $this->displayedForAttempt($question, $attempt)
        );
    }

    /**
     * Affichage d'une question pour une tentative : déterministe, identique
     * dans le JSON du chronomètre, la vue servie et la correction.
     */
    private function displayedForAttempt(\App\Models\Question $question, Attempt $attempt): array
    {
        return $this->computeDisplayed(
            $question,
            $this->optionSeed($attempt, $question),
            $this->effectiveRotation($attempt, $question),
            $this->balancedSlot($question, $attempt)
        );
    }

    /**
     * Emplacement cible (0..n-1) de la bonne réponse pour la tentative :
     * tirage équilibré et mélangé sur chaque groupe de QCM à une seule bonne
     * réponse (ex. 20 questions × 4 options → 5× chaque lettre, en désordre).
     * Null si la question n'est pas concernée (vrai/faux inclus).
     */
    private function balancedSlot(\App\Models\Question $question, Attempt $attempt): ?int
    {
        $question->loadMissing('answerOptions');

        $options = $question->answerOptions;
        $n = count($options);

        if ($n < 2 || $options->filter(fn ($o) => (bool) $o->is_correct)->count() !== 1) {
            return null;
        }

        return $this->balancedSlotMap($attempt)[(int) $question->getKey()] ?? null;
    }

    /**
     * Cartographie question → emplacement équilibré pour toute la tentative.
     * Recalculable à tout moment (affichage, correction, résultats) : le sac
     * de lettres est brassé avec la même graine, donc stable entre appels.
     */
    private function balancedSlotMap(Attempt $attempt): array
    {
        static $cache = [];

        $attemptId = (int) $attempt->id;
        if (isset($cache[$attemptId])) {
            return $cache[$attemptId];
        }

        $map = [];

        $attempt->loadMissing('attemptExercises.exercise');
        foreach ($attempt->attemptExercises as $ae) {
            $exercise = $ae->exercise;
            if (! $exercise) {
                continue;
            }

            $questions = $exercise->questions()
                ->with('answerOptions')
                ->orderBy('position')
                ->get();

            // Regroupe par nombre d'options pour équilibrer séparément chaque
            // groupe (les 20 QCM à 4 options du Défi → 5× a, 5× b, 5× c, 5× d).
            $groups = [];
            foreach ($questions as $q) {
                $count = $q->answerOptions->count();
                if ($count > 1
                    && $q->answerOptions->filter(fn ($o) => (bool) $o->is_correct)->count() === 1) {
                    $groups[$count][] = $q;
                }
            }

            foreach ($groups as $n => $qs) {
                $k = count($qs);
                $pool = [];
                for ($s = 0; $s < $n; $s++) {
                    $pool = array_merge(
                        $pool,
                        array_fill(0, intdiv($k, $n) + ($s < $k % $n ? 1 : 0), $s)
                    );
                }

                // Brassage déterministe du sac de lettres (aucun cycle visible).
                $seeded = [];
                foreach ($pool as $i => $s) {
                    $seeded[] = [md5("attempt:{$attemptId}::{$ae->exercise_id}:n:{$n}:i:{$i}"), $s, $i];
                }
                usort($seeded, fn ($a, $b) => strcmp($a[0], $b[0]) ?: ($a[2] <=> $b[2]));

                foreach ($qs as $idx => $q) {
                    $map[(int) $q->getKey()] = $seeded[$idx][1];
                }
            }
        }

        return $cache[$attemptId] = $map;
    }

    /**
     * Calcul partagé (affichage + correction + résultats) : chaque ligne
     * contient id, libellé affiché, texte et flag correct d'origine.
     *
     * $baseSlot : emplacement cible (0..n-1) de la bonne réponse pour une
     * tentative (tirage équilibré). Null = repli sur la position de la question.
     */
    private function computeDisplayed(\App\Models\Question $question, string $seed, int $rotation, ?int $baseSlot = null): array
    {
        $question->loadMissing('answerOptions');

        $options = $question->answerOptions;

        // Jeu de lettres trié (insensible à toute mutation préalable).
        $base = $options
            ->map(fn ($o) => (string) $o->label)
            ->sort()
            ->values()->all();

        $n = max(1, count($base));

        $correct = $options->filter(fn ($o) => (bool) $o->is_correct)->values();

        // QCM à une seule bonne réponse : on répartit la bonne réponse sur
        // toutes les lettres, mais dans un ORDRE MÉLANGÉ (pas de cycle visible
        // a,b,c,d) : l'emplacement vient d'un tirage équilibré par tentative
        // (5× chaque lettre pour 20 questions), décalé de la rotation du tour.
        // Les autres options restent brassées par graine.
        if ($n > 1 && $correct->count() === 1) {
            $good = $correct->first();
            $others = $options
                ->reject(fn ($o) => $o->getKey() === $good->getKey())
                ->sortBy(fn ($o) => md5($seed.':'.$o->getKey()))
                ->values();

            $slot = (($baseSlot ?? (int) $question->position) + (int) $rotation) % $n;

            $ordered = [];
            for ($i = 0; $i < $n; $i++) {
                $ordered[] = $i === $slot ? $good : $others->shift();
            }
        } else {
            // Autres types (plusieurs bonnes réponses, etc.) : ordre d'affichage
            // uniquement brassé par graine.
            $ordered = $options
                ->sortBy(fn ($o) => md5($seed.':'.$o->getKey()))
                ->values()
                ->all();
        }

        // Lettre = position à l'écran : l'option affichée en 1er porte
        // toujours la lettre $base[0], la suivante $base[1], etc.
        $out = [];
        foreach ($ordered as $i => $option) {
            $out[] = [
                'id' => $option->getKey(),
                'label' => $base[$i % $n],
                'text' => $option->text,
                'is_correct' => (bool) $option->is_correct,
            ];
        }

        return $out;
    }

    /** Libellés affichés des bonnes réponses (correction, page résultats). */
    public function displayedCorrectLabels(\App\Models\Question $question, Attempt $attempt): array
    {
        $out = [];

        foreach ($this->displayedForAttempt($question, $attempt) as $opt) {
            if ($opt['is_correct']) {
                $out[] = (string) $opt['label'];
            }
        }

        return $out;
    }

    /** Graine de brassage d'une question pour une tentative donnée. */
    public static function attemptQuestionSeed(int $attemptId, int $questionId): string
    {
        return "attempt:{$attemptId}:q:{$questionId}";
    }

    /** Graine de brassage d'une question pour une tentative donnée. */
    public function optionSeed(Attempt $attempt, \App\Models\Question $question): string
    {
        return self::attemptQuestionSeed((int) $attempt->id, (int) $question->getKey());
    }

    /**
     * Applique le brassage + rotation aux options chargées (vue d'examen
     * non chronométré) : identique au JSON, partout pendant la tentative.
     */
    public function applyOptionShuffle(iterable $questions, Attempt $attempt): void
    {
        foreach ($questions as $question) {
            $question->loadMissing('answerOptions');
            $displayed = $this->displayedForAttempt($question, $attempt);
            $byId = collect($displayed)->keyBy('id');

            $question->setRelation(
                'answerOptions',
                $question->answerOptions
                    ->sortBy(fn ($o) => array_search($o->getKey(), array_keys($byId->all())))
                    ->map(fn ($o) => tap($o->replicate(), function ($copy) use ($byId, $o) {
                        $copy->label = $byId[$o->getKey()]['label'];
                    }))
                    ->values()
            );
        }
    }

    /** Question courante sérialisée pour le frontend (options brassées). */
    public function questionPayload(AttemptExercise $attemptExercise, ?Attempt $attempt = null): ?array
    {
        $question = $attemptExercise->currentQuestion();

        if (! $question) {
            return null;
        }

        $options = $attempt !== null
            ? $this->displayOptionsForAttempt($question, $attempt)
            : $this->displayOptions(
                $question,
                self::attemptQuestionSeed((int) $attemptExercise->attempt_id, (int) $question->getKey())
            );

        return [
            'id' => $question->id,
            'type' => $question->type,
            'prompt' => $question->prompt,
            'points' => (float) $question->points,
            'time_limit_seconds' => $question->time_limit_seconds !== null
                ? (int) $question->time_limit_seconds
                : null,
            'data' => $question->data,
            'answer_options' => $options,
        ];
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

        // Les stats dashboard étaient en cache : on les invalide pour que
        // la progression / l'évolution reflètent immédiatement ce test.
        StatisticsService::flushUserCache((int) $attempt->user_id);

        // Recommandations : le dashboard les lit, il faut les générer ici.
        // Isolé en try/catch : une reco en échec ne doit jamais bloquer
        // la clôture d'une tentative.
        try {
            app(\App\Services\Statistics\RecommendationService::class)
                ->generateFor($attempt->user ?? $attempt->load('user')->user);
        } catch (\Throwable $e) {
            report($e);
        }

        // Espace Élite : 2 scores parfaits consécutifs → unlock + notif.
        // Les tests générés (draft) ne redébloquent jamais (voir service).
        try {
            app(\App\Services\Challenge\ChallengeService::class)
                ->checkAndUnlock($attempt);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->log($attempt, 'attempt_completed', ['score' => (float) $attempt->score]);
    }

    /** Progression globale d'une tentative (2 COUNT, pas de full load). */
    public function progress(Attempt $attempt): array
    {
        if ($attempt->relationLoaded('attemptExercises')) {
            $total = $attempt->attemptExercises->count();
            $done = $attempt->attemptExercises->filter(fn ($ae) => $ae->state()->isFinal())->count();
        } else {
            $total = $attempt->attemptExercises()->count();
            $done = $attempt->attemptExercises()
                ->whereIn('status', ['completed', 'expired'])
                ->count();
        }

        return [
            'total' => $total,
            'completed' => $done,
            'remaining' => $total - $done,
            'percent' => $total > 0 ? round(($done / $total) * 100, 1) : 0.0,
        ];
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
