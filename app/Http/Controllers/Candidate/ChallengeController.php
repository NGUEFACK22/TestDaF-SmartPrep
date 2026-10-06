<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\AiChallenge;
use App\Models\ChallengeUnlock;
use App\Models\ModellTest;
use App\Services\Challenge\ChallengeService;
use App\Services\Exam\ExamService;
use Illuminate\Http\Request;

/**
 * ChallengeController — Espace Élite : QCM générés par IA après 2 scores
 * parfaits consécutifs. Les tests générés sont draft (invisibles de la
 * liste) et joués avec le moteur d'examen standard.
 */
class ChallengeController extends Controller
{
    public function __construct(
        private ChallengeService $challenges,
        private ExamService $exam,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $unlocks = ChallengeUnlock::where('user_id', $user->id)
            ->with('modellTest')
            ->latest()
            ->get();

        return view('candidate.challenges.index', [
            'unlocks' => $unlocks,
            'active' => $this->challenges->activeChallenge($user),
            'history' => AiChallenge::where('user_id', $user->id)
                ->with('generatedTest')
                ->latest('id')
                ->take(10)
                ->get(),
            'minQuestions' => (int) config('testdaf.challenge.min_questions', 20),
        ]);
    }

    /** Demande une génération inédite (file d'attente, 1 défi actif à la fois). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'modell_test_id' => ['required', 'integer', 'exists:modell_tests,id'],
        ]);

        $test = ModellTest::findOrFail($data['modell_test_id']);

        try {
            $challenge = $this->challenges->requestGeneration($request->user(), $test);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('challenges.index')
            ->with('status', "Génération #{$challenge->id} lancée : vos QCM inédits arrivent (min 20 questions).");
    }

    /** Joue le défi (test généré draft, moteur standard). */
    public function play(Request $request, AiChallenge $challenge)
    {
        abort_unless($challenge->user_id === $request->user()->id, 403);
        abort_unless($challenge->status === 'ready' && $challenge->generated_test_id, 404);

        $attempt = $this->exam->startAttempt(
            $request->user(),
            $challenge->generatedTest,
            'exam'
        );

        return redirect()->route('exam.show', $attempt);
    }

    /** État de la génération (polling). */
    public function status(Request $request, AiChallenge $challenge)
    {
        abort_unless($challenge->user_id === $request->user()->id, 403);

        return response()->json([
            'status' => $challenge->status,
            'error' => $challenge->error,
            'ready' => $challenge->status === 'ready',
            'play_url' => $challenge->status === 'ready'
                ? route('challenges.play', $challenge)
                : null,
        ]);
    }
}
