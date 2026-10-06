<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\Skill;
use App\Http\Controllers\Controller;
use App\Models\Exercise;

/**
 * TrainingController — entraînement autonome par exercice.
 *
 * À la différence du mode examen : pas de chronomètre, pas d'ordre imposé,
 * correction immédiate affichée au candidat (mode entraînement).
 */
class TrainingController extends Controller
{
    /**
     * Les 7 démos officielles Hörverstehen du TestDaF digital.
     *
     * Matériel pédagogique public (TestDaF Institute) : un exercice
     * d'entraînement par type de tâche, non noté et sans chronomètre.
     */
    public function hoeren()
    {
        $demos = Exercise::query()
            ->where('skill', Skill::Hoeren)
            ->where('status', 'published')
            ->with('media')
            ->get()
            ->filter(fn (Exercise $exercise): bool => ! empty($exercise->content['demo']))
            ->sortBy(fn (Exercise $exercise): int => (int) ($exercise->content['demo_number'] ?? 0))
            ->values();

        return view('candidate.training.hoeren-index', [
            'demos' => $demos,
        ]);
    }

    public function show(Exercise $exercise)
    {
        abort_unless($exercise->status === 'published', 404);

        $exercise->load('questions.answerOptions', 'solutions', 'media');

        // Brassage des réponses à chaque affichage : l'ordre change à chaque
        // visite (les libellés voyagent avec leur texte, la correction suit).
        foreach ($exercise->questions as $question) {
            $question->setRelation('answerOptions', $question->answerOptions->shuffle()->values());
        }

        return view('candidate.training.show', [
            'exercise' => $exercise,
        ]);
    }
}
