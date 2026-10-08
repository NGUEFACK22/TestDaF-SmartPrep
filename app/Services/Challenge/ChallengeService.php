<?php

namespace App\Services\Challenge;

use App\Enums\AttemptStatus;
use App\Enums\Skill;
use App\Models\AiChallenge;
use App\Models\AnswerOption;
use App\Models\Attempt;
use App\Models\ChallengeUnlock;
use App\Models\Exercise;
use App\Models\ModellTest;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use App\Notifications\ChallengeReady;
use App\Notifications\ChallengeUnlocked;
use App\Models\Setting;
use App\Services\AI\GeminiService;
use App\Services\Statistics\StatisticsService;
use Illuminate\Support\Facades\DB;

/**
 * ChallengeService — Espace Élite (Défi IA).
 *
 * 1. Détection : 2 scores parfaits consécutifs sur le même Modelltest
 *    → unlock + notification (vérifié à chaque finishAttempt).
 * 2. Génération : QCM inédits calibrés sur les faiblesses analysées
 *    (min 20 questions, chrono par question selon la difficulté).
 * 3. Matérialisation : vrai ModellTest (draft, invisible de la liste)
 *    joué avec le moteur d'examen standard (autorité serveur, anti-cheat).
 */
class ChallengeService
{
    public function __construct(
        private GeminiService $gemini,
        private StatisticsService $statistics,
    ) {}

    /** Quota journalier de générations IA (Setting admin, défaut .env). */
    public function quotaLimit(): int
    {
        try {
            return (int) Setting::get('max_ai_requests', config('testdaf.ai.max_requests', 100));
        } catch (\Throwable) {
            return (int) config('testdaf.ai.max_requests', 100);
        }
    }

    public function dailyUsage(): int
    {
        return AiChallenge::where('requested_at', '>=', now()->startOfDay())->count();
    }

    public function quotaExceeded(): bool
    {
        return $this->dailyUsage() >= $this->quotaLimit();
    }

    // ---------------------------------------------------------- éligibilité

    public function isPerfect(Attempt $attempt): bool
    {
        $max = (float) ($attempt->max_score ?? 0);

        return $max > 0 && (float) ($attempt->score ?? 0) >= $max - 1e-9;
    }

    public function isUnlocked(User $user, ModellTest $test): bool
    {
        return ChallengeUnlock::where('user_id', $user->id)
            ->where('modell_test_id', $test->id)
            ->exists();
    }

    /** Vérifie le double 100 % et débloque (appelé à chaque tentative terminée). */
    public function checkAndUnlock(Attempt $attempt): ?ChallengeUnlock
    {
        $attempt->loadMissing(['user', 'modellTest']);

        // Les tests générés (draft) ne redébloquent jamais l'espace.
        if ($attempt->modellTest && $attempt->modellTest->status !== 'published') {
            return null;
        }

        if ($attempt->status === AttemptStatus::Completed
            && $this->isPerfect($attempt)
            && ! $this->isUnlocked($attempt->user, $attempt->modellTest)) {
            $required = (int) config('testdaf.challenge.consecutive_perfect', 2);

            $last = Attempt::where('user_id', $attempt->user_id)
                ->where('modell_test_id', $attempt->modell_test_id)
                ->where('status', AttemptStatus::Completed->value)
                ->orderByDesc('id')
                ->take($required)
                ->get();

            if ($last->count() === $required
                && $last->every(fn (Attempt $a) => $this->isPerfect($a))) {
                $unlock = ChallengeUnlock::create([
                    'user_id' => $attempt->user_id,
                    'modell_test_id' => $attempt->modell_test_id,
                    'unlocked_at' => now(),
                ]);

                try {
                    $attempt->user->notify(new ChallengeUnlocked(
                        $attempt->modell_test_id,
                        $attempt->modellTest?->title ?? 'Modelltest',
                    ));
                } catch (\Throwable $e) {
                    report($e);
                }

                return $unlock;
            }
        }

        return null;
    }

    // ---------------------------------------------------------- génération

    public function activeChallenge(User $user): ?AiChallenge
    {
        return AiChallenge::where('user_id', $user->id)
            ->whereIn('status', ['generating', 'ready'])
            ->latest('id')
            ->first();
    }

    /**
     * Demande une génération (file d'attente). Lance une exception métier
     * si non éligible, quota dépassé ou défi déjà en cours.
     */
    public function requestGeneration(User $user, ModellTest $test): AiChallenge
    {
        if (! $this->isUnlocked($user, $test)) {
            throw new \RuntimeException("Espace Élite non débloqué pour ce test (2 scores parfaits requis).");
        }

        if ($this->quotaExceeded()) {
            throw new \RuntimeException(
                "Quota IA journalier atteint ({$this->dailyUsage()}/{$this->quotaLimit()}). Réessayez demain."
            );
        }

        if ($this->activeChallenge($user)) {
            throw new \RuntimeException('Un défi est déjà en cours : terminez-le ou attendez sa génération.');
        }

        $weak = $this->statistics->weakQuestionTypes($user, 5);
        $progress = $this->statistics->skillProgress($user);

        return $this->createGeneration($user, $test->id, 'C1');
    }

    /**
     * Session IA directe d'un niveau (C1/C2, voie « entraînement par niveau ») :
     * sans condition d'unlock, même quota et même file d'attente.
     */
    public function requestLevelGeneration(User $user, string $level): AiChallenge
    {
        $level = strtoupper($level);

        if (! in_array($level, ['C1', 'C2'], true)) {
            throw new \RuntimeException('Génération IA disponible pour les niveaux C1 et C2.');
        }

        return $this->createGeneration($user, null, $level);
    }

    private function createGeneration(User $user, ?int $modellTestId, string $level): AiChallenge
    {
        if ($this->quotaExceeded()) {
            throw new \RuntimeException(
                "Quota IA journalier atteint ({$this->dailyUsage()}/{$this->quotaLimit()}). Réessayez demain."
            );
        }

        if ($this->activeChallenge($user)) {
            throw new \RuntimeException('Un défi est déjà en cours : terminez-le ou attendez sa génération.');
        }

        $weak = $this->statistics->weakQuestionTypes($user, 5);
        $progress = $this->statistics->skillProgress($user);

        $challenge = AiChallenge::create([
            'user_id' => $user->id,
            'modell_test_id' => $modellTestId,
            'skill' => Skill::Lesen->value,
            'status' => 'generating',
            'level' => $level,
            'weak_snapshot' => ['weak_types' => $weak, 'progress' => $progress],
            'requested_at' => now(),
        ]);

        \App\Jobs\GenerateChallengeQuestions::dispatch($challenge->id);

        return $challenge;
    }

    /** Génère les QCM via l'IA puis matérialise le test (appelé par le job). */
    public function generate(AiChallenge $challenge): AiChallenge
    {
        if ($challenge->status !== 'generating') {
            return $challenge; // idempotence (retry de job)
        }

        if (! $this->gemini->enabled()) {
            return $this->markFailed($challenge, 'Analyse IA indisponible (clé API non configurée).');
        }

        if ($this->quotaExceeded()) {
            return $this->markFailed($challenge, 'Quota IA journalier atteint. Réessayez demain.');
        }

        $min = (int) config('testdaf.challenge.min_questions', 20);
        $items = $this->fetchItems($challenge, $min);

        if (count($items) < $min) {
            // Échec transitoire (quota API 429, surcharge 5xx) : message adapté,
            // inutile de gaspiller un 2e appel collé au 1er (il échouerait pareil).
            if ($this->gemini->lastRetryable()) {
                $status = (int) ($this->gemini->lastError()['status'] ?? 0);

                return $this->markFailed(
                    $challenge,
                    "IA momentanément indisponible (erreur {$status} : surcharge ou quota API). Attendez quelques minutes puis relancez la génération."
                );
            }

            return $this->markFailed(
                $challenge,
                "Génération incomplète ({$this->count($items)}/{$min} questions valides). Relancez la génération."
            );
        }

        $kept = array_slice($items, 0, 30);
        $test = $this->materialize($challenge, $kept);

        $questionCount = $test->sections
            ->flatMap(fn ($s) => $s->exercises)
            ->flatMap(fn ($e) => $e->questions)
            ->count();

        $challenge->update(['status' => 'ready', 'completed_at' => now()]);

        try {
            $challenge->user->notify(new ChallengeReady($challenge->id, $questionCount));
        } catch (\Throwable $e) {
            report($e);
        }

        return $challenge->refresh();
    }

    // ---------------------------------------------------------- interne

    /** Appelle l'IA (+1 relance de complétion si < min et échec non transitoire). */
    private function fetchItems(AiChallenge $challenge, int $min): array
    {
        $nonce = bin2hex(random_bytes(4));
        $items = $this->normalizeItems(
            $this->gemini->generateJson($this->buildPrompt($challenge, $min, $nonce), $this->systemPrompt($challenge))
        );

        if (count($items) >= $min) {
            return $items;
        }

        // Échec transitoire (429/5xx) : pas de relance immédiate, elle
        // échouerait à l'identique une seconde plus tard. Laisser generate()
        // signaler "réessayez dans quelques minutes".
        if ($this->gemini->lastRetryable()) {
            return $items;
        }

        // Relance : on demande de COMPLÉTER (pas de recommencer).
        $more = $this->normalizeItems(
            $this->gemini->generateJson($this->completePrompt($challenge, $min, count($items), $nonce), $this->systemPrompt($challenge))
        );

        $seen = collect($items)->map(fn ($i) => mb_strtolower(trim((string) ($i['prompt'] ?? ''))))->all();

        foreach ($more as $item) {
            if (! in_array(mb_strtolower(trim((string) ($item['prompt'] ?? ''))), $seen, true)) {
                $items[] = $item;
                $seen[] = mb_strtolower(trim((string) ($item['prompt'] ?? '')));
            }
        }

        return $items;
    }

    private function systemPrompt(AiChallenge $challenge): string
    {
        $level = strtoupper((string) ($challenge->level ?? 'C1'));

        return 'Du bist ein erfahrener TestDaF-Autor. Du erstellst ORIGINELLE Leseverstehens-QCM '
            ."im TestDaF-Rahmen (universitäre Themen, Niveau {$level}, 4 Antwortoptionen a-d, genau EINE richtige). "
            .'Antworte NUR mit gültigem JSON: ein Array von Objekten mit den Feldern '
            .'stimulus (kurzer deutscher Lesetext 40-70 Wörter, originell), prompt (Frage auf Deutsch), '
            .'options (Objekt mit EXAKT den Schlüsseln a, b, c, d), correct (einer von a, b, c, d), '
            .'explanation (kurz, Deutsch oder Französisch), difficulty (B2, C1 oder C1+). '
            .'Keine Markdown-Codeblöcke, kein Fließtext außerhalb des JSON.';
    }

    private function buildPrompt(AiChallenge $challenge, int $min, string $nonce): string
    {
        $level = strtoupper((string) ($challenge->level ?? 'C1'));
        $mix = $level === 'C2' ? 'C1/C1+ (exigeant, quasi natif)' : 'B2/C1/C1+ mélangées';

        $weak = collect($challenge->weak_snapshot['weak_types'] ?? [])
            ->map(fn ($r) => "{$r['type']} ({$r['error_rate']} % d'erreurs)")
            ->implode(', ') ?: 'aucune faiblesse marquée (niveau homogène)';

        return implode("\n", [
            "Génération Défi IA #{$nonce} (à chaque demande, textes et thèmes DOIVENT être inédits).",
            "Cadre : TestDaF digital, Leseverstehen, niveau {$level} STRICT (vocabulaire, syntaxe et implicite calibrés {$level}), thèmes universitaires variés.",
            "Faiblesses analysées du candidat à cibler en priorité : {$weak}.",
            "Produis EXACTEMENT {$min} QCM single_choice variés (difficultés {$mix}), "
            .'stimulus originaux, distracteurs crédibles (pas de bonne réponse évidente).',
        ]);
    }

    private function completePrompt(AiChallenge $challenge, int $min, int $got, string $nonce): string
    {
        return "Tu as fourni {$got} QCM valides, il en faut {$min} au total. "
            ."Ajoute UNIQUEMENT les ".($min - $got)." QCM MANQUANTS (thèmes inédits, génération #{$nonce}), "
            .'même format JSON strict (array d\'objets stimulus/prompt/options/correct/explanation/difficulty).';
    }

    /** Normalise/valide : enveloppe {questions:[...]} acceptée, items incomplets rejetés. */
    private function normalizeItems(mixed $decoded): array
    {
        if (is_array($decoded) && isset($decoded['questions']) && is_array($decoded['questions'])) {
            $decoded = $decoded['questions'];
        }

        if (! is_array($decoded)) {
            return [];
        }

        $out = [];

        foreach (array_values($decoded) as $raw) {
            if (! is_array($raw)) {
                continue;
            }

            $options = $raw['options'] ?? null;
            if (! is_array($options)) {
                continue;
            }

            $options = array_change_key_case($options, CASE_LOWER);
            if (array_diff(['a', 'b', 'c', 'd'], array_keys($options))) {
                continue;
            }

            $correct = mb_strtolower(trim((string) ($raw['correct'] ?? '')));
            if (! in_array($correct, ['a', 'b', 'c', 'd'], true)) {
                continue;
            }

            $difficulty = strtoupper(trim((string) ($raw['difficulty'] ?? '')));
            if (! in_array($difficulty, ['B2', 'C1', 'C1+'], true)) {
                $difficulty = (string) config('testdaf.challenge.default_difficulty', 'C1');
            }

            $stimulus = trim((string) ($raw['stimulus'] ?? ''));
            $prompt = trim((string) ($raw['prompt'] ?? ''));

            if ($stimulus === '' || $prompt === '' || trim((string) $options[$correct]) === '') {
                continue;
            }

            $out[] = [
                'stimulus' => $stimulus,
                'prompt' => $prompt,
                'options' => [
                    'a' => trim((string) $options['a']),
                    'b' => trim((string) $options['b']),
                    'c' => trim((string) $options['c']),
                    'd' => trim((string) $options['d']),
                ],
                'correct' => $correct,
                'explanation' => trim((string) ($raw['explanation'] ?? '')),
                'difficulty' => $difficulty,
            ];
        }

        return $out;
    }

    private function count(array $items): int
    {
        return count($items);
    }

    public function timeFor(string $difficulty): int
    {
        $map = config('testdaf.challenge.time_by_difficulty', []);

        return (int) ($map[$difficulty] ?? $map['C1'] ?? 120);
    }

    /** Crée le vrai ModellTest draft (invisible de la liste) + section + exercice + 20+ QCM. */
    private function materialize(AiChallenge $challenge, array $items): ModellTest
    {
        return DB::transaction(function () use ($challenge, $items) {
            $base = (int) config('testdaf.challenge.test_number_base', 900);
            $number = $base + (int) ModellTest::where('number', '>=', $base)->count() + 1;

            $level = strtoupper((string) ($challenge->level ?? 'C1'));

            $test = ModellTest::create([
                'number' => $number,
                'title' => "Défi IA {$level} — Lesen (".count($items).' QCM inédits)',
                'description' => "QCM générés par IA sur mesure (cadre TestDaF, niveau {$level}). Chaque génération est inédite.",
                'theme' => 'Défi IA sur mesure',
                'difficulty' => $level,
                'status' => 'draft', // invisible des listes, jouable via /challenges
                'created_by' => $challenge->user_id,
            ]);

            $section = Section::create([
                'modell_test_id' => $test->id,
                'skill' => Skill::Lesen->value,
                'title' => Skill::Lesen->label(),
                'position' => 0,
                'status' => 'published',
            ]);

            $totalTime = array_sum(array_map(fn ($i) => $this->timeFor($i['difficulty']), $items));

            $exercise = Exercise::create([
                'skill' => Skill::Lesen,
                'type' => 'multiple_choice',
                'title' => "Défi IA {$level} — QCM inédits",
                'level' => $level,
                'difficulty' => $level,
                'instruction' => "QCM inédits générés par IA dans le cadre du niveau {$level}. Lisez chaque stimulus et choisissez la bonne réponse (a–d).",
                'duration_seconds' => $totalTime,
                'points' => count($items),
                'position' => 0,
                'content' => ['ai_challenge_id' => $challenge->id, 'generated' => true],
                'status' => 'published',
            ]);
            $section->exercises()->attach($exercise->id, ['position' => 0]);

            foreach (array_values($items) as $pos => $item) {
                $time = $this->timeFor($item['difficulty']);

                $question = Question::create([
                    'exercise_id' => $exercise->id,
                    'type' => 'single_choice',
                    'difficulty' => $item['difficulty'],
                    'position' => $pos,
                    'prompt' => $item['stimulus']."\n\n".$item['prompt'],
                    'points' => 1,
                    'time_limit_seconds' => $time,
                    'data' => ['stimulus' => $item['stimulus'], 'ai' => true],
                    'correct_answer' => [$item['correct']],
                    'explanation' => $item['explanation'] !== '' ? $item['explanation'] : null,
                ]);

                foreach (['a', 'b', 'c', 'd'] as $i => $label) {
                    AnswerOption::create([
                        'question_id' => $question->id,
                        'label' => $label,
                        'text' => $item['options'][$label],
                        'is_correct' => $label === $item['correct'],
                        'position' => $i,
                    ]);
                }
            }

            $test->update(['total_duration_seconds' => $test->fresh()->computedDuration()]);

            $challenge->update(['generated_test_id' => $test->id]);

            return $test;
        });
    }

    private function markFailed(AiChallenge $challenge, string $error): AiChallenge
    {
        $challenge->update([
            'status' => 'failed',
            'error' => $error,
            'completed_at' => now(),
        ]);

        return $challenge->refresh();
    }
}
