<?php

namespace App\Providers;

use App\Database\Connectors\NeonPgsqlConnector;
use App\Filesystem\DatabaseMediaAdapter;
use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Disque "database" : fichiers stockés en base (100 % Neon, sans S3).
        // Tout le code existant (MediaService, purge, seeders) fonctionne
        // sans modification via Storage::disk('database').
        Storage::extend('database', function ($app, array $config) {
            $adapter = new DatabaseMediaAdapter();

            return new LaravelFilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });
    }
}
