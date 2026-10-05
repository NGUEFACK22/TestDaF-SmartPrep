<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\Difficulty;
use App\Enums\Skill;
use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Exercise;
use App\Models\ModellTest;
use App\Models\Result;
use App\Models\User;
use App\Models\UserAnswer;
use App\Services\Exam\ExamService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * PreparationController — préparation par niveau (A1 → C2) et par compétence.
 *
 * - index() : la page « Préparation » liste les niveaux CECRL ; le niveau C1
 *   lance directement le test de positionnement (2 parties : audio fixes +
 *   texte/QCM dynamique), B1/B2 renvoient vers les Modelltests complets.
 * - show()  : espaces d'entraînement par compétence (cours, méthodes,
 *   exercices par difficulté, progression).
 */
class PreparationController extends Controller
{
    public function __construct(private ExamService $exam) {}

    /** Niveaux disponibles sur la page Préparation. */
    private const LEVELS = [
        'A1' => 'Découvrir : bases de la langue (tests à venir).',
        'A2' => 'Communication simple au quotidien (tests à venir).',
        'B1' => 'S\'exprimer sur des sujets familiers — Modelltests complets disponibles.',
        'B2' => 'Compréhension fine et argumentation — Modelltests complets disponibles.',
        'C1' => 'Niveau cible du TestDaF : test de positionnement en 2 parties (Hören audio + Lesen QCM).',
        'C2' => 'Bilingue / quasi natif (tests à venir).',
    ];

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
        abort_unless(in_array($level, ['B1', 'B2', 'C1'], true), 404);

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

    public function show(Request $request, string $skill)
    {
        validator(['skill' => $skill], ['skill' => [new Enum(Skill::class)]])->validate();

        $user = $request->user();
        $skillEnum = Skill::from($skill);

        $byDifficulty = [];
        foreach (Difficulty::cases() as $difficulty) {
            $byDifficulty[$difficulty->value] = Exercise::published()
                ->where('skill', $skill)
                ->where('difficulty', $difficulty->value)
                ->orderBy('position')
                ->get();
        }

        $recentAnswers = UserAnswer::query()
            ->where('user_answers.user_id', $user->id)
            ->join('questions', 'questions.id', '=', 'user_answers.question_id')
            ->join('exercises', 'exercises.id', '=', 'questions.exercise_id')
            ->where('exercises.skill', $skill)
            ->select('user_answers.is_correct')
            ->latest('user_answers.id')
            ->take(20)
            ->get();

        $successRate = $recentAnswers->isNotEmpty()
            ? round(($recentAnswers->where('is_correct', true)->count() / $recentAnswers->count()) * 100, 1)
            : null;

        $skillResults = Result::query()
            ->whereIn('attempt_id', $user->attempts()->pluck('id'))
            ->where('skill', $skill)
            ->latest('id')
            ->get();

        return view('candidate.preparation.show', [
            'skill' => $skillEnum,
            'byDifficulty' => $byDifficulty,
            'successRate' => $successRate,
            'skillResults' => $skillResults,
            'courses' => config("testdaf.exercise_types.$skill", []),
            'c1' => config("testdaf.c1.skill.$skill", []),
            'c1Global' => [
                'philosophy' => config('testdaf.c1.philosophy'),
                'exam_rule' => config('testdaf.c1.exam_rule'),
                'modes' => config('testdaf.c1.modes', []),
                'progression' => config('testdaf.c1.progression', []),
                'target' => config('testdaf.tdn.target'),
            ],
            'sprechTargets' => config('testdaf.sprechen.targets', []),
        ]);
    }
}
