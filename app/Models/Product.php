<?php

namespace App\Models;

use App\Services\StockCalculator;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'sku',
    'name',
    'category_id',
    'unit',
    'opening_stock',
    'minimum_stock_level',
    'default_purchase_price',
    'default_selling_price',
    'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'opening_stock' => 'decimal:3',
            'minimum_stock_level' => 'decimal:3',
            'default_purchase_price' => 'decimal:2',
            'default_selling_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    /**
     * Present (available) stock from the transaction ledger.
     * This value is calculated and is never stored as an editable column.
     */
    public function presentStock(): string
    {
        return app(StockCalculator::class)->forProduct($this);
    }

    public function isBelowMinimumStock(): bool
    {
        return bccomp($this->presentStock(), (string) $this->minimum_stock_level, 3) === -1;
    }
}
