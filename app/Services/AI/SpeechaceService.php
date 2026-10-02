<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * SpeechaceService — analyse de prononciation avancée (optionnel).
 *
 * Service spécialisé destiné à un usage ultérieur ; désactivé tant qu'aucune
 * clé API n'est configurée. Encapsulé pour rester remplaçable.
 */
class SpeechaceService
{
    public function enabled(): bool
    {
        return ! empty(config('testdaf.ai.speechace_api_key'));
    }

    public function analyze(string $audioPath, string $text, string $disk = 'local'): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $contents = Storage::disk($disk)->get($audioPath);

            $response = Http::timeout(120)
                ->attach('user_audio_file', $contents, 'audio.webm')
                ->post('https://api.speechace.co/api/scoring/text/v9/json?key='.config('testdaf.ai.speechace_api_key'), [
                    'text' => $text,
                    'dialect' => 'en-us',
                ]);

            if ($response->failed()) {
                Log::warning('SpeechaceService: échec API', ['status' => $response->status()]);

                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::warning('SpeechaceService: exception', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
