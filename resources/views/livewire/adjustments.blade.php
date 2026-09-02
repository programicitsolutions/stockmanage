<div>
    <x-page title="Stock adjustments" description="Corrections do not edit history. They become new ADJUSTMENT_IN or ADJUSTMENT_OUT rows after a partner approves.">
        @if (auth()->user()->isAccountant())
            <form wire:submit="requestAdjustment" class="rounded-2xl bg-white border border-slate-200 p-5 space-y-4 max-w-xl">
                <h2 class="font-semibold">Request a correction</h2>
                <select wire:model="product_id" class="block w-full rounded-md border-gray-300">
                    <option value="">Product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->sku }} — {{ $product->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('product_id')" />
                <select wire:model="direction" class="block w-full rounded-md border-gray-300">
                    <option value="ADJUSTMENT_IN">Increase stock (found extra)</option>
                    <option value="ADJUSTMENT_OUT">Decrease stock (missing / damaged)</option>
                </select>
                <x-text-input wire:model="quantity" type="number" step="0.001" min="0.001" class="w-full" placeholder="Quantity" />
                <x-input-error :messages="$errors->get('quantity')" />
                <x-text-input wire:model="reason" class="w-full" placeholder="Reason" />
                <x-input-error :messages="$errors->get('reason')" />
                <textarea wire:model="notes" rows="2" class="block w-full rounded-md border-gray-300" placeholder="Notes"></textarea>
                <x-primary-button>Submit request</x-primary-button>
            </form>
        @endif

        @if ($adjustments->isEmpty())
            <p class="text-sm text-slate-500">No adjustment requests yet.</p>
        @else
            <div class="space-y-3">
                @foreach ($adjustments as $adjustment)
                    <article class="rounded-2xl bg-white border border-slate-200 p-4">
                        <div class="flex justify-between gap-3">
                            <p class="font-semibold">{{ $adjustment->product->name }}</p>
                            <span class="text-xs uppercase tracking-wide text-slate-500">{{ $adjustment->status->label() }}</span>
                        </div>
                        <p class="text-sm mt-1">{{ $adjustment->direction->label() }} · {{ $formatQty::quantity($adjustment->quantity) }} {{ $adjustment->product->unit }}</p>
                        <p class="text-sm text-slate-600 mt-1">{{ $adjustment->reason }}</p>
                        <p class="text-xs text-slate-500 mt-2">Requested by {{ $adjustment->requestedBy->name }}</p>
                        @if (auth()->user()->isPartner() && $adjustment->status->value === 'pending')
                            <div class="mt-3 flex gap-2">
                                <button wire:click="approve({{ $adjustment->id }})" class="rounded-lg bg-teal-700 text-white text-sm px-3 py-1.5">Approve & post</button>
                                <button wire:click="reject({{ $adjustment->id }})" class="rounded-lg border border-slate-300 text-sm px-3 py-1.5">Reject</button>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
            <div>{{ $adjustments->links() }}</div>
        @endif
    </x-page>
</div>
