@extends('layouts.app')

@section('title', 'Modelltests')

@section('content')
    <h1 class="text-2xl font-bold">Modelltests</h1>
    <p class="text-sm text-slate-500 mb-6">10 Modelltests complets : Lesen → Hören → Schreiben → Sprechen → Lösung.</p>

    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 mb-6 text-sm text-blue-900">
        <strong>Attention :</strong> le mode Modelltest reproduit les conditions d'un examen numérique —
        chronométrage individuel par tâche, ordre imposé, aucune marche arrière, verrouillage à l'expiration.
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        @foreach ($tests as $test)
            @php $attempt = $attempts->get($test->id); @endphp
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs text-slate-400">Modelltest {{ $test->number }}</div>
                        <div class="text-lg font-semibold">{{ $test->title }}</div>
                    </div>
                    <span class="text-xs rounded-full px-2 py-1 {{ $test->status === 'published' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                        {{ $test->status === 'published' ? 'Publié' : 'Brouillon' }}
                    </span>
                </div>

                <p class="text-sm text-slate-500 mt-2">{{ $test->description }}</p>

                <div class="text-xs text-slate-400 mt-3 flex gap-4">
                    <span>{{ $test->sections_count }} sections</span>
                    <span>Niveau {{ $test->difficulty }}</span>
                    @if ($test->total_duration_seconds)
                        <span>{{ \App\Support\Format::clock($test->total_duration_seconds) }}</span>
                    @endif
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <form method="POST" action="{{ route('modelltests.start', $test) }}">
                        @csrf
                        <input type="hidden" name="mode" value="exam">
                        <button class="rounded-lg bg-blue-600 text-white px-4 py-2 text-sm font-medium hover:bg-blue-700"
                                onclick="return confirm('Commencer le Modelltest en conditions d\'examen ?');">
                            {{ $attempt && $attempt->status === 'in_progress' ? 'Reprendre le Modelltest' : 'Commencer le Modelltest' }}
                        </button>
                    </form>

                    @if ($attempt && $attempt->status === 'completed')
                        <a href="{{ route('results.show', $attempt) }}" class="text-sm text-blue-600 hover:underline">Voir mes résultats</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if ($tests->isEmpty())
        <p class="text-slate-500">Aucun Modelltest publié pour le moment.</p>
    @endif
@endsection