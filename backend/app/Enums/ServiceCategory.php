<?php

namespace App\Enums;

enum ServiceCategory: string
{
    case FACIAL = 'FACIAL';                         // Limpiezas, hidrataciones, peelings
    case PESTANAS_CEJAS = 'PESTANAS_CEJAS';         // Pelo a pelo, lifting, laminado, microblading
    case LABIOS = 'LABIOS';                         // Hidratación labial, micropigmentación
    case CORPORAL_MASAJES = 'CORPORAL_MASAJES';     // Masajes relajantes, reductores, drenajes
    case DEPILACION = 'DEPILACION';                 // Cera, láser, hilo
    case MANICURA = 'MANICURA';                     // Limpieza rusa, rubber, semipermanente
    case PEDICURA = 'PEDICURA';                     // Limpieza pedicura, spa, semipermanente
    case SISTEMAS_ARTIFICIALES = 'SISTEMAS_ARTIFICIALES'; // Polygel, acrílico, extensiones
    case REPARACION_RETIRO = 'REPARACION_RETIRO';   // Remoción semi, remiendos, uñas individuales

    public function label(): string
    {
        return match ($this) {
            self::FACIAL => 'Cuidado Facial',
            self::PESTANAS_CEJAS => 'Pestañas & Cejas',
            self::LABIOS => 'Labios & Micropigmentación',
            self::CORPORAL_MASAJES => 'Corporal & Masajes',
            self::DEPILACION => 'Depilación Especializada',
            self::MANICURA => 'Manicura',
            self::PEDICURA => 'Pedicura',
            self::SISTEMAS_ARTIFICIALES => 'Sistemas & Polygel',
            self::REPARACION_RETIRO => 'Retiro & Reparaciones',
        };
    }
}
