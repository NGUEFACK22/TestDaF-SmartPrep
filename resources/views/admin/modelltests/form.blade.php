@extends('layouts.app')

@section('title', $modelltest->exists ? 'Modifier le Modelltest' : 'Créer un Modelltest')

@section('content')
    <h1 class="text-2xl font-bold">{{ $modelltest->exists ? 'Modifier — Modelltest '.$modelltest->number : 'Créer un Modelltest' }}</h1>

    <form method="POST"
          action="{{ $modelltest->exists ? route('admin.modelltests.update', $modelltest) : route('admin.modelltests.store') }}"
          class="mt-6 max-w-3xl space-y-4">
        @csrf
        @if ($modelltest->exists) @method('PUT') @endif

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Numéro</label>
                <input name="number" type="number" min="1" value="{{ old('number', $modelltest->number) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Statut</label>
                <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @foreach (['draft' => 'Brouillon', 'published' => 'Publié'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $modelltest->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Titre</label>
            <input name="title" type="text" value="{{ old('title', $modelltest->title) }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea name="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('description', $modelltest->description) }}</textarea>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Thème</label>
                <input name="theme" type="text" value="{{ old('theme', $modelltest->theme) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Difficulté</label>
                <select name="difficulty" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    @foreach (['A2', 'B1', 'B2', 'C1'] as $level)
                        <option value="{{ $level }}" @selected(old('difficulty', $modelltest->difficulty) === $level)>{{ $level }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($modelltest->exists)
            <div class="border-t border-slate-200 pt-4">
                <h2 class="font-semibold mb-3">Sections — ordre, durées, exercices</h2>
                @foreach ($skills as $skill)
                    @php $section = $modelltest->sections->firstWhere('skill', $skill->value); @endphp
                    <div class="mb-4 bg-slate-50 rounded-xl p-4">
                        <div class="font-medium">{{ $skill->label() }}
                            <span class="text-xs text-slate-400">({{ $section?->exercises->count() ?? 0 }} exercice(s))</span>
                        </div>
                        <input type="text" name="sections[{{ $skill->value }}]" value="{{ $section?->title ?? $skill->label() }}"
                               class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <div class="mt-2 text-xs font-medium text-slate-500">Exercices rattachés (ordre) :</div>
                        <select name="exercises[{{ $skill->value }}][]" multiple size="6"
                                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @php $attached = $section ? $section->exercises->pluck('id')->all() : []; @endphp
                            @foreach ($exercises->where('skill', $skill->value) as $exercise)
                                <option value="{{ $exercise->id }}" @selected(in_array($exercise->id, $attached))>
                                    #{{ $exercise->id }} — {{ $exercise->title }} ({{ $exercise->duration_seconds }}s)
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>
        @endif

        <button class="rounded-lg bg-blue-600 text-white px-5 py-2.5 font-medium hover:bg-blue-700">
            {{ $modelltest->exists ? 'Mettre à jour' : 'Créer' }}
        </button>
    </form>
@endsection