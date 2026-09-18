<?php

namespace App\Models;

use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model implements HasCurrentTenantLabel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'phone',
        'email',
        'address',
        'city',
        'logo_path',
        'primary_color',
        'nequi_phone',
        'nequi_account_holder',
        'nequi_account_type',
        'nequi_qr_image',
        'bold_api_key',
        'bold_secret_key',
        'subscription_status',
        'plan_name',
        'max_appointments_per_month',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'max_appointments_per_month' => 'integer',
        ];
    }

    public function getCurrentTenantLabel(): string
    {
        return 'Estudio: ' . $this->name;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function workingSchedules(): HasMany
    {
        return $this->hasMany(WorkingSchedule::class);
    }

    public function blockedSlots(): HasMany
    {
        return $this->hasMany(BlockedSlot::class);
    }

    public function waitlists(): HasMany
    {
        return $this->hasMany(Waitlist::class);
    }
}
