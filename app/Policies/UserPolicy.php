<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.read');
    }

    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasPermission('users.read');
    }

    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasPermission('users.update');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.create');
    }

    public function impersonate(User $user): bool
    {
        return $user->isAdminLike();
    }
}
