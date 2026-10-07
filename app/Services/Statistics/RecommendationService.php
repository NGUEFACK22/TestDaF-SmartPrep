<?php

namespace App\Services\Statistics;

use App\Enums\Skill;
use App\Models\Exercise;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * RecommendationService — recommandations basées sur les données réelles.
 *
 * Analyse les erreurs récurrentes par type de question et par compétence,
 * puis propose des exercices pertinents depuis la bibliothèque de contenu.
 */
class RecommendationService
{
    public function __construct(private StatisticsService $statistics) {}

    /** (Re)génère les recommandations d'un candidat. */
    public function generateFor(User $user): Collection
    {
        $weak = collect($this->statistics->weakQuestionTypes($user, 5))
            ->filter(fn ($row) => $row['errors'] >= 1 && $row['error_rate'] >= 40);

        // On repart des recommandations existantes pour rester idempotent.
        Recommendation::where('user_id', $user->id)->where('source', 'stats')->delete();

        $created = collect();

        foreach ($weak as $row) {
            $skill = $this->skillForQuestionType($row['type']);
            $exercise = $this->suggestExercise($skill, $row['type']);

            $created->push(Recommendation::create([
                'user_id' => $user->id,
                'skill' => $skill?->value,
                'title' => "Übungstyp: {$row['type']}",
                'description' => sprintf(
                    'Vous avez rencontré cette difficulté à %s%% (%d erreurs sur %d). '
                    .'Exercices recommandés pour progresser sur ce type.',
                    $row['error_rate'],
                    $row['errors'],
                    $row['total']
                ),
                'source' => 'stats',
                'exercise_id' => $exercise?->id,
                'data' => [
                    'question_type' => $row['type'],
                    'error_rate' => $row['error_rate'],
                    'skill' => $skill?->value,
                ],
            ]));
        }

        return $created;
    }

    /** Recommande un exercice publié correspondant à la compétence / au type. */
    public function suggestExercise(?Skill $skill, ?string $questionType = null, int $take = 3): Collection
    {
        $query = Exercise::query()->published();

        if ($skill) {
            $query->where('skill', $skill->value);
        }

        if ($questionType) {
            $query->where('type', $questionType);
        }

        return $query->inRandomOrder()->take($take)->get();
    }

    private function skillForQuestionType(string $questionType): ?Skill
    {
        // Plateforme 100 % QCM écrit : tout relève de Lesen.
        return Skill::Lesen;
    }
}
