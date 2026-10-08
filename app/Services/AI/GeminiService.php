<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GeminiService — analyse multimodale / textuelle via l'API Gemini.
 *
 * Fournisseur encapsulé : l'application n'appelle jamais Gemini directement.
 * La clé API reste côté serveur (jamais exposée au navigateur).
 */
class GeminiService
{
    /** Dernier échec d'appel (status HTTP + extrait), null si dernier appel OK. */
    protected ?array $lastError = null;

    public function enabled(): bool
    {
        return (bool) config('testdaf.ai.enabled')
            && ! empty(config('testdaf.ai.gemini_api_key'));
    }

    /** Dernier échec : ['status' => int, 'body' => string] ou null. */
    public function lastError(): ?array
    {
        return $this->lastError;
    }

    /** L'échec est-il transitoire (429 quota, 5xx, timeout) → vaut le coup de réessayer plus tard. */
    public function lastRetryable(): bool
    {
        $status = (int) ($this->lastError['status'] ?? 0);

        return in_array($status, [408, 425, 429, 500, 502, 503, 504], true);
    }

    /**
     * Génère du JSON structuré (QCM inédits, etc.), ou null en cas d'échec.
     * Température élevée par défaut pour varier les questions à chaque appel.
     */
    public function generateJson(string $prompt, string $systemInstruction = '', float $temperature = 0.7): ?array
    {
        return $this->call($prompt, $systemInstruction, $temperature);
    }

    /**
     * Analyse un texte et retourne un résultat JSON structuré, ou null en cas
     * d'échec (l'appelant gère alors le repli « Analyse en attente »).
     */
    public function evaluateText(string $prompt, string $systemInstruction = ''): ?array
    {
        return $this->call($prompt, $systemInstruction, 0.2);
    }

    protected function call(string $prompt, string $systemInstruction, float $temperature): ?array
    {
        $this->lastError = null;

        if (! $this->enabled()) {
            return null;
        }

        $model = config('testdaf.ai.model');
        $endpoint = rtrim((string) config('testdaf.ai.gemini_endpoint'), '/');
        $key = config('testdaf.ai.gemini_api_key');
        $url = "{$endpoint}/models/{$model}:generateContent?key={$key}";

        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => $temperature,
            ],
        ];

        if ($systemInstruction !== '') {
            $payload['system_instruction'] = ['parts' => [['text' => $systemInstruction]]];
        }

        try {
            $response = Http::timeout(120)->acceptJson()->post($url, $payload);

            if ($response->failed()) {
                $this->lastError = [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ];
                Log::error('GeminiService: échec API', $this->lastError);

                return null;
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

            if (! is_string($text) || $text === '') {
                $this->lastError = ['status' => 200, 'body' => 'Réponse vide du fournisseur.'];

                return null;
            }

            $decoded = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->lastError = ['status' => 200, 'body' => 'JSON invalide : '.mb_substr($text, 0, 200)];

                return null;
            }

            return $decoded;
        } catch (\Throwable $e) {
            $this->lastError = ['status' => 0, 'body' => $e->getMessage()];
            Log::error('GeminiService: exception', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
