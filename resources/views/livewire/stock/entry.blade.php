<div>
    <x-page
        :title="$mode === 'in' ? 'Stock in' : 'Stock out'"
        :description="$mode === 'in' ? 'Purchase / goods received. This adds a STOCK_IN ledger row.' : 'Sale / goods issued. This adds a STOCK_OUT ledger row and cannot take stock below zero.'"
    >
        <form wire:submit="save" class="rounded-2xl bg-white border border-slate-200 p-5 space-y-4 max-w-xl">
            <div>
                <x-input-label for="product_id" value="Product" />
                <select wire:model.live="product_id" id="product_id" class="mt-1 block w-full rounded-md border-gray-300" required>
                    <option value="">Select product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->sku }} — {{ $product->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                @if ($present !== null)
                    <p class="mt-1 text-sm text-slate-500">Present stock (calculated): <span class="font-semibold text-slate-800">{{ $present }}</span></p>
                @endif
            </div>
            <div>
                <x-input-label for="quantity" value="Quantity" />
                <x-text-input wire:model="quantity" id="quantity" type="number" step="0.001" min="0.001" class="block mt-1 w-full" required />
                <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="unit_price" :value="$mode === 'in' ? 'Purchase price per unit' : 'Selling price per unit'" />
                <x-text-input wire:model="unit_price" id="unit_price" type="number" step="0.01" min="0" class="block mt-1 w-full" />
            </div>
            <div>
                <x-input-label for="reference_number" :value="$mode === 'in' ? 'Purchase / GRN number' : 'Invoice number'" />
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
                <x-input-label for="notes" value="Notes" />
                <textarea wire:model="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
            </div>
            <x-primary-button>{{ $mode === 'in' ? 'Post stock in' : 'Post stock out' }}</x-primary-button>
        </form>
    </x-page>
</div>
