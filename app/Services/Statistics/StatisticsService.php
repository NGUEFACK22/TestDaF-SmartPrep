<?php

namespace App\Services\Statistics;

use App\Enums\AttemptStatus;
use App\Enums\Skill;
use App\Models\Attempt;
use App\Models\AttemptExercise;
use App\Models\Result;
use App\Models\User;
use App\Models\UserAnswer;
use Illuminate\Support\Facades\Cache;

/**
 * StatisticsService — calculs de progression et statistiques.
 * (Très utile au tableau de bord candidat et au tableau de bord admin.)
 */
class StatisticsService
{
    /** Progression par compétence : dernier pourcentage connu par compétence (1 requête + cache). */
    public function skillProgress(User $user): array
    {
        return Cache::remember("stats:skill_progress:{$user->id}", 120, function () use ($user) {
            $attemptIds = $user->attempts()->pluck('id');

            // Aucun essai : aucune requête inutile (gros gain sur Neon).
            if ($attemptIds->isEmpty()) {
                $out = [];
                foreach (Skill::sequence() as $skill) {
                    $out[$skill->value] = [
                        'label' => $skill->label(),
                        'percentage' => 0.0,
                    ];
                }

                return $out;
            }

            // Une seule requête : dernier Result par skill (au lieu de 4).
            $latestBySkill = Result::query()
                ->whereIn('attempt_id', $attemptIds)
                ->orderByDesc('id')
                ->get()
                ->keyBy('skill');

            $out = [];
            foreach (Skill::sequence() as $skill) {
                $out[$skill->value] = [
                    'label' => $skill->label(),
                    'percentage' => (float) ($latestBySkill->get($skill->value)->percentage ?? 0),
                ];
            }

            return $out;
        });
    }

    /** Évolution par Modelltest terminé (séries pour graphiques, cache 2 min). */
    public function evolution(User $user, ?Skill $skill = null): array
    {
        $key = "stats:evolution:{$user->id}:".($skill?->value ?? 'all');

        return Cache::remember($key, 120, function () use ($user, $skill) {
            // Limite à 30 tentatives récentes : les graphiques n'ont pas
            // besoin de 500 points et la requête reste légère sur Neon.
            $attempts = $user->attempts()
                ->where('status', AttemptStatus::Completed->value)
                ->with(['results', 'modellTest:id,number'])
                ->orderBy('completed_at')
                ->limit(30)
                ->get();

        $series = [];

        foreach ($attempts as $attempt) {
            $row = [
                'attempt_id' => $attempt->id,
                'label' => 'MT '.($attempt->modellTest?->number ?? '?'),
                'date' => $attempt->completed_at?->format('d/m/Y'),
            ];

            if ($skill) {
                $row['percentage'] = (float) ($attempt->resultFor($skill)?->percentage ?? 0);
            } else {
                foreach (Skill::sequence() as $s) {
                    $row[$s->value] = (float) ($attempt->resultFor($s)?->percentage ?? 0);
                }
                $row['global'] = $attempt->percentage();
            }

                $series[] = $row;
            }

            return $series;
        });
    }

    /** Vue d'ensemble candidat (cache 2 min, 3 requêtes agrégées). */
    public function overview(User $user): array
    {
        return Cache::remember("stats:overview:{$user->id}", 120, function () use ($user) {
            $started = $user->attempts()->count();

            // Aucun essai : zéro requête supplémentaire.
            if ($started === 0) {
                return [
                    'started' => 0,
                    'completed' => 0,
                    'average' => 0,
                    'last_attempt' => null,
                    'skill_progress' => $this->skillProgress($user),
                ];
            }

            $completedQuery = $user->attempts()->where('status', AttemptStatus::Completed->value);

            return [
                'started' => $started,
                'completed' => (clone $completedQuery)->count(),
                'average' => round((float) (clone $completedQuery)->avg('score'), 2),
                'last_attempt' => $user->attempts()->with('modellTest:id,number,title')->latest('id')->first(),
                'skill_progress' => $this->skillProgress($user),
            ];
        });
    }

    /**
     * Synthèse par partie pour la page résultats : score de chaque partie
     * (exercice) + points à améliorer (prompts des questions ratées).
     */
    public function partSummary(Attempt $attempt): array
    {
        $attempt->loadMissing(['attemptExercises.exercise', 'answers.question']);

        $parts = [];

        foreach ($attempt->attemptExercises as $ae) {
            $max = (float) ($ae->max_score ?? 0);
            $score = (float) ($ae->score ?? 0);

            $parts[] = [
                'title' => $ae->exercise->title,
                'skill_label' => $ae->exercise->skill->label(),
                'skill' => $ae->exercise->skill->value,
                'score' => $score,
                'max' => $max,
                'percentage' => $max > 0 ? round($score / $max * 100, 1) : null,
                'issues' => $this->partIssues($attempt, $ae),
            ];
        }

        return $parts;
    }

    /** Prompts (limités) des questions objectives ratées dans la forme de la partie. */
    private function partIssues(Attempt $attempt, AttemptExercise $ae): array
    {
        if ($ae->exercise->skill->isProductive()) {
            return [];
        }

        $formIds = $ae->formQuestions()->pluck('id');

        return $attempt->answers
            ->filter(fn ($answer) => $answer->is_correct === false)
            ->filter(fn ($answer) => $formIds->contains($answer->question_id))
            ->map(fn ($answer) => $answer->question?->prompt)
            ->filter()
            ->take(3)
            ->values()
            ->all();
    }

    /** Types de questions faibles (erreurs récurrentes, cache 5 min). */
    public function weakQuestionTypes(User $user, int $limit = 5): array
    {
        return Cache::remember("stats:weak:{$user->id}:{$limit}", 300, function () use ($user, $limit) {
            $rows = UserAnswer::query()
                ->join('questions', 'questions.id', '=', 'user_answers.question_id')
                ->where('user_answers.user_id', $user->id)
                ->whereNotNull('user_answers.is_correct')
                ->selectRaw('questions.type as type, COUNT(*) as total, SUM(CASE WHEN user_answers.is_correct = 0 THEN 1 ELSE 0 END) as errors')
                ->groupBy('questions.type')
                ->get();

            return $rows->map(fn ($r) => [
                'type' => $r->type,
                'total' => (int) $r->total,
                'errors' => (int) $r->errors,
                'error_rate' => $r->total > 0 ? round(($r->errors / $r->total) * 100, 1) : 0.0,
            ])->sortByDesc('error_rate')->take($limit)->values()->all();
        });
    }

    /** Invalide le cache stats d'un candidat (à appeler après correction). */
    public static function flushUserCache(int $userId): void
    {
        foreach (['stats:overview:', 'stats:skill_progress:', 'stats:evolution:', 'stats:weak:'] as $prefix) {
            // Cache database/file : pas de tags, on oublie les clés connues.
            Cache::forget("{$prefix}{$userId}");
        }
        Cache::forget("stats:skill_progress:{$userId}");
        Cache::forget("stats:overview:{$userId}");
        for ($i = 1; $i <= 8; $i++) {
            Cache::forget("stats:weak:{$userId}:{$i}");
        }
        foreach (['all', 'lesen', 'hoeren', 'schreiben', 'sprechen'] as $s) {
            Cache::forget("stats:evolution:{$userId}:{$s}");
        }
    }

    /** Vue d'ensemble administrateur (cache 5 min). */
    public function adminOverview(): array
    {
        return Cache::remember('stats:admin_overview', 300, function () {
            $completed = Attempt::where('status', AttemptStatus::Completed->value);

            return [
                'users' => User::count(),
                'attempts' => Attempt::count(),
                'completed_attempts' => (clone $completed)->count(),
                'average_score' => round((float) (clone $completed)->avg('score'), 2),
                'skill_average' => $this->globalSkillAverage(),
            ];
        });
    }

    private function globalSkillAverage(): array
    {
        // Une seule requête GROUP BY au lieu de 4 AVG.
        $rows = Result::query()
            ->selectRaw('skill, AVG(percentage) as avg_pct')
            ->groupBy('skill')
            ->pluck('avg_pct', 'skill');

        $out = [];
        foreach (Skill::sequence() as $skill) {
            $out[$skill->value] = (float) round((float) ($rows[$skill->value] ?? 0), 2);
        }

        return $out;
    }
}
