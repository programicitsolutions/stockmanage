<?php

namespace App\Enums;

enum RoleSlug: string
{
    case Partner = 'partner';
    case Accountant = 'accountant';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Partner => 'Partner',
            self::Accountant => 'Accountant',
            self::Admin => 'Admin',
        };
    }
}
