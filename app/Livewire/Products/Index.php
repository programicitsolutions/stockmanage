<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Services\StockCalculator;
use App\Support\DecimalDisplay;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Products')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(StockCalculator $calculator): View
    {
        $products = Product::query()
            ->with('category')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(15);

        $stock = $calculator->forProductIds($products->getCollection()->pluck('id')->all());

        return view('livewire.products.index', [
            'products' => $products,
            'stock' => $stock,
            'formatQty' => DecimalDisplay::class,
        ]);
    }
}
