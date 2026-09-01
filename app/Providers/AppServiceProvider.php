<?php

namespace App\Providers;

use App\Models\StockTransaction;
use App\Observers\StockTransactionObserver;
use App\Services\StockCalculator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StockCalculator::class);
    }

    public function boot(): void
    {
        StockTransaction::observe(StockTransactionObserver::class);
    }
}
