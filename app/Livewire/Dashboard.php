<?php

namespace App\Livewire;

use App\Enums\AdjustmentStatus;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockTransaction;
use App\Services\StockCalculator;
use App\Support\DecimalDisplay;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render(StockCalculator $calculator): View
    {
        $products = Product::query()->where('is_active', true)->get(['id', 'sku', 'name', 'unit', 'minimum_stock_level']);
        $stock = $calculator->forProductIds($products->pluck('id')->all());

        $lowStock = $products->filter(function (Product $product) use ($stock) {
            $present = $stock[$product->id] ?? '0.000';

            return bccomp($present, (string) $product->minimum_stock_level, 3) === -1;
        })->take(8)->values();

        return view('livewire.dashboard', [
            'productCount' => Product::query()->count(),
            'transactionCount' => StockTransaction::query()->count(),
            'pendingAdjustments' => StockAdjustment::query()->where('status', AdjustmentStatus::Pending)->count(),
            'stock' => $stock,
            'lowStock' => $lowStock,
            'formatQty' => DecimalDisplay::class,
        ]);
    }
}
