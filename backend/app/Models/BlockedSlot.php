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
        'is_specific',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'blocked_date' => 'date',
            'is_recurring' => 'boolean',
            'is_specific' => 'boolean',
        ];
    }

    /**
     * Buscar si existe un bloqueo (que NO sea de almuerzo / bloqueo específico) que colisione con el intervalo horario dado.
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
                if (Schema::hasColumn('blocked_slots', 'is_specific')) {
                    $q->where('is_specific', true)
                      ->orWhere(function ($sub) {
                          $sub->whereNull('is_specific')
                              ->whereRaw("LOWER(title) NOT LIKE '%almuerzo%'")
                              ->whereRaw("LOWER(title) NOT LIKE '%lunch%'");
                      });
                } else {
                    $q->whereRaw("LOWER(title) NOT LIKE '%almuerzo%'")
                      ->whereRaw("LOWER(title) NOT LIKE '%lunch%'")
                      ->whereRaw("LOWER(COALESCE(reason, '')) NOT LIKE '%almuerzo%'")
                      ->whereRaw("LOWER(COALESCE(reason, '')) NOT LIKE '%lunch%'");
                }
            })
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
