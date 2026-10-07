<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * Health check public pour uptime/monitoring (Uptime Kuma, etc.).
 * Aucune donnée sensible : statuts + latences uniquement.
 */
class HealthController extends Controller
{
    public function check()
    {
        $checks = [
            'app' => true,
            'db' => $this->checkDb(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $ok = ! in_array(false, $checks, true);

        return response()->json([
            'ok' => $ok,
            'time' => now()->toIso8601String(),
            'checks' => $checks,
            'failed_jobs' => $this->failedJobs(),
        ], $ok ? 200 : 503);
    }

    private function checkDb(): bool
    {
        try {
            DB::select('select 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            Cache::put('health:ping', 'pong', 10);

            return Cache::get('health:ping') === 'pong';
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkStorage(): bool
    {
        try {
            // Plateforme 100 % QCM : aucun fichier stocké, on vérifie que le
            // stockage Laravel (sessions, cache fichier) reste inscriptible.
            Storage::disk('local')->put('health-ping.txt', 'pong');

            return Storage::disk('local')->exists('health-ping.txt');
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkQueue(): bool
    {
        try {
            $size = Queue::size();

            return is_int($size) ? $size < 1000 : true; // alerte si >1000 en attente
        } catch (\Throwable) {
            return true; // driver sync : pas de file à surveiller
        }
    }

    private function failedJobs(): int
    {
        try {
            return (int) DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
