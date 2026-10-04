@extends('layouts.app')

@section('title', 'Résultats')

@section('content')
    @php use App\Support\Format; @endphp

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold">{{ $attempt->modellTest?->title }}</h1>
            <p class="text-sm text-slate-500">
                Terminé le {{ $attempt->completed_at?->format('d/m/Y H:i') }} —
                Score global : {{ Format::number($attempt->score, 1) }} / {{ Format::number($attempt->max_score, 1) }}
            </p>
        </div>
        <a href="{{ route('results.report', $attempt) }}" class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-700">
            Rapport complet
        </a>
    </div>

    @if ($aiPending > 0)
        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Les productions nécessitant une analyse sont en cours de traitement.
            Les résultats Schreiben / Sprechen apparaîtront ici dès leur analyse.
            <a href="{{ route('results.show', $attempt) }}" class="font-medium underline">Actualiser</a>
        </div>
    @endif

    <div class="grid md:grid-cols-2 gap-4 mt-6">
        @foreach (\App\Enums\Skill::sequence() as $skill)
            @php $result = $results->get($skill->value); @endphp
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold">{{ $skill->label() }}</h2>
                    @if ($result)
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium rounded-full bg-slate-100 px-2.5 py-0.5 text-slate-600">{{ Format::number($result->points20, 1) }} / 20</span>
                            <span class="text-lg font-bold">{{ Format::percent($result->percentage, 0) }}</span>
                        </div>
                    @else
                        <span class="text-sm text-slate-400">Analyse en cours…</span>
                    @endif
                </div>
                @if ($result)
                    <div class="flex items-center justify-between mt-1">
                        <p class="text-sm text-slate-500">
                            {{ Format::number($result->points, 1) }} / {{ Format::number($result->max_points, 1) }} points
                        </p>
                        <p class="text-sm text-slate-700">TDN estimé : <strong>{{ $result->tdn }}</strong></p>
                    </div>
                    <div class="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full bg-blue-600" style="width: {{ min(100, $result->percentage) }}%"></div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <p class="text-xs text-slate-400 mt-2">
        Échelle 0–20 du TestDaF appliquée séparément à chaque partie (0–4 sous TDN 3 · 5–9 TDN 3 · 10–15 TDN 4 · 16–20 TDN 5).
        Objectif C1 : 16–20 points sur chaque compétence. Estimation pédagogique — jamais une note officielle TestDaF.
    </p>

    <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6">
        <h2 class="font-semibold mb-3">Correction détaillée (Lesen / Hören)</h2>
        @forelse ($attempt->attemptExercises as $ae)
            @if (! $ae->exercise->skill->isProductive() && $ae->formQuestions()->isNotEmpty())
                <div class="mb-5">
                    <div class="font-medium">{{ $ae->exercise->title }}
                        <span class="text-xs text-slate-400">— {{ Format::number($ae->score, 1) }}/{{ Format::number($ae->max_score, 1) }}</span>
                    </div>
                    @foreach ($ae->formQuestions() as $question)
                        @php $answer = $attempt->answers->firstWhere('question_id', $question->id); @endphp
                        <div class="mt-2 text-sm border-l-2 pl-3 {{ $answer?->is_correct ? 'border-green-400' : 'border-red-300' }}">
                            <div class="text-slate-700">{{ $question->prompt }}</div>
                            @if ($question->isObjective() && $question->correct_answer)
                                <div class="text-slate-500">
                                    Votre réponse : <strong>{{ is_array($answer?->decoded()) ? implode(', ', $answer->decoded()) : ($answer?->answer ?? '—') }}</strong>
                                    · Bonne réponse : <strong>{{ is_array($question->correct_answer) ? implode(', ', $question->correct_answer) : $question->correct_answer }}</strong>
                                </div>
                            @endif
                            @if ($question->explanation)
                                <div class="text-slate-500 mt-1">Pourquoi : {{ $question->explanation }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        @empty
            <p class="text-sm text-slate-500">Aucun détail disponible.</p>
        @endforelse
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6">
        <h2 class="font-semibold mb-3">Analyses IA (indicatif — entraînement uniquement)</h2>
        @forelse ($attempt->writingSubmissions->concat($attempt->speakingSubmissions) as $submission)
            @foreach ($submission->aiEvaluations as $evaluation)
                <div class="mb-4 text-sm">
                    <div class="font-medium">{{ ucfirst($evaluation->skill) }}
                        <span class="text-xs text-slate-400">— {{ $evaluation->status }}@if ($evaluation->indicatorScore() !== null) · score indicatif {{ Format::number($evaluation->indicatorScore(), 1) }}/20 @endif</span>
                    </div>
                    @if ($evaluation->feedback)
                        <p class="text-slate-600 mt-1 whitespace-pre-line">{{ $evaluation->feedback }}</p>
                    @elseif ($evaluation->error)
                        <p class="text-amber-700 mt-1">Analyse en attente ({{ $evaluation->error }})</p>
                    @else
                        <p class="text-slate-500 mt-1">Analyse en cours…</p>
                    @endif
                </div>
            @endforeach
        @empty
            <p class="text-sm text-slate-500">Aucune analyse disponible.</p>
        @endforelse
    </div>
@endsection