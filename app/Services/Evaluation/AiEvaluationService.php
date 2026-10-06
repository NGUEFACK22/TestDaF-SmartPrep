<?php

namespace App\Services\Evaluation;

use App\Enums\Skill;
use App\Models\AiEvaluation;
use App\Models\Attempt;
use App\Notifications\AnalysisCompleted;
use App\Services\AI\GeminiService;
use Illuminate\Database\Eloquent\Model;

/**
 * AiEvaluationService — orchestration de l'évaluation IA.
 *
 * Le navigateur n'appelle JAMAIS l'API IA directement : le flux est
 * Candidat -> Laravel -> (transcription) -> IA -> résultat structuré -> Laravel.
 * En cas d'échec, l'erreur est journalisée et l'évaluation reste
 * « Analyse en attente » (aucune réponse n'est perdue).
 */
class AiEvaluationService
{
    public function __construct(private GeminiService $gemini) {}

    public function enabled(): bool
    {
        return (bool) config('testdaf.ai.enabled');
    }

    public function providerAvailable(): bool
    {
        return $this->gemini->enabled();
    }

    /** Crée une évaluation à l'état « en attente ». */
    public function createPending(Model $evaluable, Skill $skill, ?Attempt $attempt = null): AiEvaluation
    {
        return AiEvaluation::create([
            'evaluable_type' => $evaluable->getMorphClass(),
            'evaluable_id' => $evaluable->getKey(),
            'user_id' => $evaluable->user_id ?? $attempt?->user_id,
            'attempt_id' => $attempt?->id ?? ($evaluable->attempt_id ?? null),
            'skill' => $skill->value,
            'provider' => config('testdaf.ai.provider'),
            'model' => config('testdaf.ai.model'),
            'status' => 'pending',
            'requested_at' => now(),
        ]);
    }

    /** Quota journalier (Setting admin, défaut .env MAX_AI_REQUESTS). 0 = bloqué. */
    public function quotaLimit(): int
    {
        try {
            return (int) \App\Models\Setting::get('max_ai_requests', config('testdaf.ai.max_requests', 100));
        } catch (\Throwable) {
            return (int) config('testdaf.ai.max_requests', 100);
        }
    }

    public function dailyUsage(): int
    {
        return AiEvaluation::where('requested_at', '>=', now()->startOfDay())->count();
    }

    public function quotaExceeded(): bool
    {
        return $this->dailyUsage() >= $this->quotaLimit();
    }

    /** Évalue un texte via le fournisseur LLM et enregistre le JSON structuré. */
    public function evaluateText(AiEvaluation $evaluation, string $prompt, string $system = ''): AiEvaluation
    {
        if (! $this->providerAvailable()) {
            $evaluation->update([
                'status' => 'failed',
                'error' => 'Analyse IA indisponible (clé API non configurée).',
                'completed_at' => now(),
            ]);

            return $evaluation;
        }

        // Quota enforced : protège la facture Gemini, retry possible demain.
        if ($this->quotaExceeded()) {
            return $this->markFailed(
                $evaluation,
                "Quota IA journalier atteint ({$this->dailyUsage()}/{$this->quotaLimit()}). Nouvelle tentative demain ou demandez à l'administrateur d'augmenter max_ai_requests."
            );
        }

        $evaluation->update(['status' => 'processing']);

        $result = $this->gemini->evaluateText($prompt, $system);

        if ($result === null) {
            return $this->markFailed($evaluation, 'Échec de l\'appel au fournisseur IA.');
        }

        return $this->markCompleted($evaluation, $result);
    }

    public function markCompleted(AiEvaluation $evaluation, array $result): AiEvaluation
    {
        $evaluation->update([
            'status' => 'completed',
            'result' => $result,
            'feedback' => $result['feedback'] ?? null,
            'completed_at' => now(),
        ]);

        $this->notifyCompleted($evaluation);

        return $evaluation->refresh();
    }

    public function markFailed(AiEvaluation $evaluation, string $error): AiEvaluation
    {
        $evaluation->update([
            'status' => 'failed',
            'error' => $error,
            'completed_at' => now(),
        ]);

        return $evaluation->refresh();
    }

    /** Autorise une nouvelle tentative d'analyse pour une évaluation échouée. */
    public function retryable(AiEvaluation $evaluation): bool
    {
        return in_array($evaluation->status, ['failed', 'pending'], true);
    }

    private function notifyCompleted(AiEvaluation $evaluation): void
    {
        if (! $evaluation->user_id || ! $evaluation->attempt_id) {
            return;
        }

        try {
            $evaluation->user?->notify(new AnalysisCompleted(
                attemptId: (int) $evaluation->attempt_id,
                skill: (string) $evaluation->skill,
                indicatorScore: $evaluation->indicatorScore(),
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
