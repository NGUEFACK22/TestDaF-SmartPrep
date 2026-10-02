<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\Difficulty;
use App\Enums\Skill;
use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\Result;
use App\Models\UserAnswer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

/**
 * PreparationController — espaces d'entraînement par compétence
 * (cours, méthodes, conseils, exercices par difficulté, progression).
 */
class PreparationController extends Controller
{
    public function show(Request $request, string $skill)
    {
        validator(['skill' => $skill], ['skill' => [new Enum(Skill::class)]])->validate();

        $user = $request->user();
        $skillEnum = Skill::from($skill);

        $byDifficulty = [];
        foreach (Difficulty::cases() as $difficulty) {
            $byDifficulty[$difficulty->value] = Exercise::published()
                ->where('skill', $skill)
                ->where('difficulty', $difficulty->value)
                ->orderBy('position')
                ->get();
        }

        $recentAnswers = UserAnswer::query()
            ->where('user_answers.user_id', $user->id)
            ->join('questions', 'questions.id', '=', 'user_answers.question_id')
            ->join('exercises', 'exercises.id', '=', 'questions.exercise_id')
            ->where('exercises.skill', $skill)
            ->select('user_answers.is_correct')
            ->latest('user_answers.id')
            ->take(20)
            ->get();

        $successRate = $recentAnswers->isNotEmpty()
            ? round(($recentAnswers->where('is_correct', true)->count() / $recentAnswers->count()) * 100, 1)
            : null;

        $skillResults = Result::query()
            ->whereIn('attempt_id', $user->attempts()->pluck('id'))
            ->where('skill', $skill)
            ->latest('id')
            ->get();

        return view('candidate.preparation.show', [
            'skill' => $skillEnum,
            'byDifficulty' => $byDifficulty,
            'successRate' => $successRate,
            'skillResults' => $skillResults,
            'courses' => config("testdaf.exercise_types.$skill", []),
        ]);
    }
}
