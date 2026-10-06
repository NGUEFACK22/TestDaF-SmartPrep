<?php

namespace App\Jobs;

use App\Models\AiChallenge;
use App\Services\Challenge\ChallengeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * GenerateChallengeQuestions — génération des QCM du Défi IA en arrière-plan.
 * Ne bloque jamais la requête HTTP (le candidat voit « Génération en cours… »).
 */
class GenerateChallengeQuestions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public int $challengeId) {}

    public function handle(ChallengeService $service): void
    {
        $challenge = AiChallenge::find($this->challengeId);

        if (! $challenge) {
            return;
        }

        $service->generate($challenge);
    }
}
