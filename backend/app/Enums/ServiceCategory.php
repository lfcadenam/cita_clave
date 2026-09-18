<?php

namespace App\Enums;

enum ServiceCategory: string
{
    case FACIAL = 'FACIAL';                         // Limpiezas, hidrataciones, peelings
    case PESTANAS_CEJAS = 'PESTANAS_CEJAS';         // Pelo a pelo, lifting, laminado, microblading
    case LABIOS = 'LABIOS';                         // Hidratación labial, micropigmentación
    case CORPORAL_MASAJES = 'CORPORAL_MASAJES';     // Masajes relajantes, reductores, drenajes
    case DEPILACION = 'DEPILACION';                 // Cera, láser, hilo

    public function label(): string
    {
        return match ($this) {
            self::FACIAL => 'Cuidado Facial',
            self::PESTANAS_CEJAS => 'Pestañas & Cejas',
            self::LABIOS => 'Labios & Micropigmentación',
            self::CORPORAL_MASAJES => 'Corporal & Masajes',
            self::DEPILACION => 'Depilación Especializada',
        };
    }
}
