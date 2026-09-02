<?php

namespace App\Enums;

enum ProductKind: string
{
    case Main = 'main';
    case Inner = 'inner';

    public function label(): string
    {
        return match ($this) {
            self::Main => 'Main product',
            self::Inner => 'Inner product',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Main => 'Finished goods you sell or issue (tins, bottles, cartons).',
            self::Inner => 'Parts used inside or with a main product (lids, liners, plugs, inserts).',
        };
    }
}
