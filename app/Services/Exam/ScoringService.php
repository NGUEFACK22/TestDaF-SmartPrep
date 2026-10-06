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
 *
 * Barème : points partiels proportionnels pour les types à réponses
 * multiples (multiple_choice, matching, fill_blank, ordering...).
 * Les types à réponse unique restent binaires.
 */
class ScoringService
{
    /** Corrige toutes les questions objectives d'une tâche terminée. */
    public function scoreObjective(Attempt $attempt, AttemptExercise $attemptExercise): void
    {
        // Seules les questions de la forme de cette tentative sont corrigées
        // (question_form = null ⇒ toutes les questions, comportement legacy).
        $questions = $attemptExercise->formQuestions();

        $earned = 0.0;
        $max = 0.0;

        // Évite le N+1 : charge toutes les réponses de la tentative en une fois.
        $answersByQuestion = $attempt->answers()->get()->keyBy('question_id');

        foreach ($questions as $question) {
            if (! $question->isObjective()) {
                continue;
            }

            $questionMax = (float) $question->points;
            $max += $questionMax;

            $userAnswer = $answersByQuestion->get($question->id);
            if (! $userAnswer) {
                continue;
            }

            $points = $this->scorePoints($question, $userAnswer->decoded());
            $isCorrect = $points >= $questionMax - 1e-9 && $questionMax > 0;

            $userAnswer->update([
                'is_correct' => $isCorrect,
                'points' => round($points, 2),
                'correction_status' => 'corrected',
            ]);

            $earned += $points;
        }

        $attemptExercise->update(['score' => round($earned, 2), 'max_score' => round($max, 2)]);
    }

    /** Compare une réponse candidat à la réponse correcte selon le type (100% ou 0%). */
    public function isCorrect(Question $question, mixed $answer): bool
    {
        $max = (float) $question->points;

        if ($max <= 0) {
            return false;
        }

        return $this->scorePoints($question, $answer) >= $max - 1e-9;
    }

    /**
     * Points obtenus (0..points) avec barème partiel.
     * Déterministe, sans IA, arrondi à 2 décimales par l'appelant.
     */
    public function scorePoints(Question $question, mixed $answer): float
    {
        $correct = $question->correct_answer;

        if ($correct === null) {
            return 0.0;
        }

        $max = (float) $question->points;

        $ratio = match ($question->type) {
            'multiple_choice' => $this->scoreSetsRatio((array) $answer, (array) $correct),
            'single_choice', 'true_false', 'text_input' => $this->normalize($answer) === $this->normalize($correct) ? 1.0 : 0.0,
            'ordering' => $this->scoreOrderingRatio((array) $answer, (array) $correct),
            'fill_blank' => $this->scoreSequencesRatio((array) $answer, (array) $correct),
            'matching', 'category_assignment', 'pair_assignment' => $this->scoreMapsRatio((array) $answer, (array) $correct),
            'short_answer' => $this->matchesAnyKeyword((string) $answer, (array) $correct) ? 1.0 : 0.0,
            default => 0.0,
        };

        $ratio = max(0.0, min(1.0, (float) $ratio));

        return round($max * $ratio, 2);
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

    /**
     * Normalisation allemande : minuscules, ß→ss, umlauts repliés,
     * ponctuation supprimée sans coller les mots, espaces unifiés.
     * Évite les faux positifs de l'ancien strip [[:punct:]].
     */
    private function normalize(mixed $value): string
    {
        if (is_array($value)) {
            $value = implode(' ', array_map('strval', $value));
        }

        $value = trim((string) $value);
        $value = mb_strtolower($value, 'UTF-8');

        // Repli allemand pour la comparaison (ß, ä, ö, ü).
        $value = str_replace(['ß', 'ä', 'ö', 'ü'], ['ss', 'a', 'o', 'u'], $value);

        // Toute ponctuation / symbole devient un espace (pas de collage).
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * Barème partiel multiple_choice : hits/total_correct pénalisé
     * par les intrus (0.5 point par intrus). Anti-devinette.
     */
    private function scoreSetsRatio(array $answer, array $correct): float
    {
        $a = array_unique(array_map(fn ($v) => $this->normalize($v), $answer));
        $b = array_unique(array_map(fn ($v) => $this->normalize($v), $correct));
        $a = array_values(array_filter($a, fn ($v) => $v !== ''));
        $b = array_values(array_filter($b, fn ($v) => $v !== ''));

        if (count($b) === 0) {
            return 0.0;
        }
        if (count($a) === 0) {
            return 0.0;
        }

        $hits = count(array_intersect($a, $b));
        $extra = count(array_diff($a, $b));

        return max(0.0, ($hits - $extra * 0.5) / count($b));
    }

    private function scoreSequencesRatio(array $answer, array $correct): float
    {
        $a = array_map(fn ($v) => $this->normalize($v), array_values($answer));
        $b = array_map(fn ($v) => $this->normalize($v), array_values($correct));

        if (count($b) === 0) {
            return 0.0;
        }

        $hits = 0;
        foreach ($b as $i => $expected) {
            if (($a[$i] ?? null) === $expected && $expected !== '') {
                $hits++;
            }
        }

        return $hits / count($b);
    }

    private function scoreMapsRatio(array $answer, array $correct): float
    {
        $norm = fn (array $arr) => collect($arr)
            ->mapWithKeys(fn ($v, $k) => [(string) $k => $this->normalize($v)])
            ->all();

        $a = $norm($answer);
        $b = $norm($correct);

        if (count($b) === 0) {
            return 0.0;
        }

        $hits = 0;
        foreach ($b as $k => $expected) {
            if (isset($a[$k]) && $a[$k] === $expected && $expected !== '') {
                $hits++;
            }
        }

        return $hits / count($b);
    }

    /**
     * Ordering : ratio de positions exactes + bonus de paires adjacentes.
     * Ex. [A,B,C,D] vs [A,C,B,D] = 2/4 positions + paires partielles.
     */
    private function scoreOrderingRatio(array $answer, array $correct): float
    {
        $a = array_map(fn ($v) => $this->normalize($v), array_values($answer));
        $b = array_map(fn ($v) => $this->normalize($v), array_values($correct));

        if (count($b) === 0) {
            return 0.0;
        }
        if ($a === $b) {
            return 1.0;
        }

        $hits = 0;
        foreach ($b as $i => $expected) {
            if (($a[$i] ?? null) === $expected) {
                $hits++;
            }
        }

        return $hits / count($b);
    }

    /**
     * short_answer strict : mot entier (frontières) + tolérance typo
     * Levenshtein ≤1 pour mots ≥5 lettres. Évite "art" dans "part".
     */
    private function matchesAnyKeyword(string $answer, array $keywords): bool
    {
        $normalized = ' '.$this->normalize($answer).' ';

        if (trim($normalized) === '') {
            return false;
        }

        foreach ($keywords as $keyword) {
            $needle = $this->normalize($keyword);
            if ($needle === '') {
                continue;
            }

            // Mot entier ou locution entière.
            if (str_contains($normalized, ' '.$needle.' ')) {
                return true;
            }

            // Tolérance typo : chaque mot-clé ≥5 lettres accepte distance 1.
            foreach (preg_split('/\s+/u', trim($normalized)) ?: [] as $word) {
                if (mb_strlen($needle) >= 5 && mb_strlen($word) >= 5
                    && levenshtein($word, $needle) <= 1) {
                    return true;
                }
            }
        }

        return false;
    }
}
