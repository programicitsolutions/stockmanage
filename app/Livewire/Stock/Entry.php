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
use Illuminate\Support\Facades\DB;
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

    /**
     * @var list<array<string, mixed>>
     */
    public array $lines = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->canEnterStock(), 403);

        $this->mode = request()->routeIs('stock.out') ? 'out' : 'in';
        $this->transaction_date = now()->toDateString();
    }

    public function updatedProductId(): void
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
        $this->updatedProductId();
    }

    public function pickExactSku(): void
    {
        $code = trim($this->productSearch);
        if ($code === '') {
            return;
        }

        $product = Product::query()->where('is_active', true)
            ->where(function ($query) use ($code) {
                $query->where('sku', $code)->orWhere('sku', strtoupper($code));
            })
            ->first();

        if ($product) {
            $this->selectProduct((int) $product->id);
        }
    }

    public function addLine(StockCalculator $calculator): void
    {
        abort_unless(auth()->user()?->canEnterStock(), 403);

        $this->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $product = Product::query()->findOrFail($this->product_id);
        $qty = bcadd($this->quantity, '0', 3);
        $current = $calculator->forProductId((int) $product->id);

        foreach ($this->lines as $i => $line) {
            if ((int) $line['product_id'] === (int) $product->id) {
                $this->lines[$i]['quantity'] = bcadd((string) $line['quantity'], $qty, 3);
                $this->resetLineDraft();

                return;
            }
        }

        $this->lines[] = [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'unit' => $product->unit,
            'quantity' => $qty,
            'unit_price' => $this->unit_price !== '' ? $this->unit_price : null,
            'current' => $current,
        ];

        $this->resetLineDraft();
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
        $this->reviewed = false;
    }

    public function review(): void
    {
        abort_unless(auth()->user()?->canEnterStock(), 403);

        if ($this->lines !== []) {
            $this->validate($this->headerRules());
            $this->reviewed = true;

            return;
        }

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

        try {
            $ids = $this->lines !== []
                ? $this->postLines($calculator)
                : [$this->postSingle($calculator)];
        } catch (InsufficientStockException $exception) {
            $this->saving = false;
            $this->reviewed = false;

            throw ValidationException::withMessages([
                'quantity' => $exception->getMessage(),
            ]);
        }

        session([
            'stock_slip' => [
                'mode' => $this->mode,
                'reference' => $this->reference_number ?: null,
                'date' => $this->transaction_date,
                'notes' => $this->notes ?: null,
                'ids' => $ids,
            ],
        ]);

        session()->flash('status', $this->mode === 'in'
            ? 'Stock in posted to the ledger. Present stock increased.'
            : 'Stock out posted to the ledger. Present stock decreased.');

        $this->redirect(route('stock.slip'), navigate: true);
    }

    /**
     * @return list<int>
     */
    private function postLines(StockCalculator $calculator): array
    {
        $this->validate($this->headerRules());

        return DB::transaction(function () use ($calculator) {
            $ids = [];
            foreach ($this->lines as $line) {
                $ids[] = $calculator->record($this->payload(
                    (int) $line['product_id'],
                    (string) $line['quantity'],
                    $line['unit_price'] ?? null,
                ))->id;
            }

            return $ids;
        });
    }

    private function postSingle(StockCalculator $calculator): int
    {
        $validated = $this->validate($this->rules());

        if (($validated['unit_price'] ?? '') === '') {
            $validated['unit_price'] = null;
        }

        return $calculator->record($this->payload(
            (int) $validated['product_id'],
            (string) $validated['quantity'],
            $validated['unit_price'] !== '' ? $validated['unit_price'] : null,
        ))->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(int $productId, string $quantity, mixed $unitPrice): array
    {
        return [
            'product_id' => $productId,
            'transaction_type' => $this->mode === 'in' ? TransactionType::StockIn : TransactionType::StockOut,
            'quantity' => $quantity,
            'unit_price' => $unitPrice !== '' ? $unitPrice : null,
            'reference_number' => $this->reference_number ?: null,
            'supplier_id' => $this->mode === 'in' ? $this->supplier_id : null,
            'customer_id' => $this->mode === 'out' ? $this->customer_id : null,
            'transaction_date' => $this->transaction_date,
            'notes' => $this->notes ?: null,
            'created_by' => auth()->id(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function headerRules(): array
    {
        $rules = [
            'transaction_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        if ($this->mode === 'in') {
            $rules['supplier_id'] = ['nullable', 'exists:suppliers,id'];
        } else {
            $rules['customer_id'] = ['nullable', 'exists:customers,id'];
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return array_merge($this->headerRules(), [
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function resetLineDraft(): void
    {
        $this->product_id = null;
        $this->productSearch = '';
        $this->quantity = '';
        $this->unit_price = '';
        $this->reviewed = false;
    }

    public function render(StockCalculator $calculator): View
    {
        $previewLines = [];
        foreach ($this->lines as $line) {
            $current = $calculator->forProductId((int) $line['product_id']);
            $qty = (string) $line['quantity'];
            $projected = $this->mode === 'in'
                ? bcadd($current, $qty, 3)
                : bcsub($current, $qty, 3);
            $previewLines[] = array_merge($line, [
                'current' => $current,
                'projected' => $projected,
            ]);
        }

        $presentRaw = $this->product_id
            ? $calculator->forProductId((int) $this->product_id)
            : null;

        $productQuery = Product::query()->where('is_active', true)->orderBy('name');
        if ($this->productSearch !== '' && ! $this->product_id) {
            $term = '%'.$this->productSearch.'%';
            $productQuery->where(function ($query) use ($term) {
                $query->where('name', 'like', $term)->orWhere('sku', 'like', $term);
            });
        }

        return view('livewire.stock.entry', [
            'products' => $productQuery->limit(12)->get(),
            'selectedProduct' => $this->product_id ? Product::query()->find($this->product_id) : null,
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(),
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'present' => $presentRaw !== null ? DecimalDisplay::quantity($presentRaw) : null,
            'previewLines' => $previewLines,
            'formatQty' => DecimalDisplay::class,
        ])->title($this->mode === 'in' ? 'Stock in' : 'Stock out');
    }
}
