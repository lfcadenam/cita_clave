<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
     * Buscar si existe un bloqueo no recurrente (puntual / inamovible) que colisione con el intervalo horario dado.
     * Si is_recurring es true -> se permite mover.
     * Si is_recurring es false -> NO se permite mover.
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
            ->where('is_recurring', false)
            ->whereDate('blocked_date', $dateFormatted)
            ->where('start_time', '<', $endFormatted)
            ->where('end_time', '>', $startFormatted);

        if ($tenantId) {
            $query->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            });
        }

        return $query->first();
    }
}
