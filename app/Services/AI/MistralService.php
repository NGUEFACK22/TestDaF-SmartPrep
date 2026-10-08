<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * MistralService — génération JSON via l'API Mistral (La Plateforme).
 *
 * Même contrat que GeminiService (generateJson, lastError, lastRetryable) :
 * sélectionné quand AI_PROVIDER=mistral. La clé reste côté serveur.
 */
class MistralService extends GeminiService
{
    public function enabled(): bool
    {
        return (bool) config('testdaf.ai.enabled')
            && (string) config('testdaf.ai.provider') === 'mistral'
            && ! empty(config('testdaf.ai.mistral_api_key'));
    }

    protected function call(string $prompt, string $systemInstruction, float $temperature): ?array
    {
        $this->lastError = null;

        if (! $this->enabled()) {
            return null;
        }

        $model = config('testdaf.ai.mistral_model');
        $endpoint = rtrim((string) config('testdaf.ai.mistral_endpoint'), '/');
        $url = "{$endpoint}/chat/completions";

        $messages = [];
        if ($systemInstruction !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemInstruction];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        try {
            $response = Http::timeout(120)->acceptJson()
                ->withToken((string) config('testdaf.ai.mistral_api_key'))
                ->post($url, [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => $temperature,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->failed()) {
                $this->lastError = [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ];
                Log::error('MistralService: échec API', $this->lastError);

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
            Log::error('MistralService: exception', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
