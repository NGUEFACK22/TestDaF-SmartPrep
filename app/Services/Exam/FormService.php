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
        // `except` fonctionne (le résultat de `get()` est indexé par
        // position, pas par clé primaire). `toBase()` est CRITIQUE :
        // Eloquent\Collection::except() ré-indexe en array_values()
        // (clés 0..N-1), ce qui détruit les clés ID et permet de
        // re-sélectionner la même question → forme raccourcie.
        $pool = $pool->keyBy(fn (Question $q) => (int) $q->getKey())->toBase();

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

    /**
     * Tirage stratifié + pondéré : garantit une équivalence de difficulté
     * entre tentatives (même format, même mix B2/C1/C1+ à ±1 près).
     * Sans stratification, le pur pondéré pouvait tirer 100% C1+ une fois
     * et 100% C1 la fois suivante → notes non comparables.
     */
    private function weightedPick(Collection $items, int $n, ?int $seed): array
    {
        if ($seed !== null) {
            mt_srand($seed);
        }

        if ($items->count() <= $n) {
            return $items->values()->all();
        }

        // Groupes par difficulté (ordre stable pour la reproductibilité).
        $byLevel = [];
        foreach ($items as $key => $item) {
            $byLevel[$item->difficulty ?? 'unknown'][$key] = $item;
        }

        // Quotas cibles : proportionnels aux poids, au moins 1 par groupe
        // présent si le format le permet (équivalence garantie).
        $quotas = $this->stratifiedQuotas($byLevel, $n);

        $picked = [];
        $remaining = $items;

        foreach ($quotas as $level => $quota) {
            $group = collect($byLevel[$level] ?? []);
            // Exclut ce qui a déjà été pris (groupes disjoints en pratique).
            $group = $group->only($remaining->keys()->all());
            $take = min($quota, $group->count());

            for ($i = 0; $i < $take; $i++) {
                [$k, $chosen] = $this->drawOne($group);
                $picked[] = $chosen;
                $group = $group->except($k);
                $remaining = $remaining->except($k);
            }
        }

        // Complète au pondéré pur si arrondis (toujours $n au total).
        while (count($picked) < $n && ! $remaining->isEmpty()) {
            [$k, $chosen] = $this->drawOne($remaining);
            $picked[] = $chosen;
            $remaining = $remaining->except($k);
        }

        return $picked;
    }

    /** Quotas par niveau : parts de poids (biais C1/C1+ préservé, sans minimum forcé). */
    private function stratifiedQuotas(array $byLevel, int $n): array
    {
        $weights = [];
        $total = 0.0;
        foreach ($byLevel as $level => $group) {
            $w = 0.0;
            foreach ($group as $item) {
                $w += $this->weight($item);
            }
            $weights[$level] = $w;
            $total += $w;
        }

        if ($total <= 0) {
            return array_map(fn () => 0, $byLevel);
        }

        // Pas de minimum forcé : un groupe à 4,7% de poids (1×B2 face à
        // 5×C1+) obtient quota 0 sur un format de 3 — le biais officiel
        // C1/C1+ est préservé. La stratification évite seulement les tirages
        // 100% extrêmes sur les gros pools mixtes (quotas proportionnels).
        $quotas = [];
        foreach ($weights as $level => $w) {
            $quotas[$level] = (int) floor($n * $w / $total);
        }

        // Ajuste la somme à $n sur le groupe le plus lourd.
        $sum = array_sum($quotas);
        if ($sum < $n) {
            $heaviest = array_keys($weights, max($weights))[0];
            $quotas[$heaviest] += $n - $sum;
        } elseif ($sum > $n) {
            $heaviest = array_keys($weights, max($weights))[0];
            $quotas[$heaviest] = max(0, $quotas[$heaviest] - ($sum - $n));
        }

        return $quotas;
    }

    /** Tire 1 item au pondéré dans $pool, retourne [clé, item]. */
    private function drawOne(Collection $pool): array
    {
        $total = 0.0;
        foreach ($pool as $item) {
            $total += $this->weight($item);
        }

        $roll = mt_rand(0, mt_getrandmax()) / mt_getrandmax() * max($total, 1e-9);
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

        return [$chosenKey, $chosen];
    }

    private function weight(Question $q): float
    {
        return self::WEIGHTS[$q->difficulty] ?? self::DEFAULT_WEIGHT;
    }
}