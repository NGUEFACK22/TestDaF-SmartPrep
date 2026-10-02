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

        $overview = $this->statistics->overview($user);
        $skillProgress = $this->statistics->skillProgress($user);
        $evolution = $this->statistics->evolution($user);

        $recommendations = $user->recommendations()->latest()->take(5)->get();

        return view('candidate.dashboard', [
            'overview' => $overview,
            'skillProgress' => $skillProgress,
            'evolution' => $evolution,
            'recommendations' => $recommendations,
            'activeAttempt' => $user->activeAttempt(),
        ]);
    }
}
