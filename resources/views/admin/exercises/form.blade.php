@extends('layouts.app')

@section('title', $exercise->exists ? 'Modifier l\'exercice' : 'Créer un exercice')

@section('content')
    <h1 class="text-2xl font-bold">{{ $exercise->exists ? 'Modifier l\'exercice' : 'Créer un exercice' }}</h1>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $exercise->exists ? route('admin.exercises.update', $exercise) : route('admin.exercises.store') }}"
          class="mt-6 max-w-3xl space-y-4">
        @csrf
        @if ($exercise->exists) @method('PUT') @endif

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Compétence</label>
                <select name="skill" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @foreach ($skills as $skill)
                        <option value="{{ $skill->value }}" @selected(old('skill', $exercise->skill->value) === $skill->value)>
                            {{ $skill->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Type d'exercice</label>
                <input name="type" type="text" list="exercise-types" value="{{ old('type', $exercise->type) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <datalist id="exercise-types">
                    @foreach ($exerciseTypes as $skillTypes)
                        @foreach ($skillTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    @endforeach
                </datalist>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Titre</label>
            <input name="title" type="text" value="{{ old('title', $exercise->title) }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Consigne (Aufgabenstellung)</label>
            <textarea name="instruction" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('instruction', $exercise->instruction) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Texte / source</label>
            <textarea name="content_text" rows="6" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Texte de l'exercice (Lesetext)">{{ old('content_text', $exercise->content['text'] ?? '') }}</textarea>
        </div>

        <div class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Durée (s)</label>
                <input name="duration_seconds" type="number" min="10" max="7200" value="{{ old('duration_seconds', $exercise->duration_seconds) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Points</label>
                <input name="points" type="number" step="0.5" min="0" value="{{ old('points', $exercise->points) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Niveau</label>
                <select name="level" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @foreach (['A2', 'B1', 'B2', 'C1'] as $level)
                        <option value="{{ $level }}" @selected(old('level', $exercise->level) === $level)>{{ $level }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Statut</label>
                <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @foreach (['draft' => 'Brouillon', 'published' => 'Publié'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $exercise->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Texte source (Schreiben)</label>
            <textarea name="content_source_text" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('content_source_text', $exercise->content['source_text'] ?? '') }}</textarea>
        </div>

        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Audio</label>
                <input name="audio" type="file" accept="audio/*" class="text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Vidéo</label>
                <input name="video" type="file" accept="video/*" class="text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Image / graphique</label>
                <input name="image" type="file" accept="image/*" class="text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Solution (Lösung)</label>
            <textarea name="solution_text" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('solution_text', $exercise->solution_text) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Explication pédagogique</label>
            <textarea name="explanation" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('explanation', $exercise->explanation) }}</textarea>
        </div>

        <button class="rounded-lg bg-blue-600 text-white px-5 py-2.5 font-medium hover:bg-blue-700">
            {{ $exercise->exists ? 'Mettre à jour' : 'Créer' }}
        </button>

    @if ($exercise->exists && $exercise->questions->isNotEmpty())
        <div class="mt-8">
            <h2 class="font-semibold mb-3">Questions ({{ $exercise->questions->count() }})</h2>
            @foreach ($exercise->questions as $question)
                <div class="bg-slate-50 rounded-xl p-4 mb-3">
                    <div class="text-sm font-medium">{{ $question->prompt }} <span class="text-xs text-slate-400">({{ $question->type }})</span></div>
                    <input type="hidden" name="questions[{{ $loop->index }}][id]" value="{{ $question->id }}">
                    <input type="text" name="questions[{{ $loop->index }}][prompt]" value="{{ $question->prompt }}"
                           class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input type="text" name="questions[{{ $loop->index }}][correct]"
                           value="{{ is_array($question->correct_answer) ? implode(', ', $question->correct_answer) : '' }}"
                           placeholder="Bonne réponse (séparées par des virgules)"
                           class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            @endforeach
            <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100">Mettre à jour les questions</button>
        </div>
    @endif
    </form>
@endsection