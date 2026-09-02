<?php

namespace App\Livewire\Stock;

use App\Models\StockTransaction;
use App\Support\DecimalDisplay;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Slip extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->canEnterStock(), 403);

        if (! session('stock_slip.ids')) {
            $this->redirect(route('stock.movement'), navigate: true);
        }
    }

    public function render(): View
    {
        $payload = session('stock_slip', []);
        $rows = StockTransaction::query()
            ->with('product')
            ->whereIn('id', $payload['ids'] ?? [])
            ->orderBy('id')
            ->get();

        return view('livewire.stock.slip', [
            'payload' => $payload,
            'rows' => $rows,
            'formatQty' => DecimalDisplay::class,
        ])->title('Stock slip');
    }
}
