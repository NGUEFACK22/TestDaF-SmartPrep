<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Données initiales de production (SANS comptes démo) :
 * rôles, réglages, 10 Modelltests + audios, démos Hören, test C1, exos C1.
 * Idempotent : à relancer sans risque après chaque déploiement.
 */
class SeedContent extends Command
{
    protected $signature = 'synphonie:seed-content';

    protected $description = 'Seed du contenu de production (sans comptes démo)';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\RoleSeeder']);
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\SettingSeeder']);
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\LevelTrackSeeder']);

        $this->info('Contenu de production en place.');

        return self::SUCCESS;
    }
}
