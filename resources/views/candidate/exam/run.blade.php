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
        'endpoints' => [
            'answers' => route('api.answers.store', $attempt),
            'timer' => route('exam.timer', $attempt),
            'event' => route('exam.event', $attempt),
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
    <div class="mx-auto max-w-5xl px-4 py-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
        <div class="flex items-center gap-2 sm:gap-4 min-w-0">
            <span class="font-bold tracking-wide">SYNPHONIE</span>
            <span class="text-slate-300">·</span>
            <span class="font-medium truncate">{{ $skill->label() }}</span>
            <span class="text-slate-400 text-sm whitespace-nowrap">Aufgabe {{ $indexInSection + 1 }} von {{ $sectionCount }}</span>
        </div>
        <div class="text-right hidden sm:block">
            <div class="text-xs text-slate-400">
                Frage <span id="question-progress" class="font-mono text-slate-200">{{ $currentQuestionIndex + 1 }} / {{ $totalQuestions }}</span>
            </div>
            @if ($timed)
                <div class="font-mono text-xl" data-question-timer-label>--:--</div>
            @endif
        </div>
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

    <div class="flex items-center justify-between mb-2 text-sm text-slate-500">
        <span>Tentative #{{ $attempt->id }} — {{ $attempt->modellTest?->title }}</span>
        <span id="save-state">Prêt</span>
    </div>

    @php
        $done = (int) ($progress['completed'] ?? 0);
        $total = max(1, (int) ($progress['total'] ?? 1));
        $step = min($total, $done + 1);
        $pct = $total > 0 ? round(($done / $total) * 100) : 0;
    @endphp
    <div class="mb-4 rounded-xl border border-slate-200 bg-white px-4 py-2.5">
        <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5">
            <span>Étape {{ $step }} / {{ $total }} du Modelltest</span>
            <span>{{ $done }} tâche(s) terminée(s)</span>
        </div>
        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full rounded-full bg-blue-600 progress-animated" style="width: {{ $pct }}%"></div>
        </div>
    </div>

    <div id="answer-warning" class="hidden mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 font-medium"></div>

    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 mb-4">
        <h1 class="font-semibold">{{ $exercise->title }}</h1>
        @if ($exercise->instruction)
            <p class="text-sm text-slate-600 mt-2 whitespace-pre-line">{{ $exercise->instruction }}</p>
        @endif
        <p class="text-xs text-blue-800 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2 mt-3">📖 1. Lisez le texte ci-dessous. 2. Répondez à chaque Frage (n° bleu). 3. Cliquez SUIVANT après chaque réponse — WEITER sur la dernière. Sans réponse = 0 point.</p>
        @if (! empty($exercise->content['text']))
            <div class="mt-4 bg-white border border-slate-200 rounded-xl p-4 text-sm leading-relaxed whitespace-pre-line">{{ $exercise->content['text'] }}</div>
        @endif
    </div>

    <div id="questions-container"></div>

    <div class="flex justify-stretch sm:justify-end mt-6">
        <button type="button" id="btn-weiter"
            class="btn-exam w-full sm:w-auto rounded-lg bg-blue-600 text-white px-8 py-3 font-semibold hover:bg-blue-700">
            WEITER
        </button>
    </div>
</main>

<script>
    window.__EXAM__ = @json($config);
</script>
</body>
</html>