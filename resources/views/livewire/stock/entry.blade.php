<div>
    <x-page
        :title="$mode === 'in' ? 'Stock in' : 'Stock out'"
        :description="$mode === 'in' ? 'Search a product, enter quantity, review the new stock, then confirm. This posts a STOCK_IN ledger row.' : 'Search a product, enter quantity, review remaining stock, then confirm. This posts a STOCK_OUT ledger row and cannot take stock below zero.'"
    >
        @if (! $reviewed)
            <form wire:submit="review" class="rounded-2xl bg-white border border-slate-200 p-5 space-y-4 max-w-xl">
                <div>
                    <x-input-label for="productSearch" value="Search product" />
                    <x-text-input wire:model.live.debounce.250ms="productSearch" id="productSearch" class="block mt-1 w-full" placeholder="Name or SKU" autocomplete="off" />
                    <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                    @if ($selectedProduct)
                        <p class="mt-2 text-sm text-slate-700">
                            Selected: <span class="font-semibold">{{ $selectedProduct->sku }} — {{ $selectedProduct->name }}</span>
                            <button type="button" wire:click="$set('product_id', null)" class="ml-2 text-teal-800 text-xs">Change</button>
                        </p>
                    @elseif ($productSearch !== '')
                        <ul class="mt-2 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-slate-50 max-h-56 overflow-y-auto">
                            @forelse ($products as $product)
                                <li>
                                    <button type="button" wire:click="selectProduct({{ $product->id }})" class="w-full text-left px-3 py-2 text-sm hover:bg-white">
                                        <span class="font-medium text-slate-900">{{ $product->name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $product->sku }}</span>
                                    </button>
                                </li>
                            @empty
                                <li class="px-3 py-2 text-sm text-slate-500">No matching products.</li>
                            @endforelse
                        </ul>
                    @endif
                    @if ($present !== null)
                        <p class="mt-2 text-sm text-slate-500">Current stock: <span class="font-semibold text-slate-800">{{ $present }}</span> {{ $selectedProduct?->unit }}</p>
                    @endif
                </div>
                <div>
                    <x-input-label for="quantity" value="Quantity" />
                    <x-text-input wire:model.blur="quantity" id="quantity" type="number" step="0.001" min="0.001" class="block mt-1 w-full" required />
                    <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="unit_price" :value="$mode === 'in' ? 'Purchase price per unit' : 'Selling price per unit'" />
                    <x-text-input wire:model="unit_price" id="unit_price" type="number" step="0.01" min="0" class="block mt-1 w-full" />
                </div>
                <div>
                    <x-input-label for="reference_number" :value="$mode === 'in' ? 'Invoice / GRN / reference' : 'Invoice / reference'" />
                    <x-text-input wire:model="reference_number" id="reference_number" class="block mt-1 w-full" />
                </div>
                @if ($mode === 'in')
                    <div>
                        <x-input-label for="supplier_id" value="Supplier" />
                        <select wire:model="supplier_id" id="supplier_id" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">None</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div>
                        <x-input-label for="customer_id" value="Customer" />
                        <select wire:model="customer_id" id="customer_id" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">None</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <x-input-label for="transaction_date" value="Transaction date" />
                    <x-text-input wire:model="transaction_date" id="transaction_date" type="date" class="block mt-1 w-full" required />
                </div>
                <div>
                    <x-input-label for="notes" value="Remarks" />
                    <textarea wire:model="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                </div>
                <x-primary-button>Review</x-primary-button>
            </form>
        @else
            <div class="rounded-2xl bg-white border border-slate-200 p-5 space-y-4 max-w-xl">
                <h2 class="font-semibold text-slate-900">Confirm {{ $mode === 'in' ? 'stock in' : 'stock out' }}</h2>
                <p class="text-sm text-slate-700">{{ $selectedProduct?->sku }} — {{ $selectedProduct?->name }}</p>
                <dl class="rounded-xl bg-slate-50 p-4 space-y-2 font-mono text-sm">
                    <div class="flex justify-between">
                        <dt>Current stock</dt>
                        <dd>{{ $formatQty::quantity($presentRaw) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>{{ $mode === 'in' ? 'Stock in' : 'Stock out' }}</dt>
                        <dd>{{ $mode === 'in' ? '+' : '−' }}{{ $formatQty::quantity($quantityNormalized) }}</dd>
                    </div>
                    <div class="flex justify-between font-semibold text-slate-900 border-t border-slate-200 pt-2">
                        <dt>{{ $mode === 'in' ? 'New stock' : 'Remaining stock' }}</dt>
                        <dd>{{ $projected !== null ? $formatQty::quantity($projected) : '—' }}</dd>
                    </div>
                </dl>
                @if ($reference_number)
                    <p class="text-sm text-slate-600">Reference: {{ $reference_number }}</p>
                @endif
                <form wire:submit="save" class="flex gap-2">
                    <x-primary-button wire:loading.attr="disabled" wire:target="save" :disabled="$saving">
                        <span wire:loading.remove wire:target="save">Confirm and save</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </x-primary-button>
                    <button type="button" wire:click="editEntry" class="rounded-md border border-slate-300 px-4 py-2 text-sm">Back</button>
                </form>
            </div>
        @endif
    </x-page>
</div>
