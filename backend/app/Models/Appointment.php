<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
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
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', AppointmentStatus::CONFIRMED);
    }

    public function scopePendingVerification($query)
    {
        return $query->where('status', AppointmentStatus::PENDING_VERIFICATION);
    }
}
