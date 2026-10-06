@extends('layouts.app')

@section('title', 'Espace Élite — Défi IA')

@section('content')
    <h1 class="text-2xl font-bold">🏆 Espace Élite — Défi IA</h1>
    <p class="text-sm text-slate-500 mt-1">
        2 scores parfaits d'affilée sur un Modelltest débloquent cet espace.
        L'IA génère à chaque demande des QCM <strong>inédits</strong> (min {{ $minQuestions }} questions),
        calibrés sur vos faiblesses, dans le cadre TestDaF — avec minuteur par question selon la difficulté.
    </p>

    @if (session('status'))
        <p class="mt-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('status') }}</p>
    @endif

    {{-- Déblocages --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6">
        <h2 class="font-semibold mb-3">Vos déblocages (2× 100 % consécutifs)</h2>
        @forelse ($unlocks as $unlock)
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 last:border-0 py-3">
                <div>
                    <div class="font-medium">{{ $unlock->modellTest?->title ?? 'Modelltest' }}</div>
                    <div class="text-xs text-slate-400">Débloqué le {{ $unlock->unlocked_at?->format('d/m/Y H:i') }}</div>
                </div>
                <form method="POST" action="{{ route('challenges.store') }}">
                    @csrf
                    <input type="hidden" name="modell_test_id" value="{{ $unlock->modell_test_id }}">
                    <button class="rounded-lg bg-violet-600 text-white px-4 py-2 text-sm font-medium hover:bg-violet-700">
                        Générer mes {{ $minQuestions }} QCM inédits
                    </button>
                </form>
            </div>
        @empty
            <p class="text-sm text-slate-500">
                Pas encore débloqué : terminez 2 fois d'affilée le même Modelltest avec un score parfait (100 %).
                Vos essais actuels sont suivis automatiquement à chaque résultat.
            </p>
        @endforelse
    </div>

    {{-- Défi en cours --}}
    @if ($active)
        <div class="rounded-2xl border border-violet-200 bg-violet-50 p-5 mt-6" data-challenge-status data-status-url="{{ route('challenges.status', $active) }}">
            <h2 class="font-semibold text-violet-900">Défi en cours</h2>
            @if ($active->status === 'generating')
                <p class="text-sm text-violet-800 mt-1" data-challenge-label>Génération en cours… vos QCM inédits arrivent (restez sur la page, actualisation auto).</p>
            @elseif ($active->status === 'ready')
                <p class="text-sm text-violet-800 mt-1">Prêt ! QCM inédits calibrés sur vos faiblesses.</p>
                <a href="{{ route('challenges.play', $active) }}" class="inline-block mt-3 rounded-lg bg-violet-600 text-white px-5 py-2.5 font-semibold hover:bg-violet-700">
                    ▶ Jouer le défi
                </a>
            @elseif ($active->status === 'failed')
                <p class="text-sm text-red-700 mt-1">Échec : {{ $active->error }}</p>
            @endif
        </div>
        <script>
            (function () {
                var box = document.querySelector('[data-challenge-status]');
                if (!box) return;
                var label = box.querySelector('[data-challenge-label]');
                if (!label) return; // prêt ou échoué : pas de polling
                var timer = setInterval(async function () {
                    try {
                        var r = await fetch(box.dataset.statusUrl, { headers: { Accept: 'application/json' } });
                        var d = await r.json();
                        if (d.status === 'ready') {
                            clearInterval(timer);
                            window.location.reload();
                        } else if (d.status === 'failed') {
                            clearInterval(timer);
                            label.textContent = 'Échec : ' + (d.error || 'réessayez.');
                        }
                    } catch (e) { /* prochaine tentative */ }
                }, 8000);
            })();
        </script>
    @endif

    {{-- Historique --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6">
        <h2 class="font-semibold mb-3">Historique des défis</h2>
        @forelse ($history as $item)
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 last:border-0 py-2 text-sm">
                <span>
                    Défi #{{ $item->id }} — {{ $item->status === 'ready' ? 'prêt' : $item->status }}
                    <span class="text-slate-400">· demandé le {{ $item->requested_at?->format('d/m/Y H:i') }}</span>
                </span>
                @if ($item->status === 'ready')
                    <a href="{{ route('challenges.play', $item) }}" class="text-violet-600 hover:underline font-medium">Jouer / rejouer →</a>
                @endif
            </div>
        @empty
            <p class="text-sm text-slate-500">Aucun défi demandé pour l'instant.</p>
        @endforelse
    </div>
@endsection
