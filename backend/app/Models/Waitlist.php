<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Waitlist extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'service_id',
        'client_name',
        'client_phone',
        'client_email',
        'requested_date',
        'preferred_time_range',
        'status',
        'notes',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_date' => 'date',
            'notified_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
