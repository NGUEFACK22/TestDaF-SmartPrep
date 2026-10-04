@extends('layouts.app')

@section('title', 'Entraînement — ' . $exercise->title)

@section('content')
    @php
        $isDemo = ! empty($exercise->content['demo']);
        $media = $exercise->media->first();
    @endphp

    <h1 class="text-2xl font-bold">{{ $exercise->skill->label() }} — {{ $exercise->title }}</h1>
    <p class="text-sm text-slate-500 mb-4">Mode entraînement : sans chronomètre, correction immédiate.</p>

    @if ($isDemo && ! empty($exercise->content['official_instruction']))
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mb-4">
            <div class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-2">
                Consigne officielle (TestDaF-Demo · Aufgabe {{ $exercise->content['demo_number'] ?? '' }} von 7)
            </div>
            <div class="text-sm leading-relaxed whitespace-pre-line text-slate-800">{{ $exercise->content['official_instruction'] }}</div>
        </div>
    @endif

    @if ($exercise->instruction)
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 mb-4 text-sm whitespace-pre-line">{{ $exercise->instruction }}</div>
    @endif

    @if ($media !== null && in_array($media->type, ['audio', 'video'], true))
        <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4">
            @if ($media->type === 'audio')
                <audio controls preload="metadata" class="w-full" src="{{ route('media.stream', $media) }}"></audio>
            @else
                <video controls preload="metadata" class="w-full rounded-xl bg-slate-900" src="{{ route('media.stream', $media) }}"></video>
            @endif
            <div class="text-xs text-slate-400 mt-2">
                {{ $media->original_name }} — {{ round($media->size / 1048576, 1) }} Mo ·
                <a href="{{ route('media.download', $media) }}" class="text-blue-600 hover:underline">Télécharger</a>
            </div>
        </div>
    @endif

    @if (! empty($exercise->content['text']))
        <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 text-sm leading-relaxed whitespace-pre-line">{{ $exercise->content['text'] }}</div>
    @endif

    @if ($isDemo)
        @if (! empty($exercise->content['structure']))
            <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4">
                <div class="text-sm font-medium text-slate-500 mb-2">Ce qu'il faut faire dans cette tâche</div>
                <ul class="text-sm space-y-1">
                    @foreach ($exercise->content['structure'] as $item)
                        <li class="flex items-start gap-2"><span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-sky-500 shrink-0"></span>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! empty($exercise->content['duration_hint']) || ! empty($exercise->content['control_time']))
            <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-600">
                @if (! empty($exercise->content['duration_hint']))
                    <span>🕐 {{ $exercise->content['duration_hint'] }}</span>
                @endif
                @if (! empty($exercise->content['control_time']))
                    <span>✅ Vérification officielle : {{ $exercise->content['control_time'] }}</span>
                @endif
            </div>
        @endif

        @if (! empty($exercise->content['tips']))
            <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4">
                <div class="text-sm font-medium text-slate-500 mb-2">Conseils C1</div>
                <ul class="text-sm space-y-1">
                    @foreach ($exercise->content['tips'] as $tip)
                        <li class="flex items-start gap-2"><span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-sky-500 shrink-0"></span>{{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! empty($exercise->content['resources']))
            <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4">
                <div class="text-sm font-medium text-slate-500 mb-2">Ressources pour s'entraîner (ouverture dans un nouvel onglet)</div>
                <ul class="text-sm space-y-1">
                    @foreach ($exercise->content['resources'] as $resource)
                        <li class="flex items-start gap-2">
                            <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-sky-500 shrink-0"></span>
                            <a href="{{ $resource['url'] }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline break-all">{{ $resource['name'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif

    @if (! $exercise->skill->isProductive())
        <div class="space-y-3">
            @foreach ($exercise->questions as $question)
                <div class="bg-white border border-slate-200 rounded-2xl p-5">
                    <div class="font-medium text-sm">{{ $question->prompt }}</div>
                    <div class="mt-3 space-y-2">
                        @foreach ($question->answerOptions as $option)
                            <div class="text-sm border rounded-lg px-3 py-2 {{ $option->is_correct ? 'border-green-400 bg-green-50' : 'border-slate-200' }}">
                                <strong>{{ $option->label }}</strong> — {{ $option->text }}
                                @if ($option->is_correct) <span class="text-green-700 font-medium">✓</span> @endif
                            </div>
                        @endforeach
                        @if (! empty($question->data['segments']) && is_array($question->correct_answer))
                            <div class="text-sm leading-relaxed bg-slate-50 border border-slate-200 rounded-lg p-3">
                                @foreach ($question->data['segments'] as $seg)
                                    @if (isset($seg['t']))
                                        {{ $seg['t'] }}
                                    @else
                                        @php $isCorrectWord = in_array(mb_strtolower($seg['id']), $question->correct_answer, true); @endphp
                                        <span class="rounded border px-1.5 py-0.5 mx-0.5 {{ $isCorrectWord ? 'border-green-400 bg-green-50 text-green-800 font-medium' : 'border-slate-300' }}">{{ $seg['w'] }}@if($isCorrectWord) ✓@endif</span>
                                    @endif
                                @endforeach
                            </div>
                            <div class="text-xs text-slate-500 mt-1">Mots à marquer : {{ implode(' · ', $question->correct_answer) }}</div>
                        @elseif ($question->answerOptions->isEmpty() && is_array($question->correct_answer))
                            <div class="text-sm text-green-700">Bonne réponse : <strong>{{ implode(', ', $question->correct_answer) }}</strong></div>
                        @endif
                    </div>
                    @if ($question->explanation)
                        <div class="text-sm text-slate-500 mt-2">Pourquoi : {{ $question->explanation }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="text-sm text-slate-500">Solution modèle (Lösung) :</div>
            <div class="text-sm mt-2 whitespace-pre-line">{{ $exercise->solution_text ?? '—' }}</div>
            @if ($exercise->explanation)
                <div class="text-sm text-slate-500 mt-3">Conseil : {{ $exercise->explanation }}</div>
            @endif
        </div>
    @endif

    <div class="mt-6">
        @if ($isDemo)
            <a href="{{ route('training.hoeren') }}" class="text-sm text-blue-600 hover:underline">← Les 7 tâches officielles Hören</a>
        @else
            <a href="{{ route('preparation.show', $exercise->skill->value) }}" class="text-sm text-blue-600 hover:underline">← Retour à la préparation</a>
        @endif
    </div>
@endsection