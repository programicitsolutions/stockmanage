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
}; ?>

@php
    $links = [
        ['Dashboard', route('dashboard'), request()->routeIs('dashboard')],
        ['Live stock', route('stock.live'), request()->routeIs('stock.live')],
        ['Movement', route('stock.movement'), request()->routeIs('stock.movement')],
        ['Products', route('products.index'), request()->routeIs('products.*')],
        ['Adjustments', route('adjustments.index'), request()->routeIs('adjustments.*')],
        ['Audit', route('audit.index'), request()->routeIs('audit.*')],
    ];
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 gap-4">
            <div class="flex min-w-0 items-center gap-6">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 text-teal-800 shrink-0">
                    <x-application-logo class="block h-8 w-8" />
                    <span class="font-semibold text-slate-900 hidden xs:inline sm:inline">{{ config('app.name') }}</span>
                </a>
                <div class="hidden lg:flex items-center gap-5 overflow-x-auto">
                    @foreach ($links as [$label, $href, $active])
                        <x-nav-link :href="$href" :active="$active" wire:navigate>{{ $label }}</x-nav-link>
                    @endforeach
                    @if (auth()->user()->isAdmin())
                        <x-nav-link :href="route('import.excel')" :active="request()->routeIs('import.*')" wire:navigate>Import Excel</x-nav-link>
                    @endif
                    @if (auth()->user()->isAccountant())
                        <x-nav-link :href="route('stock.in')" :active="request()->routeIs('stock.in')" wire:navigate>Stock in</x-nav-link>
                        <x-nav-link :href="route('stock.out')" :active="request()->routeIs('stock.out')" wire:navigate>Stock out</x-nav-link>
                    @endif
                    <x-dropdown align="left" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700">
                                Catalog
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('categories.index')" wire:navigate>Categories</x-dropdown-link>
                            <x-dropdown-link :href="route('suppliers.index')" wire:navigate>Suppliers</x-dropdown-link>
                            <x-dropdown-link :href="route('customers.index')" wire:navigate>Customers</x-dropdown-link>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center gap-3">
                <span class="text-xs font-medium text-slate-500 bg-slate-100 rounded-full px-2.5 py-1">
                    {{ auth()->user()->role?->name }}
                </span>
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-slate-600 hover:text-slate-800">
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>Profile</x-dropdown-link>
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>Log out</x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="flex items-center lg:hidden">
                <button @click="open = ! open" class="p-2 rounded-md text-slate-500 hover:bg-slate-100">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden border-t border-slate-100">
        <div class="pt-2 pb-3 space-y-1">
            @foreach ($links as [$label, $href, $active])
                <x-responsive-nav-link :href="$href" :active="$active" wire:navigate>{{ $label }}</x-responsive-nav-link>
            @endforeach
            @if (auth()->user()->isAdmin())
                <x-responsive-nav-link :href="route('import.excel')" :active="request()->routeIs('import.*')" wire:navigate>Import Excel</x-responsive-nav-link>
            @endif
            @if (auth()->user()->isAccountant())
                <x-responsive-nav-link :href="route('stock.in')" :active="request()->routeIs('stock.in')" wire:navigate>Stock in</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('stock.out')" :active="request()->routeIs('stock.out')" wire:navigate>Stock out</x-responsive-nav-link>
            @endif
            <x-responsive-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.*')" wire:navigate>Categories</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('suppliers.index')" :active="request()->routeIs('suppliers.*')" wire:navigate>Suppliers</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('customers.index')" :active="request()->routeIs('customers.*')" wire:navigate>Customers</x-responsive-nav-link>
        </div>
        <div class="pt-4 pb-1 border-t border-slate-200">
            <div class="px-4">
                <div class="font-medium text-base text-slate-800">{{ auth()->user()->name }}</div>
                <div class="font-medium text-sm text-slate-500">{{ auth()->user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>Profile</x-responsive-nav-link>
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>Log out</x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
