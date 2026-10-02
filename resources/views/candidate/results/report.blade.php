@extends('layouts.app')

@section('title', 'Rapport final')

@section('content')
    @php
        use App\Support\Format;
        $colors = ['lesen' => '#2563eb', 'hoeren' => '#0ea5e9', 'schreiben' => '#14b8a6', 'sprechen' => '#8b5cf6'];
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

    <h1 class="text-2xl font-bold">Rapport final — {{ $attempt->modellTest?->title }}</h1>
    <p class="text-sm text-slate-500 mb-6">
        Score global : {{ Format::number($attempt->score, 1) }} / {{ Format::number($attempt->max_score, 1) }}
        · Durée : {{ $attempt->duration_seconds ? Format::clock($attempt->duration_seconds) : '—' }}
    </p>

    <div class="grid md:grid-cols-4 gap-4">
        @foreach (\App\Enums\Skill::sequence() as $skill)
            @php $result = $results->get($skill->value); @endphp
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <div class="text-sm text-slate-400">{{ $skill->label() }}</div>
                <div class="text-2xl font-bold">{{ $result ? Format::percent($result->percentage, 0) : '—' }}</div>
                <div class="text-xs text-slate-400 mt-1">
                    {{ $result ? Format::number($result->points, 1).' / '.Format::number($result->max_points, 1) : 'Analyse en cours…' }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6">
        <h2 class="font-semibold mb-3">Erreurs fréquentes</h2>
        @if (! empty($weakTypes))
            <ul class="text-sm space-y-1">
                @foreach ($weakTypes as $row)
                    <li>{{ $row['type'] }} — {{ Format::percent($row['error_rate'], 0) }} d'erreurs
                        <span class="text-slate-400">({{ $row['errors'] }}/{{ $row['total'] }})</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-slate-500">Aucune erreur récurrente détectée.</p>
        @endif
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6">
        <h2 class="font-semibold mb-3">Évolution entre Modelltests</h2>
        @if (count($evolution) > 0)
            <div class="h-72"><canvas data-chart="line" data-series='@json($evoSeries)'></canvas></div>
        @else
            <p class="text-sm text-slate-500">Terminez d'autres Modelltests pour suivre votre évolution.</p>
        @endif
    </div>

    <div class="mt-6 flex gap-3">
        <a href="{{ route('results.show', $attempt) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100">Retour aux résultats</a>
        <a href="{{ route('modelltests.index') }}" class="rounded-lg bg-blue-600 text-white px-4 py-2 text-sm font-medium hover:bg-blue-700">Nouveau Modelltest</a>
    </div>

    <p class="text-xs text-slate-400 mt-6">
        Évaluation automatique à titre d'entraînement. Le rapport pourra ultérieurement être exporté en PDF.
    </p>
@endsection