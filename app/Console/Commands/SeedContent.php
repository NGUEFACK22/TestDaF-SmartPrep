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
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\ModellTestSeeder']);
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\ModellTestMediaSeeder']);
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\HoerenDemoSeeder']);
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\LevelTestSeeder']);
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\C1ExerciseSeeder']);

        $this->info('Contenu de production en place.');

        return self::SUCCESS;
    }
}
