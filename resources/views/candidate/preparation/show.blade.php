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
                    @if ($skill->value === 'sprechen' && isset($sprechTargets[$key]))
                        <span class="ml-auto rounded-full bg-violet-50 px-2 py-0.5 text-xs font-medium text-violet-700">
                            ~{{ \App\Support\Format::clock($sprechTargets[$key]) }} de parole
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
        @if ($skill->value === 'sprechen')
            <p class="text-xs text-slate-400 mt-3">
                Temps de parole indicatifs par tâche ; chaque tâche dispose par ailleurs d'un temps de préparation
                qui fait partie de son déroulement.
            </p>
        @endif
    </div>

    @if ($skill->value === 'hoeren')
        <div class="bg-sky-50 border border-sky-200 rounded-2xl p-5 mt-6">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="font-semibold mb-1">🎧 Les 7 tâches officielles Hörverstehen (TestDaF digital)</h2>
                    <p class="text-sm text-slate-600">Écoutez et regardez les démos officielles du TestDaF Institute (matériel pédagogique public), une par type de tâche : consigne officielle, structure, durées, conseils C1 et ressources d'entraînement.</p>
                </div>
                <a href="{{ route('training.hoeren') }}" class="rounded-lg bg-sky-600 text-white px-4 py-2 text-sm font-medium hover:bg-sky-700 whitespace-nowrap">Accéder aux démos →</a>
            </div>
        </div>
    @endif


    @if (! empty($c1))
        <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6">
            <h2 class="font-semibold mb-1">{{ $c1['title'] ?? '' }}</h2>
            <p class="text-xs text-slate-400 mb-3">
                Objectif : {{ $c1Global['target']['label'] ?? 'TDN 5 (C1)' }}
                ({{ ($c1Global['target']['min'] ?? 16) }}–{{ ($c1Global['target']['max'] ?? 20) }} points / 20).
            </p>

            @if (! empty($c1['tasks']))
                <div class="mb-4">
                    <div class="text-sm font-medium text-slate-500 mb-1">Les tâches</div>
                    <ul class="text-sm space-y-1">
                        @foreach ($c1['tasks'] as $task)
                            <li class="flex items-start gap-2"><span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-blue-500 shrink-0"></span>{{ $task }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! empty($c1['competences']))
                <div class="mb-4">
                    <div class="text-sm font-medium text-slate-500 mb-1">Ce qu'on attend d'un candidat C1</div>
                    <ul class="text-sm space-y-1">
                        @foreach ($c1['competences'] as $item)
                            <li class="flex items-start gap-2"><span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-blue-500 shrink-0"></span>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! empty($c1['method']))
                <div class="mb-4">
                    <div class="text-sm font-medium text-slate-500 mb-1">Méthode</div>
                    <ol class="text-sm space-y-1 list-decimal list-inside">
                        @foreach ($c1['method'] as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ol>
                </div>
            @endif

            @if (! empty($c1['structure']))
                <div class="mb-4">
                    <div class="text-sm font-medium text-slate-500 mb-1">{{ $skill->value === 'schreiben' ? 'Structure conseillée' : 'Structure type du discours' }}</div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($c1['structure'] as $step)
                            <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs text-slate-600">{{ $step }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (! empty($c1['prefer']))
                <div class="mb-4">
                    <div class="text-sm font-medium text-slate-500 mb-1">Formulations à privilégier</div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($c1['prefer'] as $phrase)
                            <span class="rounded-lg bg-green-50 px-2.5 py-1 text-xs text-green-700">{{ $phrase }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (! empty($c1['connectors']))
                <div class="mb-4">
                    <div class="text-sm font-medium text-slate-500 mb-1">Connecteurs utiles</div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($c1['connectors'] as $word)
                            <span class="rounded-lg bg-amber-50 px-2.5 py-1 text-xs text-amber-700">{{ $word }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (! empty($c1['avoid']))
                <div class="mb-4">
                    <div class="text-sm font-medium text-slate-500 mb-1">À éviter</div>
                    <ul class="text-sm space-y-1">
                        @foreach ($c1['avoid'] as $item)
                            <li class="flex items-start gap-2"><span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-red-400 shrink-0"></span>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! empty($c1['evaluation']))
                <div class="mb-4">
                    <div class="text-sm font-medium text-slate-500 mb-1">Critères d'évaluation (niveau supérieur)</div>
                    <ul class="text-sm space-y-1">
                        @foreach ($c1['evaluation'] as $item)
                            <li class="flex items-start gap-2"><span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-blue-500 shrink-0"></span>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! empty($c1['tip']))
                <p class="rounded-xl bg-blue-50 p-3 text-xs text-blue-800">{{ $c1['tip'] }}</p>
            @endif
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6">
        <h2 class="font-semibold mb-3">Deux modes de travail</h2>
        <div class="grid md:grid-cols-2 gap-3 text-sm">
            <div class="rounded-xl bg-slate-50 p-3">
                <div class="font-medium mb-1">Mode apprentissage</div>
                <p class="text-slate-600">{{ $c1Global['modes']['training'] }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <div class="font-medium mb-1">Mode examen</div>
                <p class="text-slate-600">{{ $c1Global['modes']['exam'] }}</p>
            </div>
        </div>
        <p class="text-xs text-slate-400 mt-3">{{ $c1Global['exam_rule'] }}</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6">
        <h2 class="font-semibold mb-3">Progression C1 — 6 niveaux</h2>
        <ol class="text-sm space-y-2">
            @foreach ($c1Global['progression'] as $level => $description)
                <li class="flex items-start gap-2">
                    <span class="mt-0.5 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 whitespace-nowrap">{{ $level }}</span>
                    <span class="text-slate-600">{{ $description }}</span>
                </li>
            @endforeach
        </ol>
        <p class="text-xs text-slate-400 mt-3">{{ $c1Global['philosophy'] }}</p>
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