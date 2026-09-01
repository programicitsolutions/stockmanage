<?php

namespace App\Models;

use App\Enums\RoleSlug;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class, 'created_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isPartner(): bool
    {
        return $this->role?->slug === RoleSlug::Partner;
    }

    public function isAccountant(): bool
    {
        return $this->role?->slug === RoleSlug::Accountant;
    }

    public function hasRole(RoleSlug|string $role): bool
    {
        $slug = $role instanceof RoleSlug ? $role : RoleSlug::from($role);

        return $this->role?->slug === $slug;
    }
}
