<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Services\StockCalculator;
use App\Support\DecimalDisplay;
use App\Support\StockStatus;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product->loadMissing('category');
    }

    public function render(StockCalculator $calculator): View
    {
        $summary = $calculator->ledgerSummary((int) $this->product->id);
        $status = StockStatus::for($summary['present'], (string) $this->product->minimum_stock_level);

        return view('livewire.products.show', [
            'summary' => $summary,
            'status' => $status,
            'economics' => app(\App\Services\LandingCostService::class)->productCard($this->product, $summary['present']),
            'formatQty' => DecimalDisplay::class,
            'formatMoney' => DecimalDisplay::class,
        ])->title($this->product->name);
    }
}
