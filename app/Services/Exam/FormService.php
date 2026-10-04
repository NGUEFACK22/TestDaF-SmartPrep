<?php

namespace App\Services\Exam;

use App\Enums\AttemptStatus;
use App\Models\Attempt;
use App\Models\AttemptExercise;
use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * FormService — génération de la forme de questions par tentative.
 *
 * À chaque démarrage de tentative, un sous-ensemble du pool de questions
 * de chaque exercice est sélectionné et persisté
 * (attempt_exercises.question_form) :
 *
 *  - format constant : le nombre de questions affichées par tâche reste
 *    inchangé (défini par Exercise::content['questions_per_form']) ;
 *  - difficulté : la sélection est pondérée vers les items C1/C1+
 *    (niveau légèrement supérieur au TestDaF officiel) ;
 *  - anti-répétition : les questions des deux dernières tentatives
 *    terminées du candidat sur la même tâche sont exclues dès que le
 *    pool le permet.
 *
 * question_form = null ⇒ comportement legacy : toutes les questions de
 * l'exercice (exercices de production, démos officielles, anciennes
 * tentatives).
 */
class FormService
{
    /** Poids de sélection par difficulté (supérieur = plus fréquent). */
    public const WEIGHTS = [
        'B2' => 1.0,
        'C1' => 3.0,
        'C1+' => 4.0,
    ];

    /** Poids neutre pour les questions sans étiquette. */
    public const DEFAULT_WEIGHT = 2.0;

    /** Nombre de tentatives terminées prises en compte (anti-répétition). */
    public const RECENT_ATTEMPTS = 2;

    /**
     * Génère et persiste la forme pour toutes les tâches de la tentative.
     * À appeler au démarrage de la tentative (transaction incluse).
     */
    public function buildForAttempt(Attempt $attempt): void
    {
        $attempt->loadMissing('attemptExercises.exercise');

        foreach ($attempt->attemptExercises as $ae) {
            $pool = $ae->exercise->questions()
                ->whereNotIn('type', ['essay', 'audio_response'])
                ->orderBy('position')
                ->get();

            $count = (int) ($ae->exercise->content['questions_per_form'] ?? 0);

            // Pas de forme : pool trop petit ou tâche non variabilisable
            // (production, démo officielle, exercice sans paramètre).
            if ($count <= 0 || $count >= $pool->count()) {
                continue;
            }

            $excluded = $this->recentlyUsed($attempt->user_id, $ae->exercise_id);
            $ae->update(['question_form' => $this->select($pool, $count, $excluded)]);
        }
    }

    /**
     * Sélectionne $count questions sans remise, pondérée par difficulté
     * et excluant les IDs donnés autant que possible.
     *
     * @param  Collection<int, Question>  $pool  (clefs = ids, ordre du pool)
     * @return int[] IDs sélectionnés, dans l'ordre du pool
     */
    public function select(Collection $pool, int $count, array $excluded = [], ?int $seed = null): array
    {
        $count = max(0, min($count, $pool->count()));

        if ($count === 0) {
            return [];
        }

        // Indexer le pool par l'ID de question : indispensable pour que
        // `only` / `except` fonctionnent (le résultat de `get()` est indexé
        // par position, pas par clé primaire).
        $pool = $pool->keyBy(fn (Question $q) => (int) $q->getKey());

        $excludedSet = array_map('intval', $excluded);
        $available = $pool->filter(fn (Question $q) => ! in_array((int) $q->id, $excludedSet, true));

        // Pool trop frais : on complète avec les questions exclues
        // (on garde toujours le format, même au prix d'une répétition).
        if ($available->count() < $count) {
            $fill = $pool
                ->filter(fn (Question $q) => in_array((int) $q->id, $excludedSet, true))
                ->take($count - $available->count());
            $available = $available->merge($fill);
        }

        $selected = $this->weightedPick($available, $count, $seed);
        $selectedIds = array_map(fn (Question $q) => (int) $q->getKey(), $selected);

        // Réordonner selon l'ordre du pool (note : `only()` ré-indexe à 0
        // dans cette version de Laravel, d'où le `filter` explicite).
        return $pool
            ->filter(fn (Question $q) => in_array((int) $q->getKey(), $selectedIds, true))
            ->map(fn (Question $q) => (int) $q->getKey())
            ->values()
            ->all();
    }

    /**
     * IDs de questions déjà utilisés par ce candidat sur cette tâche
     * (deux dernières tentatives terminées).
     */
    public function recentlyUsed(int $userId, int $exerciseId): array
    {
        $attemptIds = Attempt::where('user_id', $userId)
            ->where('status', AttemptStatus::Completed->value)
            ->latest('id')
            ->limit(self::RECENT_ATTEMPTS)
            ->pluck('id');

        if ($attemptIds->isEmpty()) {
            return [];
        }

        return AttemptExercise::whereIn('attempt_id', $attemptIds)
            ->where('exercise_id', $exerciseId)
            ->whereNotNull('question_form')
            ->get(['question_form'])
            ->flatMap(fn (AttemptExercise $ae) => array_map('intval', (array) $ae->question_form))
            ->unique()
            ->all();
    }

    // ------------------------------------------------------------- interne

    private function weightedPick(Collection $items, int $n, ?int $seed): array
    {
        if ($seed !== null) {
            mt_srand($seed);
        }

        $pool = $items;
        $picked = [];

        for ($i = 0; $i < $n; $i++) {
            if ($pool->isEmpty()) {
                break;
            }

            $total = 0.0;
            foreach ($pool as $item) {
                $total += $this->weight($item);
            }

            $roll = mt_rand(0, mt_getrandmax()) / mt_getrandmax() * $total;
            $acc = 0.0;
            $chosenKey = $pool->keys()->last();
            $chosen = $pool->last();

            foreach ($pool as $key => $item) {
                $acc += $this->weight($item);
                if ($roll < $acc) {
                    $chosenKey = $key;
                    $chosen = $item;
                    break;
                }
            }

            $picked[] = $chosen;
            $pool = $pool->except($chosenKey);
        }

        return $picked;
    }

    private function weight(Question $q): float
    {
        return self::WEIGHTS[$q->difficulty] ?? self::DEFAULT_WEIGHT;
    }
}