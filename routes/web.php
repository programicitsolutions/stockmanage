<?php

use App\Livewire\Adjustments;
use App\Livewire\AuditHistory;
use App\Livewire\Catalog\Categories;
use App\Livewire\Catalog\Customers;
use App\Livewire\Catalog\Suppliers;
use App\Livewire\Dashboard;
use App\Livewire\Products\Form as ProductForm;
use App\Livewire\Products\Index as ProductIndex;
use App\Livewire\Stock\Entry as StockEntry;
use App\Livewire\Stock\LiveStock;
use App\Livewire\Stock\Movement;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'role:partner,accountant'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
    Route::get('stock', LiveStock::class)->name('stock.live');
    Route::get('stock/movement', Movement::class)->name('stock.movement');
    Route::get('products', ProductIndex::class)->name('products.index');
    Route::get('categories', Categories::class)->name('categories.index');
    Route::get('suppliers', Suppliers::class)->name('suppliers.index');
    Route::get('customers', Customers::class)->name('customers.index');
    Route::get('adjustments', Adjustments::class)->name('adjustments.index');
    Route::get('audit', AuditHistory::class)->name('audit.index');
    Route::view('profile', 'profile')->name('profile');
});

Route::middleware(['auth', 'role:accountant'])->group(function () {
    Route::get('products/create', ProductForm::class)->name('products.create');
    Route::get('products/{product}/edit', ProductForm::class)->name('products.edit');
    Route::get('stock/in', StockEntry::class)->name('stock.in');
    Route::get('stock/out', StockEntry::class)->name('stock.out');
});

require __DIR__.'/auth.php';
