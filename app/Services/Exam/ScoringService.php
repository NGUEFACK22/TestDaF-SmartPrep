<?php

namespace App\Services\Exam;

use App\Enums\Skill;
use App\Models\Attempt;
use App\Models\AttemptExercise;
use App\Models\Question;
use App\Models\Result;

/**
 * ScoringService — correction déterministe des questions objectives.
 * Aucune IA ici : comparaison réponse candidat / réponse correcte.
 */
class ScoringService
{
    /** Corrige toutes les questions objectives d'une tâche terminée. */
    public function scoreObjective(Attempt $attempt, AttemptExercise $attemptExercise): void
    {
        $attemptExercise->loadMissing('exercise.questions');

        $earned = 0.0;
        $max = 0.0;

        foreach ($attemptExercise->exercise->questions as $question) {
            if (! $question->isObjective()) {
                continue;
            }

            $max += (float) $question->points;

            $userAnswer = $attempt->answers()->where('question_id', $question->id)->first();
            if (! $userAnswer) {
                continue;
            }

            $isCorrect = $this->isCorrect($question, $userAnswer->decoded());
            $points = $isCorrect ? (float) $question->points : 0.0;

            $userAnswer->update([
                'is_correct' => $isCorrect,
                'points' => $points,
                'correction_status' => 'corrected',
            ]);

            $earned += $points;
        }

        $attemptExercise->update(['score' => $earned, 'max_score' => $max]);
    }

    /** Compare une réponse candidat à la réponse correcte selon le type. */
    public function isCorrect(Question $question, mixed $answer): bool
    {
        $correct = $question->correct_answer;

        if ($correct === null) {
            return false;
        }

        return match ($question->type) {
            'multiple_choice' => $this->compareSets((array) $answer, (array) $correct),
            'single_choice', 'true_false', 'text_input' => $this->normalize($answer) === $this->normalize($correct),
            'ordering' => array_values((array) $answer) === array_values((array) $correct),
            'fill_blank' => $this->compareSequences((array) $answer, (array) $correct),
            'matching', 'category_assignment', 'pair_assignment' => $this->compareMaps((array) $answer, (array) $correct),
            'short_answer' => $this->matchesAnyKeyword((string) $answer, (array) $correct),
            default => false,
        };
    }

    /** Agrège les résultats par compétence pour toute la tentative. */
    public function computeResults(Attempt $attempt): void
    {
        $attempt->loadMissing('attemptExercises.exercise');

        $grandEarned = 0.0;
        $grandMax = 0.0;

        foreach (Skill::sequence() as $skill) {
            $exercises = $attempt->attemptExercises
                ->filter(fn (AttemptExercise $ae) => $ae->exercise->skill === $skill);

            if ($exercises->isEmpty()) {
                continue;
            }

            $earned = (float) $exercises->sum(fn ($ae) => (float) ($ae->score ?? 0));
            $max = (float) $exercises->sum(fn ($ae) => (float) ($ae->max_score ?? 0));

            Result::updateOrCreate(
                ['attempt_id' => $attempt->id, 'skill' => $skill->value],
                [
                    'points' => $earned,
                    'max_points' => $max,
                    'percentage' => $max > 0 ? round(($earned / $max) * 100, 2) : 0.0,
                    'details' => [
                        'exercises' => $exercises->map(fn ($ae) => [
                            'exercise_id' => $ae->exercise_id,
                            'title' => $ae->exercise->title,
                            'score' => (float) ($ae->score ?? 0),
                            'max_score' => (float) ($ae->max_score ?? 0),
                            'status' => $ae->state()->value,
                        ])->values()->all(),
                    ],
                ]
            );

            $grandEarned += $earned;
            $grandMax += $max;
        }

        $attempt->update(['score' => $grandEarned, 'max_score' => $grandMax]);
    }

    // ------------------------------------------------------------- helpers

    private function normalize(mixed $value): string
    {
        if (is_array($value)) {
            $value = implode(' ', array_map('strval', $value));
        }

        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = preg_replace('/[[:punct:]]/u', '', $value) ?? $value;

        return trim($value);
    }

    private function compareSets(array $a, array $b): bool
    {
        $a = array_map(fn ($v) => $this->normalize($v), $a);
        $b = array_map(fn ($v) => $this->normalize($v), $b);
        sort($a);
        sort($b);

        return $a === $b;
    }

    private function compareSequences(array $a, array $b): bool
    {
        $a = array_map(fn ($v) => $this->normalize($v), array_values($a));
        $b = array_map(fn ($v) => $this->normalize($v), array_values($b));

        return $a === $b;
    }

    private function compareMaps(array $a, array $b): bool
    {
        $norm = fn (array $arr) => collect($arr)
            ->mapWithKeys(fn ($v, $k) => [(string) $k => $this->normalize($v)])
            ->sortKeys()
            ->all();

        return $norm($a) === $norm($b);
    }

    private function matchesAnyKeyword(string $answer, array $keywords): bool
    {
        $normalized = $this->normalize($answer);

        foreach ($keywords as $keyword) {
            $needle = $this->normalize($keyword);
            if ($needle !== '' && str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }
}
