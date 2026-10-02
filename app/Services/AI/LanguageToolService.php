<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LanguageToolService — détection d'erreurs linguistiques de bas niveau
 * (orthographe, grammaire, ponctuation) pour l'allemand.
 *
 * Résultat fusionné ensuite avec l'analyse sémantique du LLM.
 */
class LanguageToolService
{
    public function enabled(): bool
    {
        return ! empty(config('testdaf.ai.languagetool_url'));
    }

    /**
     * Analyse un texte allemand et retourne les correspondances normalisées.
     *
     * @return array{matches: array<int, array>, count: int}|null
     */
    public function check(string $text, string $language = 'de-DE'): ?array
    {
        if (! $this->enabled() || trim($text) === '') {
            return null;
        }

        $url = rtrim((string) config('testdaf.ai.languagetool_url'), '/').'/check';

        try {
            $response = Http::asForm()->timeout(60)->post($url, [
                'text' => $text,
                'language' => $language,
            ]);

            if ($response->failed()) {
                Log::warning('LanguageToolService: échec API', ['status' => $response->status()]);

                return null;
            }

            $matches = data_get($response->json(), 'matches', []);
            $normalized = collect($matches)->map(fn ($m) => [
                'message' => data_get($m, 'message'),
                'short_message' => data_get($m, 'shortMessage'),
                'offset' => data_get($m, 'offset'),
                'length' => data_get($m, 'length'),
                'rule' => data_get($m, 'rule.id'),
                'category' => data_get($m, 'rule.category.name'),
                'replacements' => array_slice((array) data_get($m, 'replacements', []), 0, 5),
            ])->values()->all();

            return ['matches' => $normalized, 'count' => count($normalized)];
        } catch (\Throwable $e) {
            Log::warning('LanguageToolService: exception', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
