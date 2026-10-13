<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'phone',
        'email',
        'is_legacy',
        'notes',
        'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_legacy' => 'boolean',
            'registered_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->registered_at)) {
                $customer->registered_at = now();
            }
            if (!empty($customer->phone)) {
                $customer->phone = preg_replace('/[^0-9]/', '', (string) $customer->phone);
            }
        });

        static::updating(function ($customer) {
            if ($customer->isDirty('phone') && !empty($customer->phone)) {
                $customer->phone = preg_replace('/[^0-9]/', '', (string) $customer->phone);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class)->orderBy('appointment_date', 'desc')->orderBy('start_time', 'desc');
    }

    /**
     * Determina si el cliente es nuevo (1 cita o registrado recientemente con <=1 cita).
     * Las clientas antiguas (is_legacy = true) nunca se consideran nuevas.
     */
    public function isNew(): bool
    {
        if ($this->is_legacy) {
            return false;
        }

        return $this->appointments()->count() <= 1;
    }

    /**
     * Determina si ya es cliente recurrente / antiguo.
     */
    public function isRecurring(): bool
    {
        if ($this->is_legacy) {
            return true;
        }

        return $this->appointments()->count() >= 2;
    }

    /**
     * Retorna la configuración de badge de fidelidad.
     */
    public function getLoyaltyBadge(): array
    {
        $count = $this->appointments()->count();

        // Clienta antigua histórica sin citas en el nuevo sistema
        if ($this->is_legacy && $count === 0) {
            return [
                'label' => '⭐ Antigua',
                'description' => 'Clienta habitual histórica del salón',
                'color' => 'emerald',
                'bg_class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            ];
        }

        if ($count <= 1 && ! $this->is_legacy) {
            return [
                'label' => '✨ Nueva',
                'description' => 'Primera cita / Reciente',
                'color' => 'amber',
                'bg_class' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            ];
        }

        if ($count < 5) {
            return [
                'label' => '⭐ Recurrente',
                'description' => $this->is_legacy
                    ? "Clienta antigua ({$count} citas en sistema)"
                    : "{$count} citas registradas",
                'color' => 'emerald',
                'bg_class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            ];
        }

        return [
            'label' => '👑 Frecuente VIP',
            'description' => "{$count} citas registradas",
            'color' => 'purple',
            'bg_class' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border-purple-200 dark:border-purple-800',
        ];
    }

    /**
     * Total de citas realizadas o confirmadas.
     */
    public function getCompletedAppointmentsCount(): int
    {
        return $this->appointments()
            ->whereIn('status', [AppointmentStatus::COMPLETED, AppointmentStatus::CONFIRMED])
            ->count();
    }

    /**
     * Inversión monetaria total en servicios.
     */
    public function getTotalSpentAttribute(): float
    {
        return (float) $this->appointments()
            ->whereIn('status', [AppointmentStatus::COMPLETED, AppointmentStatus::CONFIRMED])
            ->sum('total_amount');
    }

    /**
     * Servicio favorito / más reservado.
     */
    public function getFavoriteService(): ?Service
    {
        $favoriteServiceId = $this->appointments()
            ->whereNotNull('service_id')
            ->selectRaw('service_id, COUNT(*) as count')
            ->groupBy('service_id')
            ->orderByDesc('count')
            ->value('service_id');

        return $favoriteServiceId ? Service::find($favoriteServiceId) : null;
    }

    /**
     * Última cita registrada.
     */
    public function getLastAppointment(): ?Appointment
    {
        return $this->appointments()->first();
    }

    /**
     * Teléfono sanitizado para enlaces wa.me.
     */
    public function getCleanPhone(): string
    {
        return preg_replace('/[^0-9]/', '', (string) $this->phone);
    }

    /**
     * Formato legible para visualización.
     */
    public function getFormattedPhone(): string
    {
        $clean = $this->getCleanPhone();
        if (str_starts_with($clean, '57') && strlen($clean) === 12) {
            $clean = substr($clean, 2);
        }
        if (strlen($clean) === 10) {
            return substr($clean, 0, 3) . ' ' . substr($clean, 3, 3) . ' ' . substr($clean, 6);
        }
        return $this->phone;
    }

    /**
     * Retorna el número de teléfono con formato internacional legible (+57, +1, +52).
     */
    public function getDisplayPhone(): string
    {
        $clean = $this->getCleanPhone();

        // Colombia (+57) con 12 dígitos
        if (str_starts_with($clean, '57') && strlen($clean) === 12) {
            $local = substr($clean, 2);
            return '+57 ' . substr($local, 0, 3) . ' ' . substr($local, 3, 3) . ' ' . substr($local, 6);
        }

        // México (+52) con 12 dígitos
        if (str_starts_with($clean, '52') && strlen($clean) === 12) {
            $local = substr($clean, 2);
            return '+52 ' . substr($local, 0, 2) . ' ' . substr($local, 2, 4) . ' ' . substr($local, 6);
        }

        // USA (+1) con 11 dígitos
        if (str_starts_with($clean, '1') && strlen($clean) === 11) {
            $local = substr($clean, 1);
            return '+1 ' . substr($local, 0, 3) . ' ' . substr($local, 3, 3) . ' ' . substr($local, 6);
        }

        // Celular colombiano estándar de 10 dígitos (ej: 311 251 2847)
        if (strlen($clean) === 10 && str_starts_with($clean, '3')) {
            return '+57 ' . substr($clean, 0, 3) . ' ' . substr($clean, 3, 3) . ' ' . substr($clean, 6);
        }

        // Número USA de 10 dígitos sin el 1 (ej: 786 355 9136)
        if (strlen($clean) === 10 && (str_starts_with($clean, '786') || str_starts_with($clean, '305') || str_starts_with($clean, '617'))) {
            return '+1 ' . substr($clean, 0, 3) . ' ' . substr($clean, 3, 3) . ' ' . substr($clean, 6);
        }

        return '+' . $clean;
    }

    /**
     * Retorna el enlace directo a WhatsApp (wa.me) con código de país normalizado.
     */
    public function getWhatsAppUrl(): string
    {
        $clean = $this->getCleanPhone();

        if (strlen($clean) === 10 && str_starts_with($clean, '3')) {
            $clean = '57' . $clean;
        }

        if (strlen($clean) === 10 && (str_starts_with($clean, '786') || str_starts_with($clean, '305') || str_starts_with($clean, '617'))) {
            $clean = '1' . $clean;
        }

        return "https://wa.me/{$clean}";
    }

    /**
     * Sincronizar o crear cliente a partir de datos de una cita.
     */
    public static function syncFromAppointment(Appointment $appointment): self
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $appointment->client_phone);

        $searchPhones = [$cleanPhone];
        if (strlen($cleanPhone) === 10 && str_starts_with($cleanPhone, '3')) {
            $searchPhones[] = '57' . $cleanPhone;
        } elseif (strlen($cleanPhone) === 12 && str_starts_with($cleanPhone, '57')) {
            $searchPhones[] = substr($cleanPhone, 2);
        }

        $customer = static::where('tenant_id', $appointment->tenant_id)
            ->whereIn('phone', $searchPhones)
            ->first();

        if (! $customer) {
            $customer = static::newModelInstance([
                'tenant_id' => $appointment->tenant_id,
                'phone' => $cleanPhone,
            ]);
        }

        if (! $customer->exists) {
            $customer->name = $appointment->client_name ?: 'Clienta';
            $customer->email = $appointment->client_email;
            $customer->registered_at = $appointment->appointment_date ?? now();
            $customer->save();
        } else {
            // Actualizar nombre o email si antes estaba vacío
            $updated = false;
            if (empty($customer->name) && !empty($appointment->client_name)) {
                $customer->name = $appointment->client_name;
                $updated = true;
            }
            if (empty($customer->email) && !empty($appointment->client_email)) {
                $customer->email = $appointment->client_email;
                $updated = true;
            }
            if ($updated) {
                $customer->save();
            }
        }

        return $customer;
    }
}
