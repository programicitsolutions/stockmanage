<?php

namespace App\Livewire\Stock;

use App\Models\Product;
use App\Services\StockCalculator;
use App\Support\DecimalDisplay;
use App\Support\StockStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Live stock')]
class LiveStock extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filter = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function render(StockCalculator $calculator): View
    {
        $base = Product::query()
            ->with('category')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('name');

        if (in_array($this->filter, ['low', 'critical', 'out'], true)) {
            $all = $base->get();
            $stock = $calculator->forProductIds($all->pluck('id')->all());
            $filtered = $all->filter(function (Product $product) use ($stock) {
                $present = $stock[$product->id] ?? '0.000';
                $status = StockStatus::for($present, (string) $product->minimum_stock_level);
                $product->setAttribute('present_stock_calculated', $present);
                $product->setAttribute('stock_status', $status);
                $product->setAttribute('is_low', in_array($status, ['low', 'critical', 'out'], true));

                return match ($this->filter) {
                    'low' => in_array($status, ['low', 'critical'], true),
                    'critical' => $status === 'critical',
                    'out' => $status === 'out',
                    default => true,
                };
            })->values();

            $page = LengthAwarePaginator::resolveCurrentPage();
            $perPage = 20;
            $products = new LengthAwarePaginator(
                $filtered->forPage($page, $perPage)->values(),
                $filtered->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        } else {
            $products = $base->paginate(20);
            $stock = $calculator->forProductIds($products->getCollection()->pluck('id')->all());
            $products->setCollection(
                $products->getCollection()->map(function (Product $product) use ($stock) {
                    $present = $stock[$product->id] ?? '0.000';
                    $status = StockStatus::for($present, (string) $product->minimum_stock_level);
                    $product->setAttribute('present_stock_calculated', $present);
                    $product->setAttribute('stock_status', $status);
                    $product->setAttribute('is_low', in_array($status, ['low', 'critical', 'out'], true));

                    return $product;
                })
            );
        }

        return view('livewire.stock.live-stock', [
            'products' => $products,
            'formatQty' => DecimalDisplay::class,
        ]);
    }
}
