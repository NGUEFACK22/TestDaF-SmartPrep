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

    public int $tries = 3;

    public int $timeout = 300;

    /** Délai avant une nouvelle tentative après une erreur transitoire (quota 429/413, 5xx). */
    public int $retryDelaySeconds = 75;

    public function __construct(public int $challengeId) {}

    public function handle(ChallengeService $service): void
    {
        $challenge = AiChallenge::find($this->challengeId);

        if (! $challenge) {
            return;
        }

        $service->generate($challenge);

        // Erreur transitoire (ex. quota tokens/minute dépassé) : on repasse le défi
        // en « generation en cours » et on re-tente après la fenêtre de quota,
        // au lieu d'afficher un échec définitif.
        $challenge->refresh();

        if ($challenge->status === 'failed' && $service->lastErrorRetryable() && $this->attempts() < $this->tries) {
            $challenge->update([
                'status' => 'generating',
                'error' => null,
                'completed_at' => null,
            ]);

            $this->release($this->retryDelaySeconds);

            return;
        }
    }
}
