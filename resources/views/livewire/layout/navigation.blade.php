<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect(route('login'), navigate: true);
    }

    public function replayTour(): void
    {
        $this->dispatch('replay-onboarding');
    }

    public function openAssistant(): void
    {
        $this->dispatch('open-help-chat');
    }
}; ?>

@php
    $user = auth()->user();
    $groups = [
        [
            'label' => 'Overview',
            'items' => [
                ['Dashboard', route('dashboard'), request()->routeIs('dashboard'), 'tour-dashboard', true],
                ['Live stock', route('stock.live'), request()->routeIs('stock.live'), 'tour-live', true],
                ['Reports', route('reports.index'), request()->routeIs('reports.*'), 'tour-reports', true],
            ],
        ],
        [
            'label' => 'Operations',
            'items' => [
                ['Stock in', route('stock.in'), request()->routeIs('stock.in'), 'tour-stock-in', $user->canEnterStock()],
                ['Stock out', route('stock.out'), request()->routeIs('stock.out'), 'tour-stock-out', $user->canEnterStock()],
                ['Movement', route('stock.movement'), request()->routeIs('stock.movement'), 'tour-movement', true],
                ['Adjustments', route('adjustments.index'), request()->routeIs('adjustments.*'), 'tour-adjustments', true],
            ],
        ],
        [
            'label' => 'Catalog',
            'items' => [
                ['Products', route('products.index'), request()->routeIs('products.*'), 'tour-products', true],
                ['Categories', route('categories.index'), request()->routeIs('categories.*'), '', true],
                ['Suppliers', route('suppliers.index'), request()->routeIs('suppliers.*'), '', true],
                ['Customers', route('customers.index'), request()->routeIs('customers.*'), '', true],
            ],
        ],
        [
            'label' => 'Control',
            'items' => [
                ['Audit', route('audit.index'), request()->routeIs('audit.*'), 'tour-audit', true],
                ['Users', route('users.index'), request()->routeIs('users.*'), '', $user->canManageUsers()],
            ],
        ],
    ];
@endphp

<aside class="flex h-full min-h-0 w-72 shrink-0 flex-col bg-slate-950 text-slate-200">
    <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 min-w-0">
            <x-application-logo class="h-9 w-9 text-teal-400" />
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-white">{{ config('app.name') }}</p>
                <p class="truncate text-[11px] text-slate-400">Stock workspace</p>
            </div>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
        @foreach ($groups as $group)
            @php $visible = collect($group['items'])->contains(fn ($item) => $item[4]); @endphp
            @if ($visible)
                <div>
                    <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $group['label'] }}</p>
                    <div class="space-y-0.5">
                        @foreach ($group['items'] as [$label, $href, $active, $tour, $show])
                            @if ($show)
                                <a
                                    href="{{ $href }}"
                                    wire:navigate
                                    @if ($tour !== '') data-tour="{{ $tour }}" @endif
                                    class="flex items-center rounded-xl px-3 py-2 text-sm font-medium transition {{ $active ? 'bg-white/10 text-white shadow-inner' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                                >{{ $label }}</a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </nav>

    <div class="border-t border-white/10 p-4">
        <div class="rounded-2xl bg-white/5 px-3 py-3">
            <p class="truncate text-sm font-medium text-white">{{ $user->name }}</p>
            <p class="truncate text-xs text-slate-400">{{ $user->role?->name }}</p>
            <div class="mt-3 flex flex-col gap-1">
                <button type="button" wire:click="openAssistant" class="text-left text-xs font-medium text-teal-300 hover:text-white">Ask assistant</button>
                <button type="button" wire:click="replayTour" class="text-left text-xs font-medium text-teal-300 hover:text-white">Replay walkthrough</button>
                <a href="{{ route('profile') }}" wire:navigate class="text-xs text-slate-400 hover:text-white">Profile</a>
                <button type="button" wire:click="logout" class="text-left text-xs text-slate-400 hover:text-white">Log out</button>
            </div>
        </div>
    </div>
</aside>
