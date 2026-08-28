<?php

namespace App\Policies;

use App\Models\Container;
use App\Models\User;

class ContainerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('containers.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('containers.create');
    }

    public function update(User $user, Container $container): bool
    {
        return $user->hasPermission('containers.update');
    }
}
