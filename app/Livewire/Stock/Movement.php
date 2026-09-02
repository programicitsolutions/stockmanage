<?php

namespace App\Livewire\Stock;

use App\Enums\TransactionType;
use App\Models\StockTransaction;
use App\Support\DecimalDisplay;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Stock movement')]
class Movement extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $type = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $movements = StockTransaction::query()
            ->with(['product', 'supplier', 'customer', 'createdBy'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', '%'.$this->search.'%')
                        ->orWhere('notes', 'like', '%'.$this->search.'%')
                        ->orWhereHas('product', function ($query) {
                            $query->where('name', 'like', '%'.$this->search.'%')
                                ->orWhere('sku', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->type !== '', fn ($query) => $query->where('transaction_type', $this->type))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(20);

        return view('livewire.stock.movement', [
            'movements' => $movements,
            'types' => TransactionType::cases(),
            'formatQty' => DecimalDisplay::class,
            'formatMoney' => DecimalDisplay::class,
        ]);
    }
}
