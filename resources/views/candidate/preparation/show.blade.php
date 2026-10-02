@extends('layouts.app')

@section('title', 'Préparation — ' . $skill->label())

@section('content')
    @php use App\Support\Format; @endphp

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold">Préparation — {{ $skill->label() }}</h1>
            <p class="text-sm text-slate-500">Cours, méthodes, conseils et exercices par difficulté.</p>
        </div>
        @if ($successRate !== null)
            <div class="text-right">
                <div class="text-xs text-slate-400">Réussite (20 dernières questions)</div>
                <div class="text-xl font-bold">{{ Format::percent($successRate, 0) }}</div>
            </div>
        @endif
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6">
        <h2 class="font-semibold mb-3">Types d'exercices (référence pédagogique TestDaF)</h2>
        <ul class="grid md:grid-cols-2 gap-2 text-sm">
            @foreach ($courses as $key => $label)
                <li class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                    <span>{{ $label }}</span>
                    <span class="text-xs text-slate-400">({{ $key }})</span>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="mt-6">
        <h2 class="font-semibold mb-3">Exercices disponibles par difficulté</h2>
        @foreach ($byDifficulty as $level => $exercises)
            <div class="mb-4">
                <div class="text-sm font-medium text-slate-500 mb-2">Niveau {{ $level }} — {{ $exercises->count() }} exercice(s)</div>
                <div class="grid md:grid-cols-3 gap-3">
                    @foreach ($exercises as $exercise)
                        <div class="bg-white border border-slate-200 rounded-xl p-4">
                            <div class="text-sm font-medium">{{ $exercise->title }}</div>
                            <div class="text-xs text-slate-400 mt-1">
                                {{ $exercise->type }} · {{ Format::clock($exercise->duration_seconds) }}
                            </div>
                        </div>
                    @endforeach
                    @if ($exercises->isEmpty())
                        <div class="text-sm text-slate-400">Aucun exercice à ce niveau.</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if ($skillResults->isNotEmpty())
        <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6">
            <h2 class="font-semibold mb-3">Historique {{ $skill->label() }}</h2>
            <ul class="text-sm space-y-2">
                @foreach ($skillResults as $result)
                    <li class="flex items-center justify-between border-b border-slate-100 last:border-0 py-1">
                        <span>{{ $result->created_at->format('d/m/Y') }}</span>
                        <span>{{ Format::number($result->points, 1) }} / {{ Format::number($result->max_points, 1) }}
                            — {{ Format::percent($result->percentage, 0) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection