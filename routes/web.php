<?php

use App\Livewire\Admin\ImportExcel;
use App\Livewire\Admin\Users;
use App\Livewire\Adjustments;
use App\Livewire\AuditHistory;
use App\Livewire\Catalog\Categories;
use App\Livewire\Catalog\Customers;
use App\Livewire\Catalog\Suppliers;
use App\Livewire\Dashboard;
use App\Livewire\Products\Form as ProductForm;
use App\Livewire\Products\Index as ProductIndex;
use App\Livewire\Products\Show as ProductShow;
use App\Livewire\HelpChat;
use App\Livewire\Reports;
use App\Livewire\Stock\Entry as StockEntry;
use App\Livewire\Stock\LiveStock;
use App\Livewire\Stock\Movement;
use App\Livewire\Stock\Slip as StockSlip;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'role:partner,accountant,admin'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
    Route::get('stock', LiveStock::class)->name('stock.live');
    Route::get('stock/movement', Movement::class)->name('stock.movement');
    Route::get('products', ProductIndex::class)->name('products.index');
    Route::get('products/{product}', ProductShow::class)->whereNumber('product')->name('products.show');
    Route::get('categories', Categories::class)->name('categories.index');
    Route::get('suppliers', Suppliers::class)->name('suppliers.index');
    Route::get('customers', Customers::class)->name('customers.index');
    Route::get('adjustments', Adjustments::class)->name('adjustments.index');
    Route::get('audit', AuditHistory::class)->name('audit.index');
    Route::get('assistant', HelpChat::class)->name('assistant');
    Route::get('reports', Reports::class)->name('reports.index');
    Route::view('profile', 'profile')->name('profile');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('import', ImportExcel::class)->name('import.excel');
    Route::get('users', Users::class)->name('users.index');
});

Route::middleware(['auth', 'role:accountant,admin'])->group(function () {
    Route::get('products/create', ProductForm::class)->name('products.create');
    Route::get('products/{product}/edit', ProductForm::class)->whereNumber('product')->name('products.edit');
    Route::get('stock/in', StockEntry::class)->name('stock.in');
    Route::get('stock/out', StockEntry::class)->name('stock.out');
    Route::get('stock/slip', StockSlip::class)->name('stock.slip');
});

require __DIR__.'/auth.php';
