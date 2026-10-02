@extends('layouts.app')

@section('title', 'Administration')

@section('content')
    @php use App\Support\Format; @endphp

    <h1 class="text-2xl font-bold">Administration</h1>
    <p class="text-sm text-slate-500 mb-6">Pilotage de la plateforme : utilisateurs, contenus, corrections, statistiques.</p>

    <div class="grid md:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Utilisateurs</div>
            <div class="text-2xl font-bold">{{ $users }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Modelltests</div>
            <div class="text-2xl font-bold">{{ $published }} / {{ $modelltests }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Tentatives en cours</div>
            <div class="text-2xl font-bold">{{ $attemptsInProgress }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Corrections en attente</div>
            <div class="text-2xl font-bold">{{ $pendingCorrections }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Analyses IA terminées</div>
            <div class="text-2xl font-bold">{{ $aiCompleted }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Erreurs IA</div>
            <div class="text-2xl font-bold">{{ $aiFailed }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Score moyen / compétence</div>
            @foreach ($overview['skill_average'] as $skill => $avg)
                <div class="text-xs">{{ $skill }} : <strong>{{ Format::percent($avg, 0) }}</strong></div>
            @endforeach
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Stockage média</div>
            <div class="text-2xl font-bold">{{ $mediaCount }}</div>
            <div class="text-xs text-slate-400">{{ round($mediaSize / 1048576, 1) }} Mo</div>
        </div>
    </div>

    <div class="grid md:grid-cols-4 gap-4 mt-6">
        <a href="{{ route('admin.modelltests.index') }}" class="rounded-2xl bg-slate-900 text-white text-center px-4 py-3 text-sm font-medium hover:bg-slate-700">Modelltests</a>
        <a href="{{ route('admin.exercises.index') }}" class="rounded-2xl bg-slate-900 text-white text-center px-4 py-3 text-sm font-medium hover:bg-slate-700">Exercices</a>
        <a href="{{ route('admin.corrections.index') }}" class="rounded-2xl bg-slate-900 text-white text-center px-4 py-3 text-sm font-medium hover:bg-slate-700">Corrections</a>
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