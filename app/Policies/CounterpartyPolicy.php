<?php

namespace App\Policies;

use App\Models\Counterparty;
use App\Models\User;

class CounterpartyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('counterparties.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('counterparties.create');
    }

    public function update(User $user, Counterparty $counterparty): bool
    {
        return $user->hasPermission('counterparties.update');
    }
}
