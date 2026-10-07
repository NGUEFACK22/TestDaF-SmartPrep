<?php

namespace App\Console\Commands;

use App\Models\ExamLog;
use Illuminate\Console\Command;

/**
 * Purge RGPD : supprime les vieux journaux d'examen au-delà de la rétention
 * configurée (plateforme 100 % QCM : aucun fichier candidat à purger).
 *
 * Usage : php artisan testdaf:purge --logs-days=180 --dry-run
 */
class PurgeCandidateData extends Command
{
    protected $signature = 'testdaf:purge
        {--logs-days=180 : Ancienneté (jours) des exam_logs à purger}
        {--dry-run : Affiche ce qui serait supprimé sans rien effacer}';

    protected $description = 'Purge RGPD des vieux exam_logs';

    public function handle(): int
    {
        $logsDays = max(30, (int) $this->option('logs-days'));
        $dryRun = (bool) $this->option('dry-run');
        $logsCutoff = now()->subDays($logsDays);

        $logsQuery = ExamLog::where('created_at', '<', $logsCutoff);
        $logsCount = $logsQuery->count();
        $this->info("exam_logs à purger : {$logsCount} (cutoff {$logsCutoff->toDateString()})");

        if (! $dryRun && $logsCount > 0) {
            // Suppression par lots pour ne pas saturer Neon.
            $logsQuery->chunkById(1000, fn ($chunk) => ExamLog::whereIn('id', $chunk->pluck('id'))->delete());
        }

        $this->info($dryRun ? '[dry-run] Rien n\'a été supprimé.' : 'Purge terminée.');

        return self::SUCCESS;
    }
}
