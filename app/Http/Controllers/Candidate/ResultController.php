<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Services\Statistics\StatisticsService;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function __construct(private StatisticsService $statistics) {}

    public function show(Request $request, Attempt $attempt)
    {
        $this->authorize('view', $attempt);

        $attempt->load([
            'modellTest',
            'results',
            'attemptExercises.exercise',
            'writingSubmissions.aiEvaluations',
            'speakingSubmissions.aiEvaluations',
        ]);

        $results = $attempt->results->keyBy(fn ($r) => $r->skill->value);

        $aiPending = $attempt->writingSubmissions->flatMap->aiEvaluations
            ->merge($attempt->speakingSubmissions->flatMap->aiEvaluations)
            ->filter(fn ($e) => in_array($e->status, ['pending', 'processing'], true))
            ->count();

        // Note finale sur 20 : moyenne des notes 0–20 des parties corrigées
        // (échelle TestDaF, jamais une note globale officielle).
        $grade20 = $results->count() > 0
            ? round($results->avg(fn ($r) => (float) $r->points20), 1)
            : null;

        return view('candidate.results.show', [
            'attempt' => $attempt,
            'results' => $results,
            'aiPending' => $aiPending,
            'grade20' => $grade20,
            'partSummary' => $this->statistics->partSummary($attempt),
            'weakTypes' => $this->statistics->weakQuestionTypes($request->user(), 5),
        ]);
    }

    public function report(Request $request, Attempt $attempt)
    {
        $this->authorize('view', $attempt);

        $attempt->load(['modellTest', 'results', 'attemptExercises.exercise']);

        $results = $attempt->results->keyBy(fn ($r) => $r->skill->value);

        $grade20 = $results->count() > 0
            ? round($results->avg(fn ($r) => (float) $r->points20), 1)
            : null;

        return view('candidate.results.report', [
            'attempt' => $attempt,
            'results' => $results,
            'grade20' => $grade20,
            'weakTypes' => $this->statistics->weakQuestionTypes($request->user(), 8),
            'evolution' => $this->statistics->evolution($request->user()),
        ]);
    }
}
