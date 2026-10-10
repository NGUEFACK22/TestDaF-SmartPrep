@extends('layouts.app')

@section('title', 'Préparation')

@section('content')
    @php
        use App\Http\Controllers\Candidate\PreparationController;
        use App\Support\Format;
        $aiLevels = PreparationController::AI_LEVELS;
    @endphp

    <h1 class="text-2xl font-bold">Préparation — QCM par niveau</h1>
    <p class="text-sm text-slate-500 mb-6">
        Choisissez votre niveau : les questions changent à chaque session mais restent calibrées
        sur le cadre du niveau. 100 % QCM écrit, chronométré.
    </p>

    <div class="grid md:grid-cols-3 sm:grid-cols-2 gap-4">
        @foreach ($levels as $level)
            @php
                $hasBank = $level['tests']->isNotEmpty();
                $hasIA = in_array($level['value'], $aiLevels, true);
            @endphp
            <div class="bg-white border rounded-2xl p-5 flex flex-col gap-3 card-hover
                        {{ ($hasBank || $hasIA) ? 'border-slate-200' : 'border-slate-200 opacity-70' }}">
                <div class="flex items-center justify-between">
                    <span class="text-3xl font-bold {{ ($hasBank || $hasIA) ? 'text-blue-700' : 'text-slate-400' }}">{{ $level['value'] }}</span>
                    @if ($hasBank)
                        <span class="rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                            Banque QCM
                        </span>
                    @endif
                    @if ($hasIA)
                        <span class="rounded-full bg-violet-50 border border-violet-200 px-2.5 py-0.5 text-xs font-medium text-violet-700">
                            IA inédite
                        </span>
                    @endif
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

                @if ($hasBank)
                    <form method="POST" action="{{ route('preparation.start', $level['value']) }}">
                        @csrf
                        <button type="submit"
                                class="w-full rounded-lg bg-blue-600 text-white px-4 py-2.5 text-sm font-semibold hover:bg-blue-700">
                            S'entraîner (banque {{ $level['value'] }})
                        </button>
                    </form>
                @endif

                @if ($hasIA)
                    <form method="POST" action="{{ route('preparation.generate', $level['value']) }}">
                        @csrf
                        <button type="submit"
                                class="w-full rounded-lg bg-violet-600 text-white px-4 py-2.5 text-sm font-semibold hover:bg-violet-700">
                            Générer une session IA inédite
                        </button>
                    </form>
                @endif

                @if (! $hasBank && ! $hasIA)
                    <p class="text-xs text-slate-400">Bientôt disponible.</p>
                @endif
            </div>
        @endforeach
    </div>

    @endsection
