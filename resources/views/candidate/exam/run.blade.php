@php
    use App\Support\Format;
    $skill = $exercise->skill;
    $questionCount = $questions->count();
    $timed = (bool) ($questionTimer['enabled'] ?? false);

    $config = [
        'attemptId' => $attempt->id,
        'attemptExerciseId' => $attemptExercise->id,
        'exercise' => [
            'id' => $exercise->id,
            'skill' => $skill->value,
            'type' => $exercise->type,
            'title' => $exercise->title,
            'preparation_seconds' => (int) $exercise->preparation_seconds,
            'recording_seconds' => (int) $exercise->recording_seconds,
            'locked' => ! $attemptExercise->acceptsAnswers(),
        ],
        'questions' => $questions->map(fn ($q) => [
            'id' => $q->id,
            'type' => $q->type,
            'prompt' => $q->prompt,
            'points' => (float) $q->points,
            'data' => $q->data,
            'answer_options' => $q->answerOptions->map(fn ($o) => [
                'id' => $o->id,
                'label' => $o->label,
                'text' => $o->text,
            ])->values(),
        ])->values(),
        'answers' => $answers,
        'timer' => $timer,
        'questionTimer' => $questionTimer,
        'currentQuestionIndex' => (int) ($currentQuestionIndex ?? 0),
        'totalQuestions' => (int) ($totalQuestions ?? $questionCount),
        'autosaveInterval' => (int) config('testdaf.exam.autosave_interval', 10),
        'writing' => $writing,
        'endpoints' => [
            'answers' => route('api.answers.store', $attempt),
            'writing' => route('api.writing.store', $attempt),
            'speaking' => route('api.speaking.store', $attempt),
            'timer' => route('exam.timer', $attempt),
            'complete' => route('exam.complete', [$attempt, $attemptExercise]),
            'next' => route('exam.next', [$attempt, $attemptExercise]),
            // Fallback de sécurité : la page d'examen (le serveur décide ;
            // elle redirige elle-même vers les résultats si la tentative est
            // vraiment terminée, jamais l'inverse).
            'redirect' => route('exam.show', $attempt),
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $skill->label() }} — Aufgabe {{ $indexInSection + 1 }} von {{ $sectionCount }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="exam-shell" data-exam-root
      data-expires-at="{{ $timer['expires_at'] }}"
      data-server-now="{{ $timer['server_now'] }}"
      data-duration="{{ $timer['duration_seconds'] }}"
      data-warning="{{ config('testdaf.exam.warning_seconds', 30) }}">

<header class="bg-slate-900 text-white sticky top-0 z-10">
    <div class="mx-auto max-w-5xl px-4 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <span class="font-bold tracking-wide">TESTDAF</span>
            <span class="text-slate-300">·</span>
            <span class="font-medium">{{ $skill->label() }}</span>
            <span class="text-slate-400 text-sm">Aufgabe {{ $indexInSection + 1 }} von {{ $sectionCount }}</span>
        </div>
        @if ($timed)
            <div class="text-right hidden sm:block">
                <div class="text-xs text-slate-400">
                    Frage <span id="question-progress" class="font-mono text-slate-200">{{ $currentQuestionIndex + 1 }} / {{ $totalQuestions }}</span>
                </div>
                <div class="font-mono text-xl" data-question-timer-label>--:--</div>
            </div>
        @endif
        <div class="text-right">
            <div class="text-xs text-slate-400">Temps restant</div>
            <div class="font-mono text-xl" data-timer-label>{{ Format::clock($timer['remaining_seconds']) }}</div>
        </div>
    </div>
    <div class="h-1.5 bg-slate-700">
        <div class="exam-timer-bar h-full bg-blue-500" data-timer-bar style="width: 100%"></div>
    </div>
    @if ($timed)
        <div class="h-1 bg-slate-700">
            <div class="h-full bg-emerald-400 transition-all" data-question-timer-bar style="width: 100%"></div>
        </div>
    @endif
</header>

<main class="mx-auto max-w-5xl px-4 py-6">
    <div id="expired-banner" class="hidden mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800 font-medium">
        Die Bearbeitungszeit ist abgelaufen.
    </div>

    <div class="flex items-center justify-between mb-4 text-sm text-slate-500">
        <span>Tentative #{{ $attempt->id }} — {{ $attempt->modellTest?->title }}</span>
        <span id="save-state">Prêt</span>
    </div>

    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 mb-4">
        <h1 class="font-semibold">{{ $exercise->title }}</h1>
        @if ($exercise->instruction)
            <p class="text-sm text-slate-600 mt-2 whitespace-pre-line">{{ $exercise->instruction }}</p>
        @endif
        @if ($skill === \App\Enums\Skill::Sprechen && isset(config('testdaf.sprechen.targets')[$exercise->type]))
            <p class="text-xs text-violet-700 bg-violet-50 rounded-lg px-3 py-1.5 mt-2 inline-block">
                Objectif : ~{{ Format::clock(config('testdaf.sprechen.targets')[$exercise->type]) }} de parole
                (+ {{ Format::clock(config('testdaf.sprechen.prep_seconds_default', 60)) }} de préparation)
            </p>
        @endif
        @if (! empty($exercise->content['text']))
            <div class="mt-4 bg-white border border-slate-200 rounded-xl p-4 text-sm leading-relaxed whitespace-pre-line">{{ $exercise->content['text'] }}</div>
        @endif
        @if (! empty($exercise->content['source_text']))
            <div class="mt-4 bg-white border border-slate-200 rounded-xl p-4 text-sm leading-relaxed whitespace-pre-line">{{ $exercise->content['source_text'] }}</div>
        @endif
        @foreach ($exercise->media as $media)
            @if ($media->type === 'audio')
                <audio class="mt-4 w-full" controls preload="metadata" src="{{ route('media.stream', $media) }}"></audio>
            @elseif ($media->type === 'video')
                <video class="mt-4 w-full rounded-xl" controls preload="metadata" src="{{ route('media.stream', $media) }}"></video>
            @elseif (in_array($media->type, ['image', 'graph']))
                <img class="mt-4 max-h-96 rounded-xl" src="{{ route('media.stream', $media) }}" alt="Grafik">
            @endif
        @endforeach
        @if (! empty($exercise->content['chart']))
            <div class="mt-4 bg-white border border-slate-200 rounded-xl p-4">
                @if (! empty($exercise->content['chart']['title']))
                    <h3 class="text-sm font-semibold mb-1">{{ $exercise->content['chart']['title'] }}</h3>
                @endif
                <div class="relative h-56">
                    <canvas data-chart="bar"
                            data-series='@json(['labels' => $exercise->content['chart']['labels'] ?? [], 'values' => $exercise->content['chart']['values'] ?? []])'></canvas>
                </div>
            </div>
        @endif
    </div>

    {{-- ------------------------------------------------ Lesen / Hören --}}
    @if (! $skill->isProductive())
        <div id="questions-container"></div>
    @endif

    {{-- --------------------------------------------------------- Schreiben --}}
    @if ($skill === \App\Enums\Skill::Schreiben)
        <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="flex items-center justify-between mb-2">
                <h2 class="font-semibold">Ihr Text</h2>
                <span class="text-sm text-slate-500">Wörter: <strong id="word-count">0</strong></span>
            </div>
            <textarea id="writing-editor" rows="16"
                class="w-full rounded-xl border border-slate-300 p-4 text-sm leading-relaxed focus:border-blue-500 focus:ring-blue-500"
                placeholder="Schreiben Sie hier Ihren Text…"></textarea>
            <div id="writing-locked" class="hidden mt-2 text-sm text-red-600">
                Le temps est écoulé : le texte ne peut plus être modifié.
            </div>
        </div>
    @endif

    {{-- --------------------------------------------------------- Sprechen --}}
    @if ($skill === \App\Enums\Skill::Sprechen)
        <div id="speaking-panel" class="bg-white border border-slate-200 rounded-2xl p-5">
            <p id="speaking-error" class="hidden mb-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700"></p>
            <p id="speaking-status" class="text-sm text-slate-500 mb-4"></p>

            <div id="speaking-preparation" class="hidden text-center py-8">
                <div class="text-sm text-slate-500">Vorbereitungszeit</div>
                <div class="text-4xl font-bold text-blue-600" id="prep-countdown">--</div>
            </div>

            <div id="speaking-recording" class="text-center py-8">
                <div class="text-sm text-slate-500">Aufnahme</div>
                <div class="text-4xl font-bold text-red-600 recording-pulse">●</div>
                <div class="text-sm text-slate-500 mt-2">Verbleibend: <span id="rec-countdown">--</span></div>
            </div>

            <div id="speaking-review" class="hidden">
                <audio id="speaking-playback" class="w-full hidden" controls></audio>
                <button type="button" id="btn-record-again" class="mt-3 rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100">
                    Nouvel enregistrement
                </button>
            </div>
        </div>
    @endif

    <div class="flex justify-end mt-6">
        <button type="button" id="btn-weiter"
            class="rounded-lg bg-blue-600 text-white px-8 py-3 font-semibold hover:bg-blue-700">
            WEITER
        </button>
    </div>
</main>

<script>
    window.__EXAM__ = @json($config);
</script>
</body>
</html>