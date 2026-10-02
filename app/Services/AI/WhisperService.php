<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * WhisperService — transcription audio (Whisper auto-hébergé / local).
 *
 * Aucun coût API n'est supposé : le service est optionnel et encapsulé.
 * Une transcription ne permet PAS de mesurer précisément tous les aspects
 * phonétiques (voir SpeechaceService pour une analyse de prononciation).
 */
class WhisperService
{
    public function enabled(): bool
    {
        return ! empty(config('testdaf.ai.whisper_endpoint'));
    }

    /** Transcrit un fichier audio (chemin sur un disque de stockage). */
    public function transcribe(Media|string $audio, string $disk = 'local', string $mime = 'audio/webm'): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $contents = $this->readBytes($audio, $disk);
            if ($contents === null) {
                return null;
            }

            $response = Http::timeout(180)
                ->attach('file', $contents, 'audio.webm', ['Content-Type' => $mime])
                ->post((string) config('testdaf.ai.whisper_endpoint'), [
                    'model' => 'whisper-1',
                    'language' => 'de',
                ]);

            if ($response->failed()) {
                Log::error('WhisperService: échec API', ['status' => $response->status()]);

                return null;
            }

            return data_get($response->json(), 'text');
        } catch (\Throwable $e) {
            Log::error('WhisperService: exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function readBytes(Media|string $audio, string $disk): ?string
    {
        if ($audio instanceof Media) {
            return Storage::disk($audio->disk)->exists($audio->path)
                ? Storage::disk($audio->disk)->get($audio->path)
                : null;
        }

        return is_string($audio) && is_file($audio) ? file_get_contents($audio) : null;
    }
}
