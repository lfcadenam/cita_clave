<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BOLD_ONLINE = 'BOLD_ONLINE';             // Pasarela en línea (PSE, Tarjetas, Nequi)
    case NEQUI_TRANSFER = 'NEQUI_TRANSFER';       // Transferencia directa manual a Nequi / Daviplata
    case CASH_AT_LOCATION = 'CASH_AT_LOCATION';   // Efectivo / Datáfono en el local (Saldo restante)

    public function label(): string
    {
        return match ($this) {
            self::BOLD_ONLINE => '💳 Pago en Línea (Bold / PSE / Tarjeta)',
            self::NEQUI_TRANSFER => '📲 Transferencia Directa Nequi / Daviplata',
            self::CASH_AT_LOCATION => '💵 Pago en Local (Efectivo / Datáfono)',
        };
    }
}
