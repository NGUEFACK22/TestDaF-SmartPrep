@extends('layouts.app')

@section('title', 'Entraînement — ' . $exercise->title)

@section('content')
    <h1 class="text-2xl font-bold">{{ $exercise->skill->label() }} — {{ $exercise->title }}</h1>
    <p class="text-sm text-slate-500 mb-4">Mode entraînement : sans chronomètre, correction immédiate.</p>

    @if ($exercise->instruction)
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 mb-4 text-sm whitespace-pre-line">{{ $exercise->instruction }}</div>
    @endif

    @if (! empty($exercise->content['text']))
        <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-4 text-sm leading-relaxed whitespace-pre-line">{{ $exercise->content['text'] }}</div>
    @endif

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
                        @if ($question->answerOptions->isEmpty() && is_array($question->correct_answer))
                            <div class="text-sm text-green-700">Bonne réponse : <strong>{{ implode(', ', $question->correct_answer) }}</strong></div>
                        @endif
                    </div>
                    @if ($question->explanation)
                        <div class="text-sm text-slate-500 mt-2">Pourquoi : {{ $question->explanation }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    <div class="mt-6">
        <a href="{{ route('preparation.index') }}" class="btn text-blue-600 hover:bg-blue-100 hover:underline">← Retour à la préparation</a>
    </div>
@endsection