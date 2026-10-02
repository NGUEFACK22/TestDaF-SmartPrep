<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Candidat', 'slug' => 'candidate', 'description' => 'Prépare le TestDaF digital.'],
            ['name' => 'Correcteur', 'slug' => 'corrector', 'description' => 'Corrige les productions Schreiben / Sprechen.'],
            ['name' => 'Administrateur', 'slug' => 'admin', 'description' => 'Gère la plateforme et les contenus.'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
