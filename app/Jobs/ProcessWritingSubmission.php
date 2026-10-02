<?php

namespace App\Jobs;

use App\Models\WritingSubmission;
use App\Services\Evaluation\WritingEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * ProcessWritingSubmission — analyse Schreiben en arrière-plan.
 * Ne bloque jamais la requête HTTP (le candidat voit « Analyse en cours... »).
 */
class ProcessWritingSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public int $submissionId) {}

    public function handle(WritingEvaluationService $service): void
    {
        $submission = WritingSubmission::find($this->submissionId);

        if (! $submission) {
            return;
        }

        $service->evaluate($submission);
    }
}
