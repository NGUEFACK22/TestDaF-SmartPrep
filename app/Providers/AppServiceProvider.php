<?php

namespace App\Providers;

use App\Database\Connectors\NeonPgsqlConnector;
use App\Services\AI\GeminiService;
use App\Services\AI\GroqService;
use App\Services\AI\MistralService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Connecteur PostgreSQL adapté aux endpoints pooler (ex. Neon) :
        // ajoute le paramètre SNI "options=endpoint=<id>" au DSN quand
        // DB_ENDPOINT est défini (les builds PHP au libpq < 15.3 ne le
        // font pas automatiquement). Sans DB_ENDPOINT, le connecteur se
        // comporte comme le PostgresConnector standard de Laravel.
        $this->app->bind('db.connector.pgsql', function () {
            return new NeonPgsqlConnector;
        });

        // Fournisseur IA actif (défaut : gemini). Tout le code type-hinte
        // GeminiService : selon AI_PROVIDER, c'est MistralService ou GroqService
        // qui est injecté (même contrat : generateJson, lastError, lastRetryable).
        // Les tests qui mockent GeminiService::class ne sont pas impactés.
        $this->app->bind(GeminiService::class, function () {
            return match ((string) config('testdaf.ai.provider')) {
                'mistral' => new MistralService(),
                'groq' => new GroqService(),
                default => new GeminiService(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
