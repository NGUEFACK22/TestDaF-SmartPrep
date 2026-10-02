<?php

namespace App\Http\Controllers\Candidate;

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
    public function show(Exercise $exercise)
    {
        abort_unless($exercise->status === 'published', 404);

        $exercise->load('questions.answerOptions', 'solutions', 'media');

        return view('candidate.training.show', [
            'exercise' => $exercise,
        ]);
    }
}
