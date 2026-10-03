@extends('layouts.app')

@section('title', 'Hören — les 7 tâches officielles')

@section('content')
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold">Hören — les 7 tâches officielles du TestDaF digital</h1>
            <p class="text-sm text-slate-500">Démos officielles (matériel pédagogique public du TestDaF Institute).</p>
        </div>
        <a href="{{ route('preparation.show', 'hoeren') }}" class="text-sm text-blue-600 hover:underline">← Préparation Hören</a>
    </div>

    <div class="bg-sky-50 border border-sky-200 rounded-2xl p-5 mt-6 text-sm text-sky-900">
        <div class="font-semibold mb-2">Comment s'entraîner</div>
        <ol class="list-decimal ml-5 space-y-1">
            <li>Lisez la consigne officielle et la structure de la tâche <em>avant</em> l'écoute.</li>
            <li><strong>1ʳᵉ écoute</strong> : sans pause, comme le jour J (le texte est joué une seule fois).</li>
            <li><strong>2ᵉ écoute</strong> : avec pauses, pour analyser la structure du discours.</li>
            <li>Enchaînez avec les ressources C1 (ÖSD, telc, Goethe) pour le volume d'entraînement.</li>
        </ol>
        <p class="text-xs text-sky-700 mt-2">Entraînement non noté, sans chronomètre. Les durées « ≈ » sont indicatives (calibrées sur le fichier de démo).</p>
    </div>

    @if ($demos->isEmpty())
        <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6 text-sm text-slate-500">
            Aucune démo officielle n'a encore été importée (php artisan db:seed --class=HoerenDemoSeeder).
        </div>
    @endif

    <div class="grid md:grid-cols-2 gap-4 mt-6">
        @foreach ($demos as $demo)
            @php
                $media = $demo->media->first();
                $isVideo = $media !== null && $media->type === 'video';
            @endphp
            <a href="{{ route('training.show', $demo) }}" class="block bg-white border border-slate-200 rounded-2xl p-5 hover:border-sky-400 hover:shadow-sm transition group">
                <div class="flex items-center gap-3">
                    <span class="h-10 w-10 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center font-bold shrink-0">
                        {{ $demo->content['demo_number'] ?? '?' }}
                    </span>
                    <div class="min-w-0">
                        <div class="font-medium group-hover:text-sky-700">{{ $demo->title }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">{{ $demo->content['title_de'] ?? '' }}</div>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2 flex-wrap text-xs text-slate-500">
                    <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $isVideo ? '🎬 Vidéo' : '🎧 Audio' }}</span>
                    @if (! empty($demo->content['duration_hint']))
                        <span>{{ $demo->content['duration_hint'] }}</span>
                    @endif
                    @if (! empty($demo->content['control_time']))
                        <span>· vérif. officielle {{ $demo->content['control_time'] }}</span>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
@endsection