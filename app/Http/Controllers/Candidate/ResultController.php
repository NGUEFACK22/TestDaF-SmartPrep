<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Services\Statistics\StatisticsService;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function __construct(
        private StatisticsService $statistics,
        private \App\Services\Exam\ExamService $exam,
    ) {}

    public function show(Request $request, Attempt $attempt)
    {
        $this->authorize('view', $attempt);

        $attempt->load([
            'modellTest',
            'results',
            'attemptExercises.exercise',
        ]);

        $results = $attempt->results->keyBy(fn ($r) => $r->skill->value);

        // Note finale sur 20 : moyenne des notes 0–20 des parties corrigées
        // (échelle TestDaF, jamais une note globale officielle).
        $grade20 = $results->count() > 0
            ? round($results->avg(fn ($r) => (float) $r->points20), 1)
            : null;

        return view('candidate.results.show', [
            'attempt' => $attempt,
            'results' => $results,
            'grade20' => $grade20,
            'partSummary' => $this->statistics->partSummary($attempt),
            'weakTypes' => $this->statistics->weakQuestionTypes($request->user(), 5),
            // Bonnes réponses dans les LETTRES affichées pendant la tentative
            // (rotation) : cohérence avec ce que le candidat a vu.
            'correctLabels' => $this->correctLabels($attempt),
        ]);
    }

    /**
     * Map question_id → libellés affichés des bonnes réponses, pour les
     * types à options (les lettres tournent à chaque tour).
     */
    private function correctLabels(Attempt $attempt): array
    {
        $map = [];

        foreach ($attempt->attemptExercises as $ae) {
            foreach ($ae->formQuestions() as $question) {
                if (! in_array($question->type, ['single_choice', 'true_false', 'multiple_choice'], true)) {
                    continue;
                }

                $labels = $this->exam->displayedCorrectLabels($question, $attempt);

                if ($labels !== []) {
                    $map[$question->id] = $labels;
                }
            }
        }

        return $map;
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

    /**
     * Version imprimable (export PDF via le navigateur : Fichier → Imprimer → PDF).
     * Sans dépendance lourde (dompdf) : HTML épuré + print CSS.
     */
    public function pdf(Request $request, Attempt $attempt)
    {
        $this->authorize('view', $attempt);

        $attempt->load(['modellTest', 'results', 'attemptExercises.exercise']);

        $results = $attempt->results->keyBy(fn ($r) => $r->skill->value);

        $grade20 = $results->count() > 0
            ? round($results->avg(fn ($r) => (float) $r->points20), 1)
            : null;

        return response()->view('candidate.results.pdf', [
            'attempt' => $attempt,
            'results' => $results,
            'grade20' => $grade20,
            'partSummary' => $this->statistics->partSummary($attempt),
            'weakTypes' => $this->statistics->weakQuestionTypes($request->user(), 8),
            'user' => $request->user(),
            'generatedAt' => now(),
        ])->header('Content-Disposition', 'inline; filename="rapport-testdaf-'.$attempt->id.'.html"');
    }
}
