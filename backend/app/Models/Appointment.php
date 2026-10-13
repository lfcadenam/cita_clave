<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Observers\AppointmentObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

#[ObservedBy([AppointmentObserver::class])]
class Appointment extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'appointment_number',
        'user_id',
        'service_id',
        'client_name',
        'client_phone',
        'client_email',
        'appointment_date',
        'start_time',
        'end_time',
        'status',
        'payment_method',
        'total_amount',
        'deposit_amount',
        'deposit_paid',
        'balance_due',
        'deposit_proof_image',
        'verification_notes',
        'verified_at',
        'reminder_24h_sent_at',
        'attendance_confirmed_at',
        'payment_gateway_reference',
        'payment_gateway_payload',
        'cancellation_reason',
        'cancelled_at',
        'client_notes',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'status' => AppointmentStatus::class,
            'payment_method' => PaymentMethod::class,
            'total_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'deposit_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'verified_at' => 'datetime',
            'reminder_24h_sent_at' => 'datetime',
            'attendance_confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'payment_gateway_payload' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($appointment) {
            if (empty($appointment->appointment_number)) {
                $appointment->appointment_number = 'PA-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
            }
            if (empty($appointment->balance_due)) {
                $appointment->balance_due = $appointment->total_amount - $appointment->deposit_paid;
            }
            if (empty($appointment->customer_id) && !empty($appointment->client_phone)) {
                try {
                    $customer = Customer::syncFromAppointment($appointment);
                    $appointment->customer_id = $customer->id;
                } catch (\Throwable $e) {
                    // Fallback resiliente
                }
            }
        });
    }

    /**
     * Buscar si existe una cita activa que se solape con el intervalo horario dado.
     */
    public static function findConflicting(
        string|Carbon $date,
        string $startTime,
        string $endTime,
        ?int $excludeAppointmentId = null,
        ?int $tenantId = null
    ): ?self {
        $dateString = is_string($date) ? Carbon::parse($date)->toDateString() : $date->toDateString();
        $startFormatted = Carbon::parse($startTime)->format('H:i:s');
        $endFormatted = Carbon::parse($endTime)->format('H:i:s');

        $query = static::query()
            ->whereDate('appointment_date', $dateString)
            ->whereNotIn('status', [AppointmentStatus::CANCELLED->value])
            ->where(function ($q) {
                $q->whereIn('status', [
                    AppointmentStatus::CONFIRMED->value,
                    AppointmentStatus::PENDING_VERIFICATION->value,
                    AppointmentStatus::IN_PROGRESS->value,
                    AppointmentStatus::COMPLETED->value,
                ])
                ->orWhere(function ($sub) {
                    $sub->where('status', AppointmentStatus::PENDING_DEPOSIT->value)
                        ->where('created_at', '>=', Carbon::now()->subMinutes(15));
                });
            })
            ->where('start_time', '<', $endFormatted)
            ->where('end_time', '>', $startFormatted);

        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }

    /**
     * Determina si existe una cita conflictiva en el intervalo.
     */
    public static function hasConflict(
        string|Carbon $date,
        string $startTime,
        string $endTime,
        ?int $excludeAppointmentId = null,
        ?int $tenantId = null
    ): bool {
        return static::findConflicting($date, $startTime, $endTime, $excludeAppointmentId, $tenantId) !== null;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Determina si la cita corresponde a una clienta nueva (primera cita).
     */
    public function isClientNew(): bool
    {
        if ($this->customer) {
            return $this->customer->isNew();
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $this->client_phone);
        if (empty($cleanPhone)) {
            return true;
        }

        $count = static::where('client_phone', 'LIKE', "%{$cleanPhone}%")->count();
        return $count <= 1;
    }

    /**
     * Retorna la información de badge de fidelidad de la clienta.
     */
    public function getClientLoyaltyBadge(): array
    {
        if ($this->customer) {
            return $this->customer->getLoyaltyBadge();
        }

        $isNew = $this->isClientNew();

        return [
            'label' => $isNew ? '✨ Nueva' : '⭐ Registrada',
            'description' => $isNew ? 'Primera cita' : 'Cliente frecuente',
            'color' => $isNew ? 'amber' : 'emerald',
            'bg_class' => $isNew
                ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border-amber-200 dark:border-amber-800'
                : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
        ];
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', AppointmentStatus::CONFIRMED);
    }

    public function scopePendingVerification($query)
    {
        return $query->where('status', AppointmentStatus::PENDING_VERIFICATION);
    }

    /**
     * Determina si la cita puede ser autocancelada por la clienta.
     * Reglas de negocio:
     * 1. La cita no puede estar ya cancelada, completada o marcada como no asistió.
     * 2. Debe faltar estrictamente más de 24 horas para el inicio de la cita.
     */
    public function canBeCancelledByClient(): bool
    {
        if (in_array($this->status, [
            AppointmentStatus::CANCELLED,
            AppointmentStatus::COMPLETED,
            AppointmentStatus::NO_SHOW,
        ])) {
            return false;
        }

        $startDateTime = Carbon::parse($this->appointment_date->toDateString() . ' ' . $this->start_time);

        return now()->diffInSeconds($startDateTime, false) > (24 * 3600);
    }

    /**
     * Retorna las horas restantes para el inicio de la cita.
     */
    public function hoursUntilStart(): float
    {
        $startDateTime = Carbon::parse($this->appointment_date->toDateString() . ' ' . $this->start_time);

        return round(now()->diffInMinutes($startDateTime, false) / 60, 1);
    }

    /**
     * Determina si la cita puede ser reagendada por la clienta.
     * Reglas de negocio:
     * 1. No puede estar completada ni marcada como inasistencia.
     * 2. Si está cancelada, se permite reagendar para reactivarla a una nueva fecha.
     * 3. Si está activa, debe faltar estrictamente más de 24 horas para el inicio.
     */
    public function canBeRescheduledByClient(): bool
    {
        if (in_array($this->status, [
            AppointmentStatus::COMPLETED,
            AppointmentStatus::NO_SHOW,
        ])) {
            return false;
        }

        if ($this->status === AppointmentStatus::CANCELLED) {
            return true;
        }

        $startDateTime = Carbon::parse($this->appointment_date->toDateString() . ' ' . $this->start_time);

        return now()->diffInSeconds($startDateTime, false) > (24 * 3600);
    }
}
