<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\ExerciseState;
use App\Enums\Skill;
use App\Exceptions\ExamException;
use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\AttemptExercise;
use App\Models\WritingSubmission;
use App\Services\Exam\ExamService;
use App\Services\Exam\TimerService;
use Illuminate\Http\Request;

/**
 * ExamController — interface d'examen (le serveur reste l'autorité).
 */
class ExamController extends Controller
{
    public function __construct(
        private ExamService $exam,
        private TimerService $timer,
    ) {}

    /** Affiche la tâche courante autorisée (ou redirige vers les résultats). */
    public function show(Request $request, Attempt $attempt)
    {
        $this->authorize('view', $attempt);

        if (! $attempt->isInProgress()) {
            return redirect()->route('results.show', $attempt);
        }

        $this->exam->restore($attempt);
        $attempt->refresh();

        $current = $attempt->currentExercise();

        if (! $current) {
            return redirect()->route('results.show', $attempt);
        }

        // Démarre (ou reprend) le chronomètre serveur de la tâche courante.
        if ($current->state() === ExerciseState::Available) {
            $current = $this->exam->startExercise($attempt, $current->exercise_id);
        } elseif ($current->state() === ExerciseState::Locked) {
            throw ExamException::locked();
        }

        return $this->renderExercise($attempt, $current);
    }

    /** Passe à la tâche suivante (bouton WEITER) ou finalise sur expiration. */
    public function complete(Request $request, Attempt $attempt, AttemptExercise $attemptExercise)
    {
        $this->authorize('interact', $attempt);

        abort_unless($attemptExercise->attempt_id === $attempt->id, 404);

        $expired = $attemptExercise->hasExpired();
        $next = $this->exam->completeExercise($attempt, $attemptExercise->exercise_id, $expired);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'completed' => true,
                'next' => $next ? [
                    'exercise_id' => $next->exercise_id,
                    'redirect' => route('exam.show', $attempt),
                ] : null,
                'finished' => $next === null,
                'redirect' => $next === null ? route('results.show', $attempt) : null,
            ]);
        }

        return $next
            ? redirect()->route('exam.show', $attempt)
            : redirect()->route('results.show', $attempt);
    }

    /** Point de synchronisation du temps (source = serveur). */
    public function timer(Request $request, Attempt $attempt)
    {
        $this->authorize('view', $attempt);

        $current = $attempt->currentExercise();
        if (! $current) {
            return response()->json(['finished' => true, 'redirect' => route('results.show', $attempt)]);
        }

        $this->timer->sync($current);

        return response()->json([
            'finished' => $current->state()->isFinal(),
            'exercise_id' => $current->exercise_id,
            'timer' => $this->timer->display($current->refresh()),
            'redirect' => $current->state()->isFinal() ? route('exam.show', $attempt) : null,
        ]);
    }

    private function renderExercise(Attempt $attempt, AttemptExercise $attemptExercise)
    {
        $attemptExercise->load('section');

        $questions = $attemptExercise->formQuestions();
        $questions->load('answerOptions');
        $attemptExercise->exercise->loadMissing('media');

        $answers = $attempt->answers()
            ->where('attempt_exercise_id', $attemptExercise->id)
            ->get()
            ->mapWithKeys(fn ($a) => [$a->question_id => $a->decoded()]);

        $progress = $this->exam->progress($attempt);

        // Soumission Schreiben existante (reprise après coupure).
        $writing = null;
        if ($attemptExercise->exercise->skill === Skill::Schreiben) {
            $existing = WritingSubmission::query()
                ->where('attempt_id', $attempt->id)
                ->where('attempt_exercise_id', $attemptExercise->id)
                ->first();
            if ($existing) {
                $writing = [
                    'content' => (string) $existing->content,
                    'word_count' => (int) $existing->word_count,
                    'locked' => (bool) $existing->locked,
                ];
            }
        }

        // Position de la tâche au sein de sa section.
        $sectionExercises = $attempt->attemptExercises()
            ->where('section_id', $attemptExercise->section_id)
            ->orderBy('position')
            ->pluck('exercise_id')
            ->values();

        $indexInSection = $sectionExercises->search($attemptExercise->exercise_id);

        return view('candidate.exam.run', [
            'attempt' => $attempt,
            'attemptExercise' => $attemptExercise,
            'exercise' => $attemptExercise->exercise,
            'questions' => $questions,
            'answers' => $answers,
            'timer' => $this->timer->display($attemptExercise),
            'progress' => $progress,
            'indexInSection' => $indexInSection === false ? 0 : $indexInSection,
            'sectionCount' => $sectionExercises->count(),
            'skillSequence' => Skill::sequence(),
            'writing' => $writing,
        ]);
    }
}
