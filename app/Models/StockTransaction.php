<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Exceptions\ImmutableStockTransactionException;
use Database\Factories\StockTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'product_id',
    'transaction_type',
    'quantity',
    'unit_price',
    'reference_number',
    'supplier_id',
    'customer_id',
    'transaction_date',
    'notes',
    'created_by',
    'excel_import_id',
])]
class StockTransaction extends Model
{
    /** @use HasFactory<StockTransactionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'transaction_type' => TransactionType::class,
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw ImmutableStockTransactionException::cannotModify();
        });

        static::deleting(function (): void {
            throw ImmutableStockTransactionException::cannotModify();
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function costs(): HasMany
    {
        return $this->hasMany(StockTransactionCost::class);
    }

    public function signedQuantity(): string
    {
        $quantity = (string) $this->quantity;

        if ($this->transaction_type->increasesStock()) {
            return $quantity;
        }

        return bcmul($quantity, '-1', 3);
    }
}
