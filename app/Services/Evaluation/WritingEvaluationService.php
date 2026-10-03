<?php

namespace App\Services\Evaluation;

use App\Enums\Skill;
use App\Models\AiEvaluation;
use App\Models\Exercise;
use App\Models\WritingSubmission;
use App\Services\AI\LanguageToolService;

/**
 * WritingEvaluationService — pipeline Schreiben.
 *
 * Réponse -> LanguageTool (erreurs de bas niveau) -> LLM (analyse sémantique
 * et pédagogique) -> fusion -> résultat structuré. Le score IA est indicatif
 * (jamais une note officielle TestDaF).
 */
class WritingEvaluationService
{
    public function __construct(
        private AiEvaluationService $ai,
        private LanguageToolService $languageTool,
    ) {}

    public function evaluate(WritingSubmission $submission): AiEvaluation
    {
        $submission->loadMissing('attemptExercise.exercise');

        $exercise = $submission->attemptExercise->exercise;
        $evaluation = $this->ai->createPending($submission, Skill::Schreiben, $submission->attempt);

        if (! $this->ai->providerAvailable()) {
            return $this->ai->markFailed($evaluation, 'Analyse IA indisponible (clé API non configurée).');
        }

        $this->ai->evaluateText($evaluation, $this->buildPrompt($submission, $exercise), $this->systemPrompt());

        // Fusion avec LanguageTool (si disponible).
        $lt = $this->languageTool->check((string) $submission->content);
        if ($lt !== null) {
            $result = $evaluation->result ?? [];
            $result['language_tool'] = $lt;
            $evaluation->update(['result' => $result]);
        }

        return $evaluation->refresh();
    }

    private function systemPrompt(): string
    {
        $max = (int) config('testdaf.ai.rubric_max', 20);
        $criteria = array_keys(config('testdaf.ai.rubric.schreiben', []));

        $fields = implode(', ', array_map(
            fn (string $key) => "{$key} (0-{$max})",
            $criteria
        ));

        return 'Du bist ein erfahrener TestDaF-Prüfer. Bewerte den folgenden Schreibtext '
            .'ausschließlich pädagogisch (Hinweis: dies ist keine offizielle TestDaF-Note). '
            .'Antworte NUR mit gültigem JSON mit den Feldern: '
            ."$fields, feedback (string), "
            .'strengths (array), weaknesses (array), recommendations (array).';
    }

    private function buildPrompt(WritingSubmission $submission, Exercise $exercise): string
    {
        $prompt = [];
        $prompt[] = '### Aufgabenstellung (consigne)';
        $prompt[] = (string) $exercise->instruction;
        if (! empty($exercise->content['source_text'])) {
            $prompt[] = '### Quelltext';
            $prompt[] = (string) $exercise->content['source_text'];
        }
        $prompt[] = '### Kandidatentext';
        $prompt[] = (string) $submission->content;
        $prompt[] = '### Wortanzahl: '.$submission->word_count;

        return implode("\n\n", $prompt);
    }
}
