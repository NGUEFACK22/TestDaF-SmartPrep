@extends('layouts.app')

@section('title', 'Préparation')

@section('content')
    @php use App\Support\Format; @endphp

    <h1 class="text-2xl font-bold">Préparation</h1>
    <p class="text-sm text-slate-500 mb-6">
        Choisissez votre niveau pour commencer un test, ou réviser par compétence.
    </p>

    <div class="grid md:grid-cols-3 sm:grid-cols-2 gap-4">
        @foreach ($levels as $level)
            @php($hasTests = $level['tests']->isNotEmpty())
            <div class="bg-white border rounded-2xl p-5 flex flex-col gap-3
                        {{ $hasTests ? 'border-slate-200 hover:border-blue-300' : 'border-slate-200 opacity-70' }}">
                @if ($hasTests)
                    <form id="start-{{ strtolower($level['value']) }}" method="POST"
                          action="{{ route('preparation.start', $level['value']) }}">
                        @csrf
                        {{-- Le libellé du niveau est lui-même un bouton : un clic
                             sur la carte lance immédiatement le test du niveau. --}}
                        <div class="flex items-center justify-between">
                            <button type="submit" form="start-{{ strtolower($level['value']) }}"
                                    class="text-3xl font-bold text-blue-700 hover:text-blue-900 cursor-pointer"
                                    title="Lancer le test {{ $level['value'] }}">
                                {{ $level['value'] }}
                            </button>
                            <span class="rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                {{ $level['tests']->count() }} test{{ $level['tests']->count() > 1 ? 's' : '' }}
                            </span>
                        </div>

                        <p class="text-sm text-slate-500 flex-1">{{ $level['description'] }}</p>

                        @if ($level['last_result'])
                            <div class="text-xs text-slate-500">
                                Dernier score :
                                <strong class="text-slate-800">{{ Format::percent($level['last_result']['percentage'], 0) }}</strong>
                                · {{ $level['last_result']['date']->format('d/m/Y') }}
                                <a class="text-blue-600 hover:underline" href="{{ route('results.show', $level['last_result']['attempt_id']) }}">Voir</a>
                            </div>
                        @endif

                        <button type="submit"
                                class="w-full rounded-lg bg-blue-600 text-white px-4 py-2.5 text-sm font-semibold hover:bg-blue-700">
                            Commencer le test {{ $level['value'] }}
                        </button>
                    </form>

                    <a href="{{ route('modelltests.index') }}"
                       class="text-center text-xs text-slate-400 hover:text-blue-600 hover:underline">
                        Choisir un test précis
                    </a>
                @else
                    <div class="flex items-center justify-between">
                        <span class="text-3xl font-bold text-slate-400">{{ $level['value'] }}</span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">À venir</span>
                    </div>

                    <p class="text-sm text-slate-500 flex-1">{{ $level['description'] }}</p>
                @endif
            </div>
        @endforeach
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-5 mt-6">
        <h2 class="font-semibold mb-3">Réviser par compétence</h2>
        <div class="grid md:grid-cols-4 gap-3">
            @foreach (\App\Enums\Skill::sequence() as $skill)
                <a href="{{ route('preparation.show', $skill->value) }}"
                   class="rounded-xl border border-slate-200 p-4 hover:border-blue-300">
                    <div class="font-medium">{{ $skill->label() }}</div>
                    <div class="text-xs text-slate-500 mt-1">Méthode C1, exercices par difficulté, progression.</div>
                </a>
            @endforeach
        </div>
    </div>
@endsection