<?php

namespace App\Jobs;

use App\Models\SpeakingSubmission;
use App\Services\Evaluation\SpeakingEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * ProcessSpeakingSubmission — pipeline Sprechen en arrière-plan
 * (transcription Whisper + analyse LLM + prononciation optionnelle).
 */
class ProcessSpeakingSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(public int $submissionId) {}

    public function handle(SpeakingEvaluationService $service): void
    {
        $submission = SpeakingSubmission::find($this->submissionId);

        if (! $submission) {
            return;
        }

        $service->evaluate($submission);
    }
}
