<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Skill;
use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\Question;
use App\Services\Media\MediaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExerciseController extends Controller
{
    public function __construct(private MediaService $media) {}

    public function index()
    {
        return view('admin.exercises.index', [
            'exercises' => Exercise::withCount('questions')->orderBy('skill')->orderBy('position')->paginate(25),
        ]);
    }

    public function create()
    {
        return view('admin.exercises.form', [
            'exercise' => new Exercise(['skill' => Skill::Lesen, 'status' => 'draft', 'duration_seconds' => 180]),
            'questionTypes' => config('testdaf.question_types'),
            'exerciseTypes' => config('testdaf.exercise_types'),
            'skills' => Skill::sequence(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $exercise = Exercise::create($data);

        $this->attachMedia($request, $exercise);

        return redirect()->route('admin.exercises.edit', $exercise)->with('status', 'Exercice créé.');
    }

    public function edit(Exercise $exercise)
    {
        $exercise->load('questions.answerOptions', 'media');

        return view('admin.exercises.form', [
            'exercise' => $exercise,
            'questionTypes' => config('testdaf.question_types'),
            'exerciseTypes' => config('testdaf.exercise_types'),
            'skills' => Skill::sequence(),
        ]);
    }

    public function update(Request $request, Exercise $exercise)
    {
        $exercise->update($this->validated($request));
        $this->attachMedia($request, $exercise);
        $this->syncQuestions($request, $exercise);

        return back()->with('status', 'Exercice mis à jour.');
    }

    public function destroy(Exercise $exercise)
    {
        $exercise->delete();

        return redirect()->route('admin.exercises.index')->with('status', 'Exercice supprimé.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'skill' => ['required', Rule::in(array_column(Skill::cases(), 'value'))],
            'type' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'level' => ['required', 'in:A2,B1,B2,C1'],
            'difficulty' => ['required', 'in:A2,B1,B2,C1'],
            'instruction' => ['nullable', 'string'],
            'duration_seconds' => ['required', 'integer', 'min:10', 'max:7200'],
            'preparation_seconds' => ['nullable', 'integer', 'min:0', 'max:600'],
            'recording_seconds' => ['nullable', 'integer', 'min:0', 'max:600'],
            'points' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'position' => ['nullable', 'integer', 'min:0'],
            'content_text' => ['nullable', 'string'],
            'content_source_text' => ['nullable', 'string'],
            'solution_text' => ['nullable', 'string'],
            'explanation' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $data['content'] = array_filter([
            'text' => $request->input('content_text'),
            'source_text' => $request->input('content_source_text'),
        ], fn ($v) => $v !== null && $v !== '');

        unset($data['content_text'], $data['content_source_text']);

        return $data;
    }

    private function attachMedia(Request $request, Exercise $exercise): void
    {
        foreach (['audio' => 'audio', 'video' => 'video', 'image' => 'image'] as $field => $kind) {
            if ($request->hasFile($field)) {
                $this->media->storeContentFile($request->file($field), $kind, $exercise);
            }
        }
    }

    /** Synchronise les questions / réponses correctes (saisie simplifiée). */
    private function syncQuestions(Request $request, Exercise $exercise): void
    {
        $rows = $request->input('questions', []);

        if (! is_array($rows) || $rows === []) {
            return;
        }

        foreach (array_values($rows) as $position => $row) {
            if (empty($row['prompt'])) {
                continue;
            }

            $question = Question::updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'exercise_id' => $exercise->id,
                    'type' => $row['type'] ?? 'single_choice',
                    'position' => $position,
                    'prompt' => $row['prompt'],
                    'points' => $row['points'] ?? 1,
                    'correct_answer' => $this->normalizeCorrect($row['correct'] ?? null),
                    'explanation' => $row['explanation'] ?? null,
                ]
            );

            if (! empty($row['options'])) {
                $labels = ['A', 'B', 'C', 'D', 'E', 'F'];
                foreach (array_values($row['options']) as $i => $option) {
                    if ($option === null || $option === '') {
                        continue;
                    }
                    $question->answerOptions()->updateOrCreate(
                        ['label' => $labels[$i] ?? (string) $i],
                        ['text' => $option, 'position' => $i]
                    );
                }
            }
        }
    }

    private function normalizeCorrect(mixed $correct): ?array
    {
        if ($correct === null || $correct === '') {
            return null;
        }

        $values = is_array($correct) ? $correct : explode(',', (string) $correct);

        return array_values(array_map('trim', $values));
    }
}
