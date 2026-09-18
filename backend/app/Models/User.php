<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'phone',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($panel->getId() === 'superadmin') {
            return $this->role === UserRole::SUPER_ADMIN;
        }

        return in_array($this->role, [UserRole::ADMIN, UserRole::SUPER_ADMIN]);
    }

    public function getTenants(Panel $panel): array|Collection
    {
        if ($this->role === UserRole::SUPER_ADMIN) {
            return Tenant::where('is_active', true)->get();
        }

        if ($this->tenant) {
            return collect([$this->tenant]);
        }

        return Tenant::where('is_active', true)->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->role === UserRole::SUPER_ADMIN) {
            return true;
        }

        return $this->tenant_id === $tenant->id;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
