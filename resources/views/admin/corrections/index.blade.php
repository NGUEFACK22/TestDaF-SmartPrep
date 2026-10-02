@extends('layouts.app')

@section('title', 'Corrections')

@section('content')
    <h1 class="text-2xl font-bold">Corrections manuelles</h1>
    <p class="text-sm text-slate-500 mb-6">Schreiben et Sprechen soumis par les candidats.</p>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <h2 class="font-semibold mb-3">Schreiben ({{ $writing->count() }})</h2>
            <ul class="text-sm space-y-2">
                @forelse ($writing as $submission)
                    <li class="flex items-center justify-between border-b border-slate-100 last:border-0 py-1">
                        <span>{{ $submission->attempt->user?->name }} — {{ $submission->attemptExercise->exercise->title }}</span>
                        <a href="{{ route('admin.corrections.show', ['writing', $submission->id]) }}" class="text-blue-600 hover:underline">Corriger</a>
                    </li>
                @empty
                    <li class="text-slate-400">Aucune soumission.</li>
                @endforelse
            </ul>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <h2 class="font-semibold mb-3">Sprechen ({{ $speaking->count() }})</h2>
            <ul class="text-sm space-y-2">
                @forelse ($speaking as $submission)
                    <li class="flex items-center justify-between border-b border-slate-100 last:border-0 py-1">
                        <span>{{ $submission->attempt->user?->name }} — {{ $submission->attemptExercise->exercise->title }}</span>
                        <a href="{{ route('admin.corrections.show', ['speaking', $submission->id]) }}" class="text-blue-600 hover:underline">Corriger</a>
                    </li>
                @empty
                    <li class="text-slate-400">Aucune soumission.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6">
        <h2 class="font-semibold mb-3">Corrections récentes</h2>
        <ul class="text-sm space-y-2">
            @foreach ($recent as $correction)
                <li class="border-b border-slate-100 last:border-0 py-1">
                    {{ $correction->corrector?->name }} — {{ $correction->points }} pts
                    <span class="text-slate-400">({{ $correction->corrected_at?->diffForHumans() }})</span>
                </li>
            @endforeach
        </ul>
    </div>
@endsection