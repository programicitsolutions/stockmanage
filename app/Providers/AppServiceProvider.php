<?php

namespace App\Providers;

use App\Models\StockTransaction;
use App\Observers\StockTransactionObserver;
use App\Services\ProductCatalog;
use App\Services\StockAdjustmentService;
use App\Services\StockCalculator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StockCalculator::class);
        $this->app->singleton(ProductCatalog::class);
        $this->app->singleton(StockAdjustmentService::class);
    }

    public function boot(): void
    {
        StockTransaction::observe(StockTransactionObserver::class);
    }
}
