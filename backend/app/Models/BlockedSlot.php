<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedSlot extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'title',
        'blocked_date',
        'start_time',
        'end_time',
        'is_recurring',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'blocked_date' => 'date',
            'is_recurring' => 'boolean',
        ];
    }
}
