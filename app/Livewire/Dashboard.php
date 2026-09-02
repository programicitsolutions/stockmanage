<?php

namespace App\Livewire;

use App\Services\StockInsights;
use App\Support\DecimalDisplay;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render(StockInsights $insights): View
    {
        $data = $insights->dashboard();

        return view('livewire.dashboard', [
            ...$data,
            'formatQty' => DecimalDisplay::class,
            'formatMoney' => DecimalDisplay::class,
        ]);
    }
}
