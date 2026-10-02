<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSpeakingSubmission;
use App\Jobs\ProcessWritingSubmission;
use App\Models\AiEvaluation;
use App\Models\Attempt;
use App\Models\SpeakingSubmission;
use App\Models\WritingSubmission;
use Illuminate\Http\Request;

/**
 * AiController — demander / consulter l'état des analyses IA
 * (jamais d'appel direct à l'IA depuis le navigateur).
 */
class AiController extends Controller
{
    /** Relance l'analyse IA d'une production (si échouée ou en attente). */
    public function request(Request $request, Attempt $attempt)
    {
        $this->authorize('interact', $attempt);

        $data = $request->validate([
            'type' => ['required', 'in:writing,speaking'],
            'id' => ['required', 'integer'],
        ]);

        if ($data['type'] === 'writing') {
            $submission = WritingSubmission::where('attempt_id', $attempt->id)->findOrFail($data['id']);
            ProcessWritingSubmission::dispatch($submission->id);
        } else {
            $submission = SpeakingSubmission::where('attempt_id', $attempt->id)->findOrFail($data['id']);
            ProcessSpeakingSubmission::dispatch($submission->id);
        }

        return response()->json(['ok' => true, 'message' => 'Analyse relancée.']);
    }

    /** État des analyses IA de la tentative (polling). */
    public function status(Request $request, Attempt $attempt)
    {
        $this->authorize('view', $attempt);

        $evaluations = AiEvaluation::where('attempt_id', $attempt->id)
            ->get()
            ->map(fn (AiEvaluation $e) => [
                'id' => $e->id,
                'skill' => $e->skill,
                'status' => $e->status,
                'indicator_score' => $e->indicatorScore(),
                'completed_at' => $e->completed_at?->toIso8601String(),
            ]);

        $pending = $evaluations->whereIn('status', ['pending', 'processing'])->count();

        return response()->json([
            'evaluations' => $evaluations,
            'pending' => $pending,
            'all_done' => $pending === 0,
        ]);
    }
}
