<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case PENDING_DEPOSIT = 'PENDING_DEPOSIT';             // Esperando pago de abono en pasarela
    case PENDING_VERIFICATION = 'PENDING_VERIFICATION';   // Comprobante Nequi subido, pendiente por validar por Paola
    case CONFIRMED = 'CONFIRMED';                         // Cita confirmada (Abono recibido)
    case IN_PROGRESS = 'IN_PROGRESS';                     // En atención en el local
    case COMPLETED = 'COMPLETED';                         // Servicio terminado y saldo cobrado
    case CANCELLED = 'CANCELLED';                         // Cancelada por clienta o administradora
    case NO_SHOW = 'NO_SHOW';                             // No asistió (abono retenido)

    public function label(): string
    {
        return match ($this) {
            self::PENDING_DEPOSIT => '⏳ Pendiente de Abono',
            self::PENDING_VERIFICATION => '📸 Comprobante Nequi por Verificar',
            self::CONFIRMED => '✅ Cita Confirmada',
            self::IN_PROGRESS => '💆‍♀️ En Atención',
            self::COMPLETED => '🎉 Servicio Completado',
            self::CANCELLED => '❌ Cancelada',
            self::NO_SHOW => '⚠️ Inasistencia (No-Show)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING_DEPOSIT => 'gray',
            self::PENDING_VERIFICATION => 'warning',
            self::CONFIRMED => 'success',
            self::IN_PROGRESS => 'info',
            self::COMPLETED => 'primary',
            self::CANCELLED => 'danger',
            self::NO_SHOW => 'danger',
        };
    }
}
