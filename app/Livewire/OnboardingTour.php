<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class OnboardingTour extends Component
{
    public bool $open = false;

    public int $step = 0;

    /**
     * @var list<array{title: string, body: string, target: string}>
     */
    public array $steps = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->open = $user !== null && $user->onboarding_completed_at === null;
        $this->steps = $this->buildSteps();
    }

    public function startTour(): void
    {
        $this->step = 1;
        $this->open = true;
    }

    public function next(): void
    {
        if ($this->step >= count($this->steps) - 1) {
            $this->complete();

            return;
        }

        $this->step++;
    }

    public function back(): void
    {
        if ($this->step > 0) {
            $this->step--;
        }
    }

    public function complete(): void
    {
        $user = auth()->user();
        if ($user && $user->onboarding_completed_at === null) {
            $user->forceFill(['onboarding_completed_at' => now()])->save();
        }

        $this->open = false;
        $this->step = 0;
    }

    #[On('replay-onboarding')]
    public function replay(): void
    {
        $this->step = 0;
        $this->open = true;
    }

    public function render(): View
    {
        return view('livewire.onboarding-tour', [
            'current' => $this->steps[$this->step] ?? $this->steps[0],
            'total' => count($this->steps),
        ]);
    }

    /**
     * @return list<array{title: string, body: string, target: string}>
     */
    private function buildSteps(): array
    {
        $name = auth()->user()?->name ?? 'there';

        return [
            [
                'title' => "Welcome, {$name}",
                'body' => 'This is your stock workspace. Present stock is calculated from the ledger — nobody types the quantity into a product. Follow a short tour, then you can skip it anytime.',
                'target' => '',
            ],
            [
                'title' => 'Dashboard',
                'body' => 'These cards are live: products, available stock, value, low stock, and today’s in/out. Nothing here is a demo number.',
                'target' => 'tour-dashboard',
            ],
            [
                'title' => 'Stock in',
                'body' => 'Accountants search a product, enter quantity, review current + in = new stock, then confirm. That posts STOCK_IN to the ledger.',
                'target' => 'tour-stock-in',
            ],
            [
                'title' => 'Stock out',
                'body' => 'Issues and sales go here. You cannot take stock below zero. Review remaining stock before save.',
                'target' => 'tour-stock-out',
            ],
            [
                'title' => 'Main vs inner products',
                'body' => 'Main products are finished goods (tins, bottles, cartons). Inner products are parts used with them (lids, liners, plugs). Stock is still one ledger for both.',
                'target' => 'tour-products',
            ],
            [
                'title' => 'Adjustments',
                'body' => 'If a physical count differs from system stock, request an adjustment. A manager must approve it. You cannot approve your own request.',
                'target' => 'tour-adjustments',
            ],
            [
                'title' => 'You are ready',
                'body' => 'Use Reports and Audit when you need the story behind a number. Replay this tour from your profile menu.',
                'target' => 'tour-reports',
            ],
        ];
    }
}
