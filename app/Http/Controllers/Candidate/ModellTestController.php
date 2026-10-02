<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\ModellTest;
use App\Services\Exam\ExamService;
use Illuminate\Http\Request;

class ModellTestController extends Controller
{
    public function __construct(private ExamService $exam) {}

    public function index(Request $request)
    {
        $tests = ModellTest::published()
            ->withCount('sections')
            ->orderBy('number')
            ->get();

        $user = $request->user();

        $attempts = $user->attempts()
            ->with('modellTest')
            ->latest('id')
            ->get()
            ->keyBy('modell_test_id');

        return view('candidate.modelltests.index', [
            'tests' => $tests,
            'attempts' => $attempts,
        ]);
    }

    /** Crée (ou reprend) une tentative et redirige vers l'examen. */
    public function start(Request $request, ModellTest $modellTest)
    {
        abort_unless($modellTest->status === 'published', 404);

        $mode = $request->input('mode') === 'training' ? 'training' : 'exam';

        $attempt = $this->exam->startAttempt($request->user(), $modellTest, $mode);

        return redirect()->route('exam.show', $attempt);
    }
}
