<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\ModellTest;
use App\Models\Result;
use App\Models\User;
use App\Services\Exam\ExamService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * PreparationController — entraînement QCM par niveau (A1 → C2).
 *
 * - index() : catalogue des niveaux (banques QCM A1–C1 + génération IA C1–C2).
 * - startLevel() : session chronométrée sur banque (tirage sans remise).
 * - generateLevel() : session IA inédite calibrée sur les faiblesses.
 */
class PreparationController extends Controller
{
    public function __construct(private ExamService $exam) {}

    /** Niveaux disponibles sur la page Préparation (100 % QCM écrit). */
    public const LEVELS = [
        'A1' => 'Découvrir : QCM simples du quotidien, questions différentes à chaque session.',
        'A2' => 'Communication simple : QCM du quotidien, questions différentes à chaque session.',
        'B1' => 'S\'exprimer sur des sujets familiers — QCM + Lückentext par session.',
        'B2' => 'Compréhension fine et implicite — QCM variés par session.',
        'C1' => 'Niveau cible du TestDaF : QCM au format TestDaF + sessions IA inédites.',
        'C2' => 'Quasi natif : sessions IA inédites générées à la demande.',
    ];

    /** Niveaux avec banque de QCM (tirage sans remise à chaque session). */
    public const BANK_LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1'];

    /** Niveaux avec génération IA inédite à la demande. */
    public const AI_LEVELS = ['C1', 'C2'];

    /** Page Préparation : grille des niveaux, départ direct du test C1. */
    public function index(Request $request)
    {
        $user = $request->user();

        $levels = [];
        foreach (self::LEVELS as $value => $description) {
            $tests = ModellTest::published()
                ->where('difficulty', $value)
                ->orderBy('number')
                ->get();

            $levels[] = [
                'value' => $value,
                'description' => $description,
                'tests' => $tests,
                'last_result' => $this->lastLevelResult($user, $tests),
            ];
        }

        return view('candidate.preparation.index', ['levels' => $levels]);
    }

    /**
     * Départ direct du test d'un niveau (POST : création d'une tentative).
     *
     * Le test lancé est le premier (par numéro) que le candidat n'a pas
     * encore tenté ; s'il a tout essayé, rotation vers celui dont sa
     * dernière tentative est la plus ancienne. Une tentative en cours sur
     * ce test est restaurée (voir ExamService::startAttempt).
     */
    public function startLevel(Request $request, string $level)
    {
        $level = strtoupper($level);
        abort_unless(in_array($level, self::BANK_LEVELS, true), 404);

        $tests = ModellTest::published()
            ->where('difficulty', $level)
            ->orderBy('number')
            ->get();

        abort_unless($tests->isNotEmpty(), 404);

        $test = $this->nextTestForUser($request->user(), $tests);
        $attempt = $this->exam->startAttempt($request->user(), $test);

        return redirect()->route('exam.show', $attempt);
    }

    /**
     * Génère une session IA inédite pour un niveau (C1/C2) : QCM calibrés
     * sur les faiblesses, cadre du niveau, minuteur par difficulté.
     * Accès direct, sans condition de score (la génération file d'attente).
     */
    public function generateLevel(Request $request, string $level)
    {
        $level = strtoupper($level);
        abort_unless(in_array($level, self::AI_LEVELS, true), 404);

        try {
            $challenge = app(\App\Services\Challenge\ChallengeService::class)
                ->requestLevelGeneration($request->user(), $level);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('challenges.index')
            ->with('status', "Session IA {$level} #{$challenge->id} lancée : QCM inédits en cours de génération.");
    }

    /**
     * Choix du test du niveau à lancer pour ce candidat :
     * 1) le premier test (par numéro) sans tentative ;
     * 2) sinon, celui dont la dernière tentative est la plus ancienne.
     */
    private function nextTestForUser(User $user, Collection $tests): ModellTest
    {
        $lastAttemptPerTest = Attempt::query()
            ->where('user_id', $user->id)
            ->whereIn('modell_test_id', $tests->pluck('id'))
            ->get(['id', 'modell_test_id'])
            ->groupBy('modell_test_id')
            ->map(fn ($group) => (int) $group->max('id'));

        return $tests
            ->sort(function (ModellTest $a, ModellTest $b) use ($lastAttemptPerTest) {
                $aLast = $lastAttemptPerTest->get($a->id, 0);
                $bLast = $lastAttemptPerTest->get($b->id, 0);

                if ($aLast !== $bLast) {
                    return $aLast <=> $bLast;
                }

                return $a->number <=> $b->number;
            })
            ->first();
    }

    private function lastLevelResult($user, $tests): ?array
    {
        if ($tests->isEmpty()) {
            return null;
        }

        $attempt = Attempt::query()
            ->where('user_id', $user->id)
            ->whereIn('modell_test_id', $tests->pluck('id'))
            ->where('status', 'completed')
            ->latest('id')
            ->first();

        if (! $attempt) {
            return null;
        }

        $results = Result::query()->where('attempt_id', $attempt->id)->get();
        $max = (float) $results->sum('max_points');

        return [
            'attempt_id' => $attempt->id,
            'percentage' => $max > 0 ? round(((float) $results->sum('points')) / $max * 100, 1) : 0.0,
            'date' => $attempt->completed_at,
        ];
    }

}
