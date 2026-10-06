<?php

namespace App\Console\Commands;

use App\Models\ExamLog;
use App\Models\Media;
use App\Services\Media\MediaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Purge RGPD : supprime les productions candidats (audio/vidéo) et les
 * vieux journaux d'examen au-delà de la rétention configurée.
 *
 * Usage : php artisan testdaf:purge --days=365 --dry-run
 */
class PurgeCandidateData extends Command
{
    protected $signature = 'testdaf:purge
        {--days=365 : Ancienneté (jours) au-delà de laquelle les fichiers sont supprimés}
        {--logs-days=180 : Ancienneté (jours) des exam_logs à purger}
        {--dry-run : Affiche ce qui serait supprimé sans rien effacer}';

    protected $description = 'Purge RGPD des médias candidats et des vieux exam_logs';

    public function __construct(private MediaService $media)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = max(30, (int) $this->option('days'));
        $logsDays = max(30, (int) $this->option('logs-days'));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);
        $logsCutoff = now()->subDays($logsDays);

        // Médias candidats privés antérieurs au cutoff (jamais les contenus).
        $query = Media::query()
            ->where('created_at', '<', $cutoff)
            ->whereJsonContains('meta->public', false);

        // Fallback si le driver JSON ne supporte pas whereJsonContains (SQLite).
        $medias = Media::query()->where('created_at', '<', $cutoff)->get()
            ->filter(fn (Media $m) => ($m->meta['public'] ?? true) === false);

        $this->info("Médias candidats à purger : {$medias->count()} (cutoff {$cutoff->toDateString()})");

        $freed = 0;
        foreach ($medias as $m) {
            $freed += (int) ($m->size ?? 0);
            if (! $dryRun) {
                try {
                    $disk = $m->disk ?: config('testdaf.media.disk', 'local');
                    if ($m->path && Storage::disk($disk)->exists($m->path)) {
                        Storage::disk($disk)->delete($m->path);
                    }
                    $m->delete();
                } catch (\Throwable $e) {
                    $this->error("Échec purge media #{$m->id} : {$e->getMessage()}");
                    report($e);
                }
            }
        }

        $logsQuery = ExamLog::where('created_at', '<', $logsCutoff);
        $logsCount = $logsQuery->count();
        $this->info("exam_logs à purger : {$logsCount} (cutoff {$logsCutoff->toDateString()})");

        if (! $dryRun && $logsCount > 0) {
            // Suppression par lots pour ne pas saturer Neon.
            $logsQuery->chunkById(1000, fn ($chunk) => ExamLog::whereIn('id', $chunk->pluck('id'))->delete());
        }

        $this->info($dryRun
            ? '[dry-run] Rien n\'a été supprimé. Espace récupérable : '.$this->human($freed)
            : 'Purge terminée. Espace récupéré : '.$this->human($freed));

        return self::SUCCESS;
    }

    private function human(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' o';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' Ko';
        }
        if ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / 1024 / 1024, 1).' Mo';
        }

        return round($bytes / 1024 / 1024 / 1024, 2).' Go';
    }
}
