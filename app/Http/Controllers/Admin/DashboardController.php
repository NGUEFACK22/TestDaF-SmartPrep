<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttemptStatus;
use App\Http\Controllers\Controller;
use App\Models\AiChallenge;
use App\Models\Attempt;
use App\Models\ChallengeUnlock;
use App\Models\Exercise;
use App\Models\ModellTest;
use App\Models\User;
use App\Services\Statistics\StatisticsService;

class DashboardController extends Controller
{
    public function __construct(private StatisticsService $statistics) {}

    public function index()
    {
        return view('admin.dashboard', [
            'overview' => $this->statistics->adminOverview(),
            'users' => User::count(),
            'levelTracks' => ModellTest::where('status', 'published')->count(),
            'exercises' => Exercise::count(),
            'attemptsInProgress' => Attempt::where('status', AttemptStatus::InProgress->value)->count(),
            'eliteUnlocks' => ChallengeUnlock::count(),
            'challengesReady' => AiChallenge::where('status', 'ready')->count(),
            'challengesFailed' => AiChallenge::where('status', 'failed')->count(),
            'recentAttempts' => Attempt::with(['user', 'modellTest'])->latest()->take(10)->get(),
        ]);
    }
}
