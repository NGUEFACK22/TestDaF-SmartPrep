<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $candidateRole = Role::where('slug', 'candidate')->first();

        User::updateOrCreate(
            ['email' => 'admin@testdaf.local'],
            [
                'name' => 'Administrateur',
                'password' => 'TestDaF-Admin-2026!',
                'role_id' => $adminRole?->id,
                'status' => 'active',
                'locale' => 'fr',
            ]
        );

        User::updateOrCreate(
            ['email' => 'candidat@testdaf.local'],
            [
                'name' => 'Candidat Démo',
                'password' => 'TestDaF-Demo-2026!',
                'role_id' => $candidateRole?->id,
                'status' => 'active',
                'locale' => 'fr',
            ]
        );
    }
}
