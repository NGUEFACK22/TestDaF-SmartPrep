<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Services\Statistics\RecommendationService;
use App\Services\Statistics\StatisticsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private StatisticsService $statistics,
        private RecommendationService $recommendations,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        // overview contient déjà skill_progress (cache 2 min) : pas de
        // double requête skillProgress (gros gain sur Neon serverless).
        $overview = $this->statistics->overview($user);
        $skillProgress = $overview['skill_progress'] ?? $this->statistics->skillProgress($user);
        $evolution = $this->statistics->evolution($user);

        $recommendations = $user->recommendations()->latest()->take(5)->get();

        // Espace Élite : déblocages (2× 100 %) + défi actif éventuel.
        $eliteUnlocks = \App\Models\ChallengeUnlock::where('user_id', $user->id)
            ->with('modellTest')
            ->latest()
            ->take(3)
            ->get();
        $eliteActive = app(\App\Services\Challenge\ChallengeService::class)->activeChallenge($user);

        return view('candidate.dashboard', [
            'overview' => $overview,
            'skillProgress' => $skillProgress,
            'evolution' => $evolution,
            'recommendations' => $recommendations,
            'activeAttempt' => $user->activeAttempt(),
            'eliteUnlocks' => $eliteUnlocks,
            'eliteActive' => $eliteActive,
        ]);
    }
}
