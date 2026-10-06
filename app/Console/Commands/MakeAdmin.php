<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Crée (ou promeut) un administrateur en production :
 *   php artisan synphonie:make-admin admin@example.com "Nom Admin"
 * Le mot de passe est demandé interactivement (jamais en argument).
 */
class MakeAdmin extends Command
{
    protected $signature = 'synphonie:make-admin {email : E-mail du compte} {name? : Nom affiché}';

    protected $description = 'Crée ou promeut un administrateur';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $name = (string) ($this->argument('name') ?: $email);
        $password = (string) $this->secret('Mot de passe (min 12 caractères)');

        if (mb_strlen($password) < 12) {
            $this->error('Mot de passe trop court (min 12 caractères).');

            return self::FAILURE;
        }

        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role_id' => $adminRole->id,
                'status' => 'active',
                'locale' => 'fr',
            ]
        );

        $this->info("Administrateur prêt : {$user->email}.");

        return self::SUCCESS;
    }
}
