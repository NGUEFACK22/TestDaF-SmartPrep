<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWritingSubmissionRequest;
use App\Jobs\ProcessWritingSubmission;
use App\Models\Attempt;
use App\Models\WritingSubmission;
use App\Services\Exam\TimerService;
use Illuminate\Http\Request;

/**
 * WritingController — module Schreiben : autosave, compteur de mots,
 * verrouillage après expiration/validation.
 */
class WritingController extends Controller
{
    public function __construct(private TimerService $timer) {}

    public function store(StoreWritingSubmissionRequest $request, Attempt $attempt)
    {
        $this->authorize('interact', $attempt);

        $attemptExercise = $attempt->attemptExercises()
            ->where('exercise_id', $request->integer('exercise_id'))
            ->firstOrFail();

        $this->timer->sync($attemptExercise);

        if (! $attemptExercise->acceptsAnswers()) {
            return response()->json([
                'ok' => false,
                'locked' => true,
                'message' => 'Le temps imparti est écoulé : le texte ne peut plus être modifié.',
            ], 423);
        }

        $autosave = $request->boolean('autosave');

        $submission = WritingSubmission::updateOrCreate(
            ['attempt_id' => $attempt->id, 'attempt_exercise_id' => $attemptExercise->id],
            ['user_id' => $attempt->user_id, 'content' => (string) $request->input('content', '')]
        );

        $submission->recomputeWordCount();

        if (! $autosave) {
            $submission->submitted_at = now();
            $submission->locked = true;
        }

        $submission->save();

        if (! $autosave
            && $submission->submitted_at
            && (bool) config('testdaf.ai.auto_evaluation')
            && (bool) config('testdaf.ai.enabled')) {
            ProcessWritingSubmission::dispatch($submission->id);
        }

        return response()->json([
            'ok' => true,
            'word_count' => $submission->word_count,
            'locked' => $submission->locked,
            'saved_at' => now()->toIso8601String(),
        ]);
    }

    /** Récupère la soumission courante d'un exercice (reprise). */
    public function show(Request $request, Attempt $attempt)
    {
        $this->authorize('view', $attempt);

        $attemptExercise = $attempt->attemptExercises()
            ->where('exercise_id', $request->integer('exercise_id'))
            ->firstOrFail();

        $submission = WritingSubmission::where('attempt_id', $attempt->id)
            ->where('attempt_exercise_id', $attemptExercise->id)
            ->first();

        return response()->json([
            'content' => $submission?->content ?? '',
            'word_count' => $submission?->word_count ?? 0,
            'locked' => (bool) ($submission?->locked),
        ]);
    }
}
