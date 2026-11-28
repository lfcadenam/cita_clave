<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
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

    /**
     * Buscar si existe un bloqueo (que NO sea de almuerzo) que colisione con el intervalo horario dado.
     */
    public static function findConflictingNonLunch(
        string $date,
        string $startTime,
        string $endTime,
        ?int $tenantId = null
    ): ?self {
        $startFormatted = Carbon::parse($startTime)->format('H:i:s');
        $endFormatted = Carbon::parse($endTime)->format('H:i:s');
        $dateFormatted = Carbon::parse($date)->toDateString();

        $query = static::query()
            ->where(function ($q) use ($dateFormatted) {
                $q->where('is_recurring', true)
                  ->orWhereDate('blocked_date', $dateFormatted);
            })
            ->where(function ($q) {
                $q->whereRaw("LOWER(title) NOT LIKE '%almuerzo%'")
                  ->whereRaw("LOWER(COALESCE(reason, '')) NOT LIKE '%almuerzo%'");
            })
            ->where('start_time', '<', $endFormatted)
            ->where('end_time', '>', $startFormatted);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }
}
