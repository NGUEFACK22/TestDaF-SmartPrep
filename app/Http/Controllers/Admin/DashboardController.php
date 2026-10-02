<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttemptStatus;
use App\Http\Controllers\Controller;
use App\Models\AiEvaluation;
use App\Models\Attempt;
use App\Models\ManualCorrection;
use App\Models\Media;
use App\Models\ModellTest;
use App\Models\User;
use App\Models\WritingSubmission;
use App\Services\Statistics\StatisticsService;

class DashboardController extends Controller
{
    public function __construct(private StatisticsService $statistics) {}

    public function index()
    {
        return view('admin.dashboard', [
            'overview' => $this->statistics->adminOverview(),
            'users' => User::count(),
            'modelltests' => ModellTest::count(),
            'published' => ModellTest::where('status', 'published')->count(),
            'attemptsInProgress' => Attempt::where('status', AttemptStatus::InProgress->value)->count(),
            'pendingCorrections' => ManualCorrection::where('status', 'pending')->count()
                + WritingSubmission::whereNull('submitted_at')->count(),
            'aiCompleted' => AiEvaluation::where('status', 'completed')->count(),
            'aiFailed' => AiEvaluation::where('status', 'failed')->count(),
            'mediaCount' => Media::count(),
            'mediaSize' => (int) Media::sum('size'),
            'recentAttempts' => Attempt::with(['user', 'modellTest'])->latest()->take(10)->get(),
        ]);
    }
}
