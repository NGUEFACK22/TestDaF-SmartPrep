<?php

namespace App\Http\Controllers\Candidate;

use App\Exceptions\ExamException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveAnswerRequest;
use App\Models\Attempt;
use App\Models\Question;
use App\Services\Exam\AnswerService;

/**
 * AnswerController — sauvegarde (et autosave) des réponses aux questions
 * objectives. Toutes les autorisations sont validées côté serveur.
 */
class AnswerController extends Controller
{
    public function __construct(private AnswerService $answers) {}

    public function store(SaveAnswerRequest $request, Attempt $attempt)
    {
        $this->authorize('interact', $attempt);

        $question = Question::findOrFail($request->integer('question_id'));

        $attemptExercise = $attempt->attemptExercises()
            ->where('exercise_id', $question->exercise_id)
            ->first();

        if (! $attemptExercise) {
            throw ExamException::unauthorized('Cette question n\'appartient pas à cette tentative.');
        }

        $userAnswer = $this->answers->save(
            $attempt,
            $attemptExercise,
            $question,
            $request->input('answer'),
            $request->boolean('autosave')
        );

        return response()->json([
            'ok' => true,
            'question_id' => $userAnswer->question_id,
            'saved_at' => $userAnswer->answered_at?->toIso8601String(),
        ]);
    }
}
