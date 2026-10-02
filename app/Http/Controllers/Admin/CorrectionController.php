<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualCorrection;
use App\Models\SpeakingSubmission;
use App\Models\WritingSubmission;
use App\Notifications\ManualCorrectionReady;
use App\Services\Exam\ScoringService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * CorrectionController — correction manuelle des productions (Schreiben/Sprechen).
 * Le correcteur consulte la consigne, la réponse (texte ou audio) puis attribue
 * une note et des commentaires.
 */
class CorrectionController extends Controller
{
    public function __construct(private ScoringService $scoring) {}

    public function index()
    {
        return view('admin.corrections.index', [
            'writing' => WritingSubmission::whereNotNull('submitted_at')
                ->with('attemptExercise.exercise', 'attempt.user')
                ->latest('submitted_at')->take(50)->get(),
            'speaking' => SpeakingSubmission::whereNotNull('submitted_at')
                ->with('attemptExercise.exercise', 'attempt.user', 'media')
                ->latest('submitted_at')->take(50)->get(),
            'recent' => ManualCorrection::with('corrector')->latest('corrected_at')->take(20)->get(),
        ]);
    }

    public function show(string $type, int $id)
    {
        $submission = $this->resolve($type, $id);
        $submission->load('attemptExercise.exercise', 'attempt.user');

        return view('admin.corrections.show', [
            'type' => $type,
            'submission' => $submission,
            'exercise' => $submission->attemptExercise->exercise,
            'existing' => $submission->manualCorrections()->latest()->first(),
            'ai' => $submission->aiEvaluations()->latest()->first(),
        ]);
    }

    public function store(Request $request, string $type, int $id)
    {
        $submission = $this->resolve($type, $id);

        $data = $request->validate([
            'points' => ['required', 'numeric', 'min:0', 'max:999'],
            'comments' => ['nullable', 'string', 'max:5000'],
        ]);

        $exercise = $submission->attemptExercise->exercise;

        $correction = ManualCorrection::updateOrCreate(
            [
                'evaluable_type' => $submission->getMorphClass(),
                'evaluable_id' => $submission->getKey(),
            ],
            [
                'corrector_id' => $request->user()->id,
                'points' => $data['points'],
                'max_points' => (float) $exercise->points,
                'comments' => $data['comments'] ?? null,
                'status' => 'corrected',
                'corrected_at' => now(),
            ]
        );

        // Report de la note sur la tâche et recalcul des résultats.
        $attemptExercise = $submission->attemptExercise;
        $attemptExercise->update([
            'score' => (float) $data['points'],
            'max_score' => (float) $exercise->points,
        ]);

        $this->scoring->computeResults($attemptExercise->attempt);

        $attemptExercise->attempt->user?->notify(new ManualCorrectionReady(
            attemptId: (int) $attemptExercise->attempt_id,
            skill: (string) $exercise->skill->value,
            points: (float) $data['points'],
            maxPoints: (float) $exercise->points,
        ));

        return redirect()->route('admin.corrections.index')
            ->with('status', 'Correction enregistrée pour la tentative #'.$attemptExercise->attempt_id.'.');
    }

    private function resolve(string $type, int $id): Model
    {
        return match ($type) {
            'writing' => WritingSubmission::findOrFail($id),
            'speaking' => SpeakingSubmission::findOrFail($id),
            default => abort(404),
        };
    }
}
