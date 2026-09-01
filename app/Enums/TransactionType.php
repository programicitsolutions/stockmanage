<?php

namespace App\Enums;

enum TransactionType: string
{
    case Opening = 'OPENING';
    case StockIn = 'STOCK_IN';
    case StockOut = 'STOCK_OUT';
    case AdjustmentIn = 'ADJUSTMENT_IN';
    case AdjustmentOut = 'ADJUSTMENT_OUT';

    public function increasesStock(): bool
    {
        return match ($this) {
            self::Opening, self::StockIn, self::AdjustmentIn => true,
            self::StockOut, self::AdjustmentOut => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Opening => 'Opening stock',
            self::StockIn => 'Stock in',
            self::StockOut => 'Stock out',
            self::AdjustmentIn => 'Adjustment in',
            self::AdjustmentOut => 'Adjustment out',
        };
    }
}
