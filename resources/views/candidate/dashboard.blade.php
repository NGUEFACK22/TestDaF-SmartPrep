@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
    @php
        use App\Support\Format;
        $colors = ['lesen' => '#2563eb', 'hoeren' => '#0ea5e9', 'schreiben' => '#14b8a6', 'sprechen' => '#8b5cf6'];
        $barSeries = [
            'labels' => collect($skillProgress)->pluck('label')->values(),
            'values' => collect($skillProgress)->pluck('percentage')->map(fn ($v) => (float) $v)->values(),
        ];
        $evoDatasets = [];
        foreach (\App\Enums\Skill::sequence() as $s) {
            $evoDatasets[] = [
                'label' => $s->label(),
                'color' => $colors[$s->value],
                'data' => collect($evolution)->pluck($s->value)->map(fn ($v) => (float) $v)->values(),
            ];
        }
        $evoSeries = ['labels' => collect($evolution)->pluck('label')->values(), 'datasets' => $evoDatasets];
    @endphp

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold">Bonjour {{ auth()->user()->name }}</h1>
            <p class="text-sm text-slate-500">Votre progression vers le TestDaF digital.</p>
        </div>
        <a href="{{ route('modelltests.index') }}" class="rounded-lg bg-blue-600 text-white px-4 py-2.5 font-medium hover:bg-blue-700">
            Nouveau Modelltest
        </a>
    </div>

    @if ($activeAttempt)
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 flex items-center justify-between flex-wrap gap-3">
            <div>
                <div class="font-semibold text-amber-900">Tentative en cours — {{ $activeAttempt->modellTest?->title }}</div>
                <div class="text-sm text-amber-800">Reprenez là où vous vous êtes arrêté. Le temps serveur continue d'être respecté.</div>
            </div>
            <a href="{{ route('exam.show', $activeAttempt) }}" class="rounded-lg bg-amber-600 text-white px-4 py-2 font-medium hover:bg-amber-700">
                Reprendre
            </a>
        </div>
    @endif

    <div class="grid md:grid-cols-4 gap-4 mt-6">
        @foreach ($skillProgress as $key => $skill)
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <div class="text-sm text-slate-400">{{ $skill['label'] }}</div>
                <div class="text-2xl font-bold text-slate-800">{{ Format::percent($skill['percentage'], 0) }}</div>
                <div class="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full" style="width: {{ min(100, $skill['percentage']) }}%; background-color: {{ $colors[$key] }}"></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid md:grid-cols-2 gap-6 mt-6">
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <h2 class="font-semibold mb-4">Progression par compétence</h2>
            <div class="h-64"><canvas data-chart="bar" data-series='@json($barSeries)'></canvas></div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <h2 class="font-semibold mb-4">Évolution entre Modelltests</h2>
            <div class="h-64">
                @if (count($evolution) > 0)
                    <canvas data-chart="line" data-series='@json($evoSeries)'></canvas>
                @else
                    <p class="text-sm text-slate-500">Terminez un Modelltest pour voir votre courbe d'évolution.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-4 mt-6">
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Tests commencés</div>
            <div class="text-2xl font-bold">{{ $overview['started'] }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Tests terminés</div>
            <div class="text-2xl font-bold">{{ $overview['completed'] }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Score moyen</div>
            <div class="text-2xl font-bold">{{ Format::number($overview['average'], 1) }}</div>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6">
        <h2 class="font-semibold mb-4">Exercices recommandés</h2>
        @forelse ($recommendations as $rec)
            <div class="border-b border-slate-100 last:border-0 py-3">
                <div class="font-medium">{{ $rec->title }}</div>
                <div class="text-sm text-slate-500">{{ $rec->description }}</div>
            </div>
        @empty
            <p class="text-sm text-slate-500">Aucune recommandation pour l'instant. Complétez des exercices pour en recevoir.</p>
        @endforelse
    </div>
@endsection