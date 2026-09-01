<?php

namespace App\Models;

use App\Enums\CostType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['stock_transaction_id', 'cost_type', 'amount', 'notes'])]
class StockTransactionCost extends Model
{
    protected function casts(): array
    {
        return [
            'cost_type' => CostType::class,
            'amount' => 'decimal:2',
        ];
    }

    public function stockTransaction(): BelongsTo
    {
        return $this->belongsTo(StockTransaction::class);
    }
}
