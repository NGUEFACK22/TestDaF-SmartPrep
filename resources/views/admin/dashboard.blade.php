@extends('layouts.app')

@section('title', 'Administration')

@section('content')
    @php use App\Support\Format; @endphp

    <h1 class="text-2xl font-bold">Administration</h1>
    <p class="text-sm text-slate-500 mb-6">Pilotage de la plateforme : utilisateurs, niveaux QCM, défis IA, statistiques.</p>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Utilisateurs</div>
            <div class="text-2xl font-bold">{{ $users }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Tests de niveau publiés</div>
            <div class="text-2xl font-bold">{{ $levelTracks }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Exercices QCM</div>
            <div class="text-2xl font-bold">{{ $exercises }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Tentatives en cours</div>
            <div class="text-2xl font-bold">{{ $attemptsInProgress }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Espaces Élite débloqués</div>
            <div class="text-2xl font-bold">{{ $eliteUnlocks }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Défis IA prêts / échoués</div>
            <div class="text-2xl font-bold">{{ $challengesReady }} / {{ $challengesFailed }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Score moyen / compétence</div>
            @foreach ($overview['skill_average'] as $skill => $avg)
                <div class="text-xs">{{ $skill }} : <strong>{{ Format::percent($avg, 0) }}</strong></div>
            @endforeach
        </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-4 mt-6">
        <a href="{{ route('admin.exercises.index') }}" class="rounded-2xl bg-slate-900 text-white text-center px-4 py-3 text-sm font-medium hover:bg-slate-700">Exercices</a>
        <a href="{{ route('admin.users.index') }}" class="rounded-2xl bg-slate-900 text-white text-center px-4 py-3 text-sm font-medium hover:bg-slate-700">Utilisateurs</a>
        <a href="{{ route('admin.settings.edit') }}" class="rounded-2xl bg-slate-900 text-white text-center px-4 py-3 text-sm font-medium hover:bg-slate-700">Paramètres IA</a>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6">
        <h2 class="font-semibold mb-3">Activité récente</h2>
        <ul class="text-sm space-y-2">
            @foreach ($recentAttempts as $attempt)
                <li class="flex justify-between border-b border-slate-100 last:border-0 py-1">
                    <span>{{ $attempt->user?->name }} — {{ $attempt->modellTest?->title }}</span>
                    <span class="text-slate-400">{{ $attempt->status }} · {{ $attempt->created_at->diffForHumans() }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endsection