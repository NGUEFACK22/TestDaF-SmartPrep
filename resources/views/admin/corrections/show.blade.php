@extends('layouts.app')

@section('title', 'Correction')

@section('content')
    <h1 class="text-2xl font-bold">Correction — {{ $submission->attemptExercise->exercise->title }}</h1>
    <p class="text-sm text-slate-500 mb-6">
        Candidat : {{ $submission->attempt->user?->name }} · Tentative #{{ $submission->attempt_id }}
    </p>

    <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <h2 class="font-semibold mb-2">Consigne</h2>
        <p class="text-sm text-slate-600 whitespace-pre-line">{{ $exercise->instruction }}</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-4">
        <h2 class="font-semibold mb-2">Réponse du candidat</h2>
        @if ($type === 'writing')
            <div class="text-sm whitespace-pre-line">{{ $submission->content }}</div>
        @else
            @if ($submission->media)
                <audio controls class="w-full" src="{{ route('media.stream', $submission->media) }}"></audio>
            @endif
            @if ($submission->transcript)
                <div class="text-sm mt-3"><strong>Transcription :</strong> {{ $submission->transcript }}</div>
            @endif
        @endif
    </div>

    @if ($ai)
        <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-4">
            <h2 class="font-semibold mb-2">Analyse IA ({{ $ai->status }})</h2>
            @if ($ai->feedback)
                <p class="text-sm text-slate-600 whitespace-pre-line">{{ $ai->feedback }}</p>
            @else
                <p class="text-sm text-slate-400">{{ $ai->error ?? 'Analyse en cours…' }}</p>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('admin.corrections.store', [$type, $submission->id]) }}"
          class="bg-white border border-slate-200 rounded-2xl p-5 mt-4 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Points (max {{ $exercise->points }})</label>
            <input name="points" type="number" step="0.5" min="0" value="{{ old('points', $existing?->points) }}" required
                   class="w-40 rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Commentaires</label>
            <textarea name="comments" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('comments', $existing?->comments) }}</textarea>
        </div>
        <button class="rounded-lg bg-blue-600 text-white px-5 py-2.5 font-medium hover:bg-blue-700">Valider la correction</button>
    </form>
@endsection