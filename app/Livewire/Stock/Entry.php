<?php

namespace App\Livewire\Stock;

use App\Enums\TransactionType;
use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\StockCalculator;
use App\Support\DecimalDisplay;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Entry extends Component
{
    public string $mode = 'in';

    public ?int $product_id = null;

    public string $productSearch = '';

    public string $quantity = '';

    public string $unit_price = '';

    public string $reference_number = '';

    public ?int $supplier_id = null;

    public ?int $customer_id = null;

    public string $transaction_date = '';

    public string $notes = '';

    public bool $reviewed = false;

    public bool $saving = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canEnterStock(), 403);

        $this->mode = request()->routeIs('stock.out') ? 'out' : 'in';
        $this->transaction_date = now()->toDateString();
    }

    public function updatedProductId(StockCalculator $calculator): void
    {
        $this->reviewed = false;

        if (! $this->product_id) {
            return;
        }

        $product = Product::query()->find($this->product_id);

        if (! $product) {
            return;
        }

        $this->productSearch = $product->sku.' — '.$product->name;
        $this->unit_price = $this->mode === 'in'
            ? (string) $product->default_purchase_price
            : (string) $product->default_selling_price;
    }

    public function updatedQuantity(): void
    {
        $this->reviewed = false;
    }

    public function selectProduct(int $productId): void
    {
        $this->product_id = $productId;
        $this->updatedProductId(app(StockCalculator::class));
    }

    public function review(): void
    {
        abort_unless(auth()->user()?->canEnterStock(), 403);

        $this->validate($this->rules());
        $this->reviewed = true;
    }

    public function editEntry(): void
    {
        $this->reviewed = false;
        $this->saving = false;
    }

    public function save(StockCalculator $calculator): void
    {
        abort_unless(auth()->user()?->canEnterStock(), 403);

        if ($this->saving) {
            return;
        }

        $this->saving = true;

        $validated = $this->validate($this->rules());

        if (($validated['unit_price'] ?? '') === '') {
            $validated['unit_price'] = null;
        }

        try {
            $calculator->record([
                'product_id' => $validated['product_id'],
                'transaction_type' => $this->mode === 'in' ? TransactionType::StockIn : TransactionType::StockOut,
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'] !== '' ? $validated['unit_price'] : null,
                'reference_number' => $validated['reference_number'] ?: null,
                'supplier_id' => $this->mode === 'in' ? ($validated['supplier_id'] ?? null) : null,
                'customer_id' => $this->mode === 'out' ? ($validated['customer_id'] ?? null) : null,
                'transaction_date' => $validated['transaction_date'],
                'notes' => $validated['notes'] ?: null,
                'created_by' => auth()->id(),
            ]);
        } catch (InsufficientStockException $exception) {
            $this->saving = false;
            $this->reviewed = false;

            throw ValidationException::withMessages([
                'quantity' => $exception->getMessage(),
            ]);
        }

        session()->flash('status', $this->mode === 'in'
            ? 'Stock in posted to the ledger. Present stock increased.'
            : 'Stock out posted to the ledger. Present stock decreased.');

        $this->redirect(route('stock.movement'), navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        $rules = [
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:64'],
            'transaction_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        if ($this->mode === 'in') {
            $rules['supplier_id'] = ['nullable', 'exists:suppliers,id'];
        } else {
            $rules['customer_id'] = ['nullable', 'exists:customers,id'];
        }

        return $rules;
    }

    public function render(StockCalculator $calculator): View
    {
        $presentRaw = $this->product_id
            ? $calculator->forProductId((int) $this->product_id)
            : null;

        $quantity = $this->quantity !== '' && is_numeric($this->quantity)
            ? bcadd($this->quantity, '0', 3)
            : null;

        $projected = null;
        if ($presentRaw !== null && $quantity !== null && bccomp($quantity, '0', 3) === 1) {
            $projected = $this->mode === 'in'
                ? bcadd($presentRaw, $quantity, 3)
                : bcsub($presentRaw, $quantity, 3);
        }

        $productQuery = Product::query()->where('is_active', true)->orderBy('name');

        if ($this->productSearch !== '' && ! $this->product_id) {
            $term = '%'.$this->productSearch.'%';
            $productQuery->where(function ($query) use ($term) {
                $query->where('name', 'like', $term)->orWhere('sku', 'like', $term);
            });
        }

        $selected = $this->product_id
            ? Product::query()->find($this->product_id)
            : null;

        return view('livewire.stock.entry', [
            'products' => $productQuery->limit(20)->get(),
            'selectedProduct' => $selected,
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'present' => $presentRaw !== null ? DecimalDisplay::quantity($presentRaw) : null,
            'presentRaw' => $presentRaw,
            'quantityNormalized' => $quantity,
            'projected' => $projected,
            'formatQty' => DecimalDisplay::class,
        ])->title($this->mode === 'in' ? 'Stock in' : 'Stock out');
    }
}
