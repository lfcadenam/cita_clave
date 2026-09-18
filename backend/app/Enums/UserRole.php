<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case ADMIN = 'ADMIN';
    case CLIENT = 'CLIENT';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Administrador Nuvex',
            self::ADMIN => 'Administradora / Especialista',
            self::CLIENT => 'Clienta',
        };
    }
}
