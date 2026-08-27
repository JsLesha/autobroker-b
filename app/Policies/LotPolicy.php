<?php

namespace App\Policies;

use App\Models\Lot;
use App\Models\User;

class LotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('lots.read');
    }

    public function view(User $user, Lot $lot): bool
    {
        if ($user->hasPermission('lots.read')) {
            return true;
        }

        return $lot->buyer_user_id === $user->id || $lot->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('lots.create');
    }

    public function update(User $user, Lot $lot): bool
    {
        return $user->hasPermission('lots.update');
    }

    public function delete(User $user, Lot $lot): bool
    {
        return $user->hasPermission('lots.delete');
    }
}
