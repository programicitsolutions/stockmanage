<?php

namespace App\Models;

use App\Enums\AdjustmentStatus;
use App\Enums\TransactionType;
use Database\Factories\StockAdjustmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'direction',
    'quantity',
    'status',
    'reason',
    'notes',
    'requested_by',
    'reviewed_by',
    'reviewed_at',
    'applied_transaction_id',
])]
class StockAdjustment extends Model
{
    /** @use HasFactory<StockAdjustmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'status' => AdjustmentStatus::class,
            'reviewed_at' => 'datetime',
            'direction' => TransactionType::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function appliedTransaction(): BelongsTo
    {
        return $this->belongsTo(StockTransaction::class, 'applied_transaction_id');
    }
}
