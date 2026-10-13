<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Services\Exam\ExamService;

/**
 * TrainingController — entraînement autonome par exercice (100 % QCM écrit).
 *
 * À la différence du mode examen : pas de chronomètre, pas d'ordre imposé,
 * correction immédiate affichée au candidat (mode entraînement).
 */
class TrainingController extends Controller
{
    public function show(Exercise $exercise)
    {
        abort_unless($exercise->status === 'published', 404);

        $exercise->load('questions.answerOptions', 'solutions');

        // Brassage des réponses à chaque affichage : le contenu change de
        // place, les LETTRES restent dans l'ordre (a, b, c, … de haut en bas).
        $engine = app(ExamService::class);
        $seed = bin2hex(random_bytes(6));

        foreach ($exercise->questions as $question) {
            $displayed = $engine->displayOptions($question, $seed);
            $byId = collect($displayed)->keyBy('id');

            $question->setRelation(
                'answerOptions',
                $question->answerOptions
                    ->sortBy(fn ($o) => array_search($o->getKey(), array_keys($byId->all())))
                    ->map(fn ($o) => tap($o->replicate(), fn ($copy) => $copy->label = $byId[$o->getKey()]['label']))
                    ->values()
            );
        }

        return view('candidate.training.show', [
            'exercise' => $exercise,
        ]);
    }
}
