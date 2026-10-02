<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSpeakingSubmissionRequest;
use App\Jobs\ProcessSpeakingSubmission;
use App\Models\Attempt;
use App\Models\SpeakingSubmission;
use App\Services\Exam\TimerService;
use App\Services\Media\MediaService;

/**
 * SpeakingController — module Sprechen : réception sécurisée de l'audio
 * enregistré (WebM/Opus) et déclenchement de l'analyse.
 */
class SpeakingController extends Controller
{
    public function __construct(
        private TimerService $timer,
        private MediaService $media,
    ) {}

    public function store(StoreSpeakingSubmissionRequest $request, Attempt $attempt)
    {
        $this->authorize('interact', $attempt);

        $attemptExercise = $attempt->attemptExercises()
            ->where('exercise_id', $request->integer('exercise_id'))
            ->firstOrFail();

        $this->timer->sync($attemptExercise);

        // On accepte l'upload pendant la phase autorisée (y compris juste après
        // l'arrêt automatique de l'enregistrement, avant validation serveur).
        if ($attemptExercise->state()->isFinal() && $attemptExercise->hasExpired()) {
            return response()->json([
                'ok' => false,
                'locked' => true,
                'message' => 'Le temps imparti est écoulé.',
            ], 423);
        }

        $uploaded = $this->media->storeCandidateFile(
            $request->file('audio'),
            'audio',
            $attempt->user_id,
            $attempt->id,
            $attemptExercise->exercise_id
        );

        $submission = SpeakingSubmission::updateOrCreate(
            ['attempt_id' => $attempt->id, 'attempt_exercise_id' => $attemptExercise->id],
            [
                'user_id' => $attempt->user_id,
                'media_id' => $uploaded->id,
                'path' => $uploaded->path,
                'mime' => $uploaded->mime,
                'size' => $uploaded->size,
                'duration_seconds' => $request->integer('duration_seconds') ?: null,
            ]
        );

        $submit = $request->boolean('submit');

        if ($submit) {
            $submission->update(['submitted_at' => now(), 'locked' => true]);

            if ((bool) config('testdaf.ai.auto_evaluation') && (bool) config('testdaf.ai.enabled')) {
                ProcessSpeakingSubmission::dispatch($submission->id);
            }
        }

        return response()->json([
            'ok' => true,
            'media_id' => $uploaded->id,
            'submitted' => $submit,
            'playback_url' => route('media.stream', $uploaded),
        ]);
    }
}
