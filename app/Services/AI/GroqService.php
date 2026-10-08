<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GroqService — génération JSON via l'API Groq (OpenAI-compatible).
 *
 * Même contrat que GeminiService (generateJson, lastError, lastRetryable) :
 * sélectionné quand AI_PROVIDER=groq. Modèles à préfixe (ex. openai/...).
 * La clé reste côté serveur. Note Groq : avec response_format json_object,
 * les messages doivent contenir le mot "json" (le system prompt l'assure).
 */
class GroqService extends GeminiService
{
    public function enabled(): bool
    {
        return (bool) config('testdaf.ai.enabled')
            && (string) config('testdaf.ai.provider') === 'groq'
            && ! empty(config('testdaf.ai.groq_api_key'));
    }

    protected function call(string $prompt, string $systemInstruction, float $temperature): ?array
    {
        $this->lastError = null;

        if (! $this->enabled()) {
            return null;
        }

        $model = config('testdaf.ai.groq_model');
        $endpoint = rtrim((string) config('testdaf.ai.groq_endpoint'), '/');
        $url = "{$endpoint}/chat/completions";

        $messages = [];
        if ($systemInstruction !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemInstruction];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        try {
            $response = Http::timeout(120)->acceptJson()
                ->withToken((string) config('testdaf.ai.groq_api_key'))
                ->post($url, [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => $temperature,
                    // 20 QCM + stimulus ≈ 8-12k tokens : sans plafond explicite,
                    // la réponse est tronquée (JSON invalide → 0 question).
                    'max_completion_tokens' => 16000,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->failed()) {
                $this->lastError = [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ];
                Log::error('GroqService: échec API', $this->lastError);

                return null;
            }

            $text = data_get($response->json(), 'choices.0.message.content');

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
            Log::error('GroqService: exception', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
