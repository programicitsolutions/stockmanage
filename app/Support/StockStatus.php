<?php

namespace App\Support;

class StockStatus
{
    public static function for(string $present, string $minimum): string
    {
        if (bccomp($present, '0', 3) !== 1) {
            return 'out';
        }

        if (bccomp($minimum, '0', 3) !== 1) {
            return 'healthy';
        }

        if (bccomp($present, $minimum, 3) !== -1) {
            return 'healthy';
        }

        $critical = bcdiv($minimum, '2', 3);

        if (bccomp($present, $critical, 3) !== 1) {
            return 'critical';
        }

        return 'low';
    }

    public static function label(string $status): string
    {
        return match ($status) {
            'out' => 'Out of stock',
            'critical' => 'Critical',
            'low' => 'Low stock',
            default => 'Healthy',
        };
    }
}
