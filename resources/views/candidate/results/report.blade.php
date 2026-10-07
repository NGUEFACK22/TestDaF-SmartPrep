@extends('layouts.app')

@section('title', 'Rapport final')

@section('content')
    @php
        use App\Support\Format;
        $colors = ['lesen' => '#2563eb'];
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
    @if ($grade20 !== null)
        <p class="mt-1">
            <span class="text-2xl font-bold text-slate-900">Note : {{ Format::number($grade20, 1) }} / 20</span>
            <span class="ml-2 text-xs text-slate-500">moyenne des parties corrigées (échelle 0–20 du TestDaF)</span>
        </p>
    @endif
    <p class="text-sm text-slate-500 mb-6 mt-1">
        Score global : {{ Format::number($attempt->score, 1) }} / {{ Format::number($attempt->max_score, 1) }}
        · Durée : {{ $attempt->duration_seconds ? Format::clock($attempt->duration_seconds) : '—' }}
    </p>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach (\App\Enums\Skill::sequence() as $skill)
            @php $result = $results->get($skill->value); @endphp
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <div class="text-sm text-slate-400">{{ $skill->label() }}</div>
                <div class="text-2xl font-bold">{{ $result ? Format::percent($result->percentage, 0) : '—' }}</div>
                <div class="text-xs text-slate-400 mt-1">
                    {{ $result ? Format::number($result->points, 1).' / '.Format::number($result->max_points, 1).' points · '.Format::number($result->points20, 1).' / 20' : 'Analyse en cours…' }}
                </div>
                @if ($result)
                    <div class="mt-2 inline-block rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                        TDN estimé : {{ $result->tdn }}
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <p class="text-xs text-slate-400 mt-2">
        Le TestDaF évalue chaque partie séparément (pas de note globale officielle).
        Échelle 0–20 : 0–4 sous TDN 3 · 5–9 TDN 3 · 10–15 TDN 4 · 16–20 TDN 5.
        Estimation pédagogique — jamais une note officielle TestDaF.
    </p>

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
        <a href="{{ route('preparation.index') }}" class="rounded-lg bg-blue-600 text-white px-4 py-2 text-sm font-medium hover:bg-blue-700">Nouvelle session</a>
    </div>

    <p class="text-xs text-slate-400 mt-6">
        Évaluation automatique à titre d'entraînement. Le rapport pourra ultérieurement être exporté en PDF.
    </p>
@endsection