@extends('layouts.app')

@section('title', 'Suivi IA')

@section('content')
    @php
        $badge = [
            'ready' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'generating' => 'bg-amber-100 text-amber-800',
        ];
    @endphp

    <div class="flex items-center justify-between gap-4 mb-2">
        <h1 class="text-2xl font-bold">Suivi des générations IA</h1>
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Administration</a>
    </div>
    <p class="text-sm text-slate-500 mb-6">Défis IA (Espace Élite) : état, modèle utilisé et erreurs. Relancez une génération échouée sans accès au serveur.</p>

    @include('partials.flash')

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Générations aujourd'hui</div>
            <div class="text-2xl font-bold">{{ $usage }} <span class="text-base font-normal text-slate-400">/ {{ $quota }}</span></div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">En cours</div>
            <div class="text-2xl font-bold">{{ $pending }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Réussies (7 jours)</div>
            <div class="text-2xl font-bold text-green-700">{{ $ready7 }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-400">Échouées (7 jours)</div>
            <div class="text-2xl font-bold text-red-700">{{ $failed7 }}</div>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">#</th>
                        <th class="px-4 py-3 font-medium">Candidat</th>
                        <th class="px-4 py-3 font-medium">Niveau</th>
                        <th class="px-4 py-3 font-medium">Statut</th>
                        <th class="px-4 py-3 font-medium">Modèle</th>
                        <th class="px-4 py-3 font-medium">QCM</th>
                        <th class="px-4 py-3 font-medium">Demandé</th>
                        <th class="px-4 py-3 font-medium">Durée</th>
                        <th class="px-4 py-3 font-medium">Détail</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $challenge)
                        <tr class="border-t border-slate-100 align-top">
                            <td class="px-4 py-3 text-slate-400">{{ $challenge->id }}</td>
                            <td class="px-4 py-3">{{ $challenge->user?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $challenge->level }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium {{ $badge[$challenge->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $challenge->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $challenge->generated_with ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $counts[(string) $challenge->id] ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $challenge->requested_at?->diffForHumans() ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-500">
                                @if ($challenge->requested_at && $challenge->completed_at)
                                    {{ $challenge->requested_at->diffInSeconds($challenge->completed_at) }} s
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-500 max-w-xs">
                                @if ($challenge->status === 'failed' && $challenge->error)
                                    <span class="text-red-600">{{ \Illuminate\Support\Str::limit($challenge->error, 120) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($challenge->status !== 'generating')
                                    <form method="POST" action="{{ route('admin.ai.retry', $challenge) }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-slate-900 underline hover:no-underline">Relancer</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-6 text-center text-slate-400">Aucune génération enregistrée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
