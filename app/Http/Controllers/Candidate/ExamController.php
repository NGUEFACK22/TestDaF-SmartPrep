<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\ExerciseState;
use App\Enums\Skill;
use App\Exceptions\ExamException;
use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\AttemptExercise;
use App\Services\Exam\ExamService;
use App\Services\Exam\QuestionTimerService;
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
        private QuestionTimerService $questionTimer,
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
            // `redirect` est TOUJOURS défini : c'est la destination à suivre.
            // Tant qu'une partie suivante existe, on retourne vers la page
            // d'examen (jamais vers les résultats avant la vraie fin).
            $target = $next ? route('exam.show', $attempt) : route('results.show', $attempt);

            return response()->json([
                'ok' => true,
                'completed' => true,
                'next' => $next ? [
                    'exercise_id' => $next->exercise_id,
                    'redirect' => $target,
                ] : null,
                'finished' => $next === null,
                'redirect' => $target,
            ]);
        }

        return $next
            ? redirect()->route('exam.show', $attempt)
            : redirect()->route('results.show', $attempt);
    }

    /**
     * Passe à la question suivante (bouton SUIVANT) : verrouille la question
     * courante, démarre le timer de la suivante, ou finalise la tâche si
     * c'était la dernière question. Le serveur reste l'autorité.
     */
    public function next(Request $request, Attempt $attempt, AttemptExercise $attemptExercise)
    {
        $this->authorize('interact', $attempt);

        abort_unless($attemptExercise->attempt_id === $attempt->id, 404);

        $from = $request->input('from_index');
        $fromIndex = ($from === null || $from === '') ? null : (int) $from;

        $result = $this->exam->advanceQuestion($attempt, $attemptExercise, $fromIndex);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true] + $result);
        }

        return isset($result['redirect'])
            ? redirect($result['redirect'])
            : redirect()->route('exam.show', $attempt);
    }

    /**
     * Événement de proctoring léger (changement d'onglet, perte de focus,
     * copier-coller). Informatif uniquement : ne bloque jamais le candidat,
     * mais alimente exam_logs pour l'audit et la détection d'anomalies.
     * Throttle 60/min côté route.
     */
    public function event(Request $request, Attempt $attempt)
    {
        $this->authorize('interact', $attempt);

        $validated = $request->validate([
            'event' => 'required|string|max:50|in:tab_hidden,tab_visible,focus_lost,focus_gained,paste,copy,fullscreen_exit',
            'exercise_id' => 'nullable|integer',
        ]);

        \App\Models\ExamLog::create([
            'user_id' => $request->user()->id,
            'attempt_id' => $attempt->id,
            'event' => 'proctoring_'.$validated['event'],
            'payload' => [
                'exercise_id' => $validated['exercise_id'] ?? $attempt->current_exercise_id,
            ],
            'ip' => $request->ip(),
        ]);

        return response()->json(['ok' => true]);
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
        $this->questionTimer->sync($current->refresh());

        $current = $current->refresh();

        if ($current->state()->isFinal()) {
            return response()->json([
                'finished' => true,
                'exercise_id' => $current->exercise_id,
                'redirect' => route('exam.show', $attempt),
            ]);
        }

        // Dernière question chronométrée dépassée : le serveur clôture la tâche.
        $closed = $this->exam->closeIfLastQuestionExpired($attempt, $current);
        if ($closed !== null) {
            return response()->json([
                'finished' => true,
                'exercise_id' => $current->exercise_id,
                'redirect' => $closed['redirect'] ?? route('exam.show', $attempt),
            ]);
        }

        return response()->json([
            'finished' => false,
            'exercise_id' => $current->exercise_id,
            'timer' => $this->timer->display($current),
            'question_timer' => $this->questionTimer->display($current),
            'question' => $this->exam->questionPayload($current, $attempt),
            'redirect' => null,
        ]);
    }

    private function renderExercise(Attempt $attempt, AttemptExercise $attemptExercise)
    {
        $attemptExercise->load('section');

        $questions = $attemptExercise->formQuestions();
        $questions->load('answerOptions');
        // Brassage des réponses : même ordre que le JSON (même graine).
        $this->exam->applyOptionShuffle($questions, $attempt);

        // Timer par question : le client ne reçoit que la question en cours
        // (les questions futures restent côté serveur).
        $questionTimerData = $this->questionTimer->display($attemptExercise);
        if ($questionTimerData['enabled']) {
            $currentQuestion = $attemptExercise->currentQuestion();
            $questions = collect($currentQuestion ? [$currentQuestion] : []);
        }

        $answers = $attempt->answers()
            ->where('attempt_exercise_id', $attemptExercise->id)
            ->get()
            ->mapWithKeys(fn ($a) => [$a->question_id => $a->decoded()]);

        $progress = $this->exam->progress($attempt);

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
            'questionTimer' => $questionTimerData,
            'currentQuestionIndex' => $attemptExercise->questionIndex(),
            'totalQuestions' => $attemptExercise->formQuestions()->count(),
            'progress' => $progress,
            'indexInSection' => $indexInSection === false ? 0 : $indexInSection,
            'sectionCount' => $sectionExercises->count(),
            'skillSequence' => Skill::sequence(),
        ]);
    }
}
