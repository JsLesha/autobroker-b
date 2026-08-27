<?php

namespace App\Models;

use App\Enums\RoleCode;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name', 'email', 'password', 'role_id', 'department_id', 'nickname',
    'active', 'active_prebid', 'is_office_dealer', 'telegram_id', 'telegram_name',
    'bitrix_user_id', 'public_offer_status', 'public_offer_accepted_at',
    'first_login_at', 'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'active_prebid' => 'boolean',
            'is_office_dealer' => 'boolean',
            'public_offer_accepted_at' => 'datetime',
            'first_login_at' => 'datetime',
            'last_login_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(UserDocument::class);
    }

    public function roleCode(): ?RoleCode
    {
        return $this->role?->code;
    }

    public function isAdminLike(): bool
    {
        return $this->roleCode()?->isAdminLike() ?? false;
    }

    public function hasPermission(string $code): bool
    {
        if ($this->isAdminLike()) {
            return true;
        }

        if (! $this->relationLoaded('role')) {
            $this->load('role.permissions');
        }

        return $this->role?->permissions->contains('code', $code) ?? false;
    }

    public function publicOfferPhase(): string
    {
        if ($this->public_offer_status === 'accepted') {
            return 'ACCEPTED';
        }

        if ($this->first_login_at && $this->first_login_at->lt(now()->subDays(30))) {
            return 'RESTRICTED';
        }

        if ($this->first_login_at && $this->first_login_at->lt(now()->subDays(3))) {
            return 'OVERDUE';
        }

        return 'PENDING';
    }
}
