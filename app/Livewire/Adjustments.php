<?php

namespace App\Livewire;

use App\Enums\TransactionType;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\StockAdjustmentService;
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

    public string $direction = 'ADJUSTMENT_IN';

    public string $quantity = '';

    public string $reason = '';

    public string $notes = '';

    public function requestAdjustment(StockAdjustmentService $service): void
    {
        abort_unless(auth()->user()?->isAccountant(), 403);

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
        $this->reset('product_id', 'quantity', 'reason', 'notes');
        $this->direction = TransactionType::AdjustmentIn->value;
        session()->flash('status', 'Adjustment requested. A partner must approve it before stock changes.');
    }

    public function approve(int $adjustmentId, StockAdjustmentService $service): void
    {
        abort_unless(auth()->user()?->isPartner(), 403);

        $adjustment = StockAdjustment::query()->findOrFail($adjustmentId);

        try {
            $service->approveAndApply($adjustment, auth()->user());
        } catch (InsufficientStockException $exception) {
            throw ValidationException::withMessages([
                'approve' => $exception->getMessage(),
            ]);
        }

        session()->flash('status', 'Adjustment approved and posted to the ledger.');
    }

    public function reject(int $adjustmentId, StockAdjustmentService $service): void
    {
        abort_unless(auth()->user()?->isPartner(), 403);

        $service->reject(StockAdjustment::query()->findOrFail($adjustmentId), auth()->user());
        session()->flash('status', 'Adjustment rejected. Present stock was not changed.');
    }

    public function render(): View
    {
        return view('livewire.adjustments', [
            'adjustments' => StockAdjustment::query()
                ->with(['product', 'requestedBy', 'reviewedBy'])
                ->latest()
                ->paginate(15),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
            'formatQty' => DecimalDisplay::class,
        ]);
    }
}
