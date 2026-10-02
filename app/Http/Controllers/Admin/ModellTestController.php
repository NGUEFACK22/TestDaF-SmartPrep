<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Skill;
use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\ModellTest;
use App\Models\Section;
use App\Models\User;
use App\Notifications\ModellTestAvailable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ModellTestController extends Controller
{
    public function index()
    {
        return view('admin.modelltests.index', [
            'modelltests' => ModellTest::withCount('sections')->orderBy('number')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('admin.modelltests.form', [
            'modelltest' => new ModellTest(['number' => 1, 'status' => 'draft', 'difficulty' => 'B2']),
            'skills' => Skill::sequence(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $modellTest = ModellTest::create($data + ['created_by' => $request->user()->id]);

        $this->syncSections($modellTest, $request);

        return redirect()->route('admin.modelltests.edit', $modellTest)
            ->with('status', 'Modelltest créé.');
    }

    public function edit(ModellTest $modelltest)
    {
        $modelltest->load('sections.exercises');

        return view('admin.modelltests.form', [
            'modelltest' => $modelltest,
            'skills' => Skill::sequence(),
            'exercises' => Exercise::orderBy('skill')->orderBy('position')->get(),
        ]);
    }

    public function show(ModellTest $modelltest)
    {
        return redirect()->route('admin.modelltests.edit', $modelltest);
    }

    public function update(Request $request, ModellTest $modelltest)
    {
        $modelltest->update($this->validated($request, $modelltest));
        $this->syncSections($modelltest, $request);
        $this->syncExercises($request, $modelltest);

        return back()->with('status', 'Modelltest mis à jour.');
    }

    public function destroy(ModellTest $modelltest)
    {
        $modelltest->delete();

        return redirect()->route('admin.modelltests.index')->with('status', 'Modelltest supprimé.');
    }

    private function validated(Request $request, ?ModellTest $modellTest = null): array
    {
        return $request->validate([
            'number' => ['required', 'integer', 'min:1', 'max:999', Rule::unique('modell_tests', 'number')->ignore($modellTest?->id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'theme' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['required', 'in:A2,B1,B2,C1'],
            'status' => ['required', 'in:draft,published'],
        ]);
    }

    private function syncSections(ModellTest $modellTest, Request $request): void
    {
        $titles = $request->input('sections', []);

        foreach (Skill::sequence() as $index => $skill) {
            $title = $titles[$skill->value] ?? $skill->label();

            Section::updateOrCreate(
                ['modell_test_id' => $modellTest->id, 'skill' => $skill->value],
                ['title' => $title, 'position' => $index]
            );
        }
    }

    private function syncExercises(Request $request, ModellTest $modellTest): void
    {
        $map = $request->input('exercises', []); // [skill => [exercise_id,...]]

        foreach ($modellTest->sections as $section) {
            $ids = $map[$section->skill->value] ?? [];

            $sync = [];
            foreach (array_values($ids) as $position => $exerciseId) {
                $sync[$exerciseId] = ['position' => $position];
            }

            $section->exercises()->sync($sync);
        }

        // Recalcule la durée totale.
        $modellTest->update([
            'total_duration_seconds' => $modellTest->fresh('sections.exercises')->computedDuration(),
        ]);

        // Notification si le test devient publié.
        if (($data['status'] ?? null) === 'published') {
            $this->notifyPublished($modellTest);
        }
    }

    private function notifyPublished(ModellTest $modellTest): void
    {
        foreach (User::where('status', 'active')->cursor() as $user) {
            if ($user->notifications()->where('data->type', 'modelltest_available')->where('data->modell_test_id', $modellTest->id)->exists()) {
                continue;
            }

            try {
                $user->notify(new ModellTestAvailable($modellTest->id, $modellTest->number, $modellTest->title));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
