<?php

namespace App\Services\Statistics;

use App\Enums\AttemptStatus;
use App\Enums\Skill;
use App\Models\Attempt;
use App\Models\Result;
use App\Models\User;
use App\Models\UserAnswer;

/**
 * StatisticsService — calculs de progression et statistiques.
 * (Très utile au tableau de bord candidat et au tableau de bord admin.)
 */
class StatisticsService
{
    /** Progression par compétence : dernier pourcentage connu par compétence. */
    public function skillProgress(User $user): array
    {
        $attemptIds = $user->attempts()->pluck('id');
        $out = [];

        foreach (Skill::sequence() as $skill) {
            $latest = Result::query()
                ->whereIn('attempt_id', $attemptIds)
                ->where('skill', $skill->value)
                ->latest('id')
                ->first();

            $out[$skill->value] = [
                'label' => $skill->label(),
                'percentage' => (float) ($latest->percentage ?? 0),
            ];
        }

        return $out;
    }

    /** Évolution par Modelltest terminé (séries pour graphiques). */
    public function evolution(User $user, ?Skill $skill = null): array
    {
        $attempts = $user->attempts()
            ->where('status', AttemptStatus::Completed->value)
            ->with(['results', 'modellTest'])
            ->orderBy('completed_at')
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
    }

    /** Vue d'ensemble candidat. */
    public function overview(User $user): array
    {
        $attempts = $user->attempts();
        $completed = (clone $attempts)->where('status', AttemptStatus::Completed->value);

        return [
            'started' => (clone $attempts)->count(),
            'completed' => (clone $completed)->count(),
            'average' => round((float) (clone $completed)->avg('score'), 2),
            'last_attempt' => $user->attempts()->with('modellTest')->latest('id')->first(),
            'skill_progress' => $this->skillProgress($user),
        ];
    }

    /** Types de questions faibles (erreurs récurrentes). */
    public function weakQuestionTypes(User $user, int $limit = 5): array
    {
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
    }

    /** Vue d'ensemble administrateur. */
    public function adminOverview(): array
    {
        $completed = Attempt::where('status', AttemptStatus::Completed->value);

        return [
            'users' => User::count(),
            'attempts' => Attempt::count(),
            'completed_attempts' => (clone $completed)->count(),
            'average_score' => round((float) (clone $completed)->avg('score'), 2),
            'skill_average' => $this->globalSkillAverage(),
        ];
    }

    private function globalSkillAverage(): array
    {
        $out = [];
        foreach (Skill::sequence() as $skill) {
            $out[$skill->value] = (float) round((float) Result::where('skill', $skill->value)->avg('percentage'), 2);
        }

        return $out;
    }
}
