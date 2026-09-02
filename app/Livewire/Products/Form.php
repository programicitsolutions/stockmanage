<?php

namespace App\Livewire\Products;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?int $productId = null;

    public string $sku = '';

    public string $name = '';

    public ?int $category_id = null;

    public string $unit = 'pcs';

    public string $opening_stock = '0';

    public string $minimum_stock_level = '0';

    public string $default_purchase_price = '0';

    public string $default_selling_price = '0';

    public bool $is_active = true;

    public function mount(?Product $product = null): void
    {
        abort_unless(auth()->user()?->canManageProducts(), 403);

        if ($product?->exists) {
            $this->productId = $product->id;
            $this->sku = $product->sku;
            $this->name = $product->name;
            $this->category_id = $product->category_id;
            $this->unit = $product->unit;
            $this->opening_stock = (string) $product->opening_stock;
            $this->minimum_stock_level = (string) $product->minimum_stock_level;
            $this->default_purchase_price = (string) $product->default_purchase_price;
            $this->default_selling_price = (string) $product->default_selling_price;
            $this->is_active = $product->is_active;
        }
    }

    public function save(ProductCatalog $catalog): void
    {
        abort_unless(auth()->user()?->canManageProducts(), 403);

        $validated = $this->validate([
            'sku' => [
                'required',
                'string',
                'max:64',
                Rule::unique('products', 'sku')->ignore($this->productId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'unit' => ['required', 'string', 'max:32'],
            'opening_stock' => ['required', 'numeric', 'min:0'],
            'minimum_stock_level' => ['required', 'numeric', 'min:0'],
            'default_purchase_price' => ['required', 'numeric', 'min:0'],
            'default_selling_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        if ($this->productId) {
            $catalog->update(Product::query()->findOrFail($this->productId), [
                'sku' => $validated['sku'],
                'name' => $validated['name'],
                'category_id' => $validated['category_id'],
                'unit' => $validated['unit'],
                'minimum_stock_level' => $validated['minimum_stock_level'],
                'default_purchase_price' => $validated['default_purchase_price'],
                'default_selling_price' => $validated['default_selling_price'],
                'is_active' => $validated['is_active'],
            ]);
            session()->flash('status', 'Product updated. Present stock was not changed.');
        } else {
            $catalog->create($validated, auth()->user());
            session()->flash('status', 'Product saved. Opening stock was posted to the ledger.');
        }

        $this->redirect(route('products.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.products.form', [
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'editing' => (bool) $this->productId,
        ])->title($this->productId ? 'Edit product' : 'Add product');
    }
}
