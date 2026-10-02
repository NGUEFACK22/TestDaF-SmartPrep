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
    public function enabled(): bool
    {
        return (bool) config('testdaf.ai.enabled')
            && ! empty(config('testdaf.ai.gemini_api_key'));
    }

    /**
     * Analyse un texte et retourne un résultat JSON structuré, ou null en cas
     * d'échec (l'appelant gère alors le repli « Analyse en attente »).
     */
    public function evaluateText(string $prompt, string $systemInstruction = ''): ?array
    {
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
                'temperature' => 0.2,
            ],
        ];

        if ($systemInstruction !== '') {
            $payload['system_instruction'] = ['parts' => [['text' => $systemInstruction]]];
        }

        try {
            $response = Http::timeout(120)->acceptJson()->post($url, $payload);

            if ($response->failed()) {
                Log::error('GeminiService: échec API', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                return null;
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

            if (! is_string($text) || $text === '') {
                return null;
            }

            $decoded = json_decode($text, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        } catch (\Throwable $e) {
            Log::error('GeminiService: exception', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
