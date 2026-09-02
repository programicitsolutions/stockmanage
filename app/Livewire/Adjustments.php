<?php

namespace App\Livewire;

use App\Enums\TransactionType;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\StockAdjustmentService;
use App\Services\StockCalculator;
use App\Support\DecimalDisplay;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Stock adjustments')]
class Adjustments extends Component
{
    use WithPagination;

    public ?int $product_id = null;

    public string $productSearch = '';

    public string $direction = 'ADJUSTMENT_IN';

    public string $quantity = '';

    public string $physical_qty = '';

    public string $reason = '';

    public string $notes = '';

    public bool $saving = false;

    /**
     * @var list<array<string, mixed>>
     */
    public array $countLines = [];

    public function selectProduct(int $productId): void
    {
        $this->product_id = $productId;
        $product = Product::query()->find($productId);
        $this->productSearch = $product ? $product->sku.' — '.$product->name : '';
    }

    public function requestAdjustment(StockAdjustmentService $service, StockCalculator $calculator): void
    {
        abort_unless(auth()->user()?->canRequestAdjustments(), 403);

        if ($this->saving) {
            return;
        }

        $this->saving = true;

        try {
            if ($this->physical_qty !== '') {
                $validated = $this->validate([
                    'product_id' => ['required', 'exists:products,id'],
                    'physical_qty' => ['required', 'numeric', 'min:0'],
                    'reason' => ['required', 'string', 'max:255'],
                    'notes' => ['nullable', 'string', 'max:2000'],
                ]);

                $system = $calculator->forProductId((int) $validated['product_id']);
                $physical = bcadd((string) $validated['physical_qty'], '0', 3);
                $difference = bcsub($physical, $system, 3);

                if (bccomp($difference, '0', 3) === 0) {
                    throw ValidationException::withMessages([
                        'physical_qty' => 'Physical count matches system stock. No adjustment is needed.',
                    ]);
                }

                $direction = bccomp($difference, '0', 3) === 1
                    ? TransactionType::AdjustmentIn
                    : TransactionType::AdjustmentOut;

                $absQty = bccomp($difference, '0', 3) === -1
                    ? bcmul($difference, '-1', 3)
                    : $difference;

                $service->request([
                    'product_id' => $validated['product_id'],
                    'direction' => $direction,
                    'quantity' => $absQty,
                    'reason' => $validated['reason'],
                    'notes' => $validated['notes'] ?? null,
                    'system_qty' => $system,
                    'physical_qty' => $physical,
                ], auth()->user());
            } else {
                $validated = $this->validate([
                    'product_id' => ['required', 'exists:products,id'],
                    'direction' => ['required', Rule::in([
                        TransactionType::AdjustmentIn->value,
                        TransactionType::AdjustmentOut->value,
                    ])],
                    'quantity' => ['required', 'numeric', 'gt:0'],
                    'reason' => ['required', 'string', 'max:255'],
                    'notes' => ['nullable', 'string', 'max:2000'],
                ]);

                $service->request($validated, auth()->user());
            }
        } catch (ValidationException $exception) {
            $this->saving = false;

            throw $exception;
        }

        $this->reset('product_id', 'productSearch', 'quantity', 'physical_qty', 'reason', 'notes');
        $this->direction = TransactionType::AdjustmentIn->value;
        $this->saving = false;
        session()->flash('status', 'Adjustment requested. A manager or admin must approve it before stock changes.');
    }

    public function addCountLine(StockCalculator $calculator): void
    {
        abort_unless(auth()->user()?->canRequestAdjustments(), 403);

        $validated = $this->validate([
            'product_id' => ['required', 'exists:products,id'],
            'physical_qty' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $system = $calculator->forProductId((int) $validated['product_id']);
        $physical = bcadd((string) $validated['physical_qty'], '0', 3);
        $difference = bcsub($physical, $system, 3);

        if (bccomp($difference, '0', 3) === 0) {
            throw ValidationException::withMessages([
                'physical_qty' => 'Physical count matches system stock. No adjustment is needed.',
            ]);
        }

        $product = Product::query()->findOrFail($validated['product_id']);

        $this->countLines[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'system' => $system,
            'physical' => $physical,
            'difference' => $difference,
            'reason' => $validated['reason'],
            'notes' => $this->notes ?: null,
        ];

        $this->reset('product_id', 'productSearch', 'physical_qty');
    }

    public function removeCountLine(int $index): void
    {
        unset($this->countLines[$index]);
        $this->countLines = array_values($this->countLines);
    }

    public function submitCountSheet(StockAdjustmentService $service): void
    {
        abort_unless(auth()->user()?->canRequestAdjustments(), 403);

        if ($this->countLines === []) {
            throw ValidationException::withMessages([
                'physical_qty' => 'Add at least one counted product to the sheet.',
            ]);
        }

        foreach ($this->countLines as $line) {
            $difference = (string) $line['difference'];
            $direction = bccomp($difference, '0', 3) === 1
                ? TransactionType::AdjustmentIn
                : TransactionType::AdjustmentOut;
            $absQty = bccomp($difference, '0', 3) === -1
                ? bcmul($difference, '-1', 3)
                : $difference;

            $service->request([
                'product_id' => $line['product_id'],
                'direction' => $direction,
                'quantity' => $absQty,
                'reason' => $line['reason'],
                'notes' => $line['notes'] ?? null,
                'system_qty' => $line['system'],
                'physical_qty' => $line['physical'],
            ], auth()->user());
        }

        $this->countLines = [];
        session()->flash('status', 'Count sheet submitted. Each line is a pending adjustment for a manager to approve.');
    }

    public function approve(int $adjustmentId, StockAdjustmentService $service): void
    {
        abort_unless(auth()->user()?->canApproveAdjustments(), 403);

        $adjustment = StockAdjustment::query()->findOrFail($adjustmentId);

        try {
            $service->approveAndApply($adjustment, auth()->user());
        } catch (InsufficientStockException $exception) {
            throw ValidationException::withMessages([
                'approve' => $exception->getMessage(),
            ]);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([
                'approve' => $exception->validator->errors()->first() ?: 'This adjustment cannot be approved.',
            ]);
        }

        session()->flash('status', 'Adjustment approved and posted to the ledger.');
    }

    public function reject(int $adjustmentId, StockAdjustmentService $service): void
    {
        abort_unless(auth()->user()?->canApproveAdjustments(), 403);

        try {
            $service->reject(StockAdjustment::query()->findOrFail($adjustmentId), auth()->user());
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([
                'approve' => $exception->validator->errors()->first() ?: 'This adjustment cannot be rejected.',
            ]);
        }

        session()->flash('status', 'Adjustment rejected. Present stock was not changed.');
    }

    public function render(StockCalculator $calculator): View
    {
        $systemQty = $this->product_id
            ? $calculator->forProductId((int) $this->product_id)
            : null;

        $physical = $this->physical_qty !== '' && is_numeric($this->physical_qty)
            ? bcadd($this->physical_qty, '0', 3)
            : null;

        $difference = $systemQty !== null && $physical !== null
            ? bcsub($physical, $systemQty, 3)
            : null;

        $productQuery = Product::query()->where('is_active', true)->orderBy('name');
        if ($this->productSearch !== '' && ! $this->product_id) {
            $term = '%'.$this->productSearch.'%';
            $productQuery->where(function ($query) use ($term) {
                $query->where('name', 'like', $term)->orWhere('sku', 'like', $term);
            });
        }

        return view('livewire.adjustments', [
            'adjustments' => StockAdjustment::query()
                ->with(['product', 'requestedBy', 'reviewedBy'])
                ->latest()
                ->paginate(15),
            'products' => $productQuery->limit(20)->get(),
            'selectedProduct' => $this->product_id ? Product::query()->find($this->product_id) : null,
            'systemQty' => $systemQty,
            'difference' => $difference,
            'countLines' => $this->countLines,
            'formatQty' => DecimalDisplay::class,
        ]);
    }
}
