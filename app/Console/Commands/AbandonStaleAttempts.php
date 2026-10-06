<?php

namespace App\Console\Commands;

use App\Enums\AttemptStatus;
use App\Models\Attempt;
use App\Models\ExamLog;
use Illuminate\Console\Command;

/**
 * Abandonne les tentatives InProgress sans activité depuis 7 jours.
 * Évite que restore() reprenne indéfiniment des tentatives fantômes
 * et fausse le "activeAttempt" du dashboard.
 */
class AbandonStaleAttempts extends Command
{
    protected $signature = 'testdaf:abandon-stale {--days=7 : Inactivité (jours) avant abandon}';

    protected $description = 'Abandonne les tentatives en cours sans activité';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $stale = Attempt::where('status', AttemptStatus::InProgress->value)
            ->where('updated_at', '<', $cutoff)
            ->limit(500)
            ->get();

        $this->info("Tentatives à abandonner : {$stale->count()} (cutoff {$cutoff->toDateString()})");

        foreach ($stale as $attempt) {
            $attempt->update(['status' => AttemptStatus::Abandoned]);

            ExamLog::create([
                'user_id' => $attempt->user_id,
                'attempt_id' => $attempt->id,
                'event' => 'attempt_abandoned',
                'payload' => ['reason' => 'stale', 'days' => $days],
                'ip' => null,
            ]);
        }

        $this->info('Terminé.');

        return self::SUCCESS;
    }
}
