<?php

namespace App\Livewire\Products;

use App\Models\Category;
use App\Models\Product;
use App\Services\StockCalculator;
use App\Support\DecimalDisplay;
use App\Support\StockStatus;
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

    #[Url]
    public string $category = '';

    #[Url]
    public string $kind = 'all';

    #[Url]
    public string $status = 'all';

    #[Url]
    public string $sort = 'name';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function updatingKind(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render(StockCalculator $calculator): View
    {
        $query = Product::query()
            ->with('category')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->category !== '', fn ($query) => $query->where('category_id', $this->category))
            ->when($this->kind === 'main', fn ($query) => $query->where('kind', 'main'))
            ->when($this->kind === 'inner', fn ($query) => $query->where('kind', 'inner'))
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false));

        $sort = in_array($this->sort, ['name', 'sku'], true) ? $this->sort : 'name';
        $products = $query->orderBy($sort)->paginate(15);
        $stock = $calculator->forProductIds($products->getCollection()->pluck('id')->all());

        $products->setCollection(
            $products->getCollection()->map(function (Product $product) use ($stock) {
                $present = $stock[$product->id] ?? '0.000';
                $product->setAttribute('present_stock_calculated', $present);
                $product->setAttribute('stock_status', StockStatus::for($present, (string) $product->minimum_stock_level));

                return $product;
            })
        );

        return view('livewire.products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(),
            'formatQty' => DecimalDisplay::class,
        ]);
    }
}
