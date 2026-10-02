<?php

namespace App\Policies;

use App\Models\Attempt;
use App\Models\User;

class AttemptPolicy
{
    /** Un candidat ne peut voir que ses propres tentatives ; l'admin voit tout. */
    public function view(User $user, Attempt $attempt): bool
    {
        return $user->id === $attempt->user_id || $user->isAdmin();
    }

    /** Seul le propriétaire (candidat actif) peut interagir avec sa tentative. */
    public function interact(User $user, Attempt $attempt): bool
    {
        return $user->id === $attempt->user_id;
    }

    public function update(User $user, Attempt $attempt): bool
    {
        return $user->id === $attempt->user_id;
    }
}
