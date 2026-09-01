<?php

namespace App\Enums;

enum CostType: string
{
    case Purchase = 'purchase';
    case Transport = 'transport';
    case LoadingUnloading = 'loading_unloading';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Purchase price',
            self::Transport => 'Transport',
            self::LoadingUnloading => 'Loading / unloading',
            self::Other => 'Other charges',
        };
    }
}
