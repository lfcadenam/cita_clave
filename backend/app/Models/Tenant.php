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
        'short_name',
        'business_type',
        'slug',
        'domain',
        'phone',
        'whatsapp_number',
        'email',
        'address',
        'city',
        'specialties',
        'schedule_summary',
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
        return 'Salón Especialista';
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

    public function getDisplayNameAttribute(): string
    {
        if (! empty($this->short_name)) {
            return $this->short_name;
        }

        $parts = explode(' ', trim($this->name));
        return count($parts) >= 2 ? $parts[0] . ' ' . $parts[1] : $this->name;
    }

    public function getWhatsappUrlAttribute(): string
    {
        $num = preg_replace('/\D/', '', $this->whatsapp_number ?: $this->phone ?: '3103248385');
        return 'https://wa.me/57' . $num;
    }

    /**
     * Determina si la pasarela Bold tiene API Key y Secret Key parametrizadas.
     */
    public function isBoldConfigured(): bool
    {
        $apiKey = $this->bold_api_key ?: config('payment.bold.api_key');
        $secretKey = $this->bold_secret_key ?: config('payment.bold.secret_key');

        return ! empty($apiKey)
            && ! empty($secretKey)
            && ! str_contains((string) $apiKey, 'sample')
            && ! str_contains((string) $secretKey, 'sample');
    }
}
