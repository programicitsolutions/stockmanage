<div>
    <x-page :title="$editing ? 'Edit product' : 'Add product'" description="Opening stock posts once to the ledger. Present stock is never typed here.">
        <form wire:submit="save" class="rounded-2xl bg-white border border-slate-200 p-5 space-y-4 max-w-xl">
            <div>
                <x-input-label for="sku" value="SKU / product code" />
                <x-text-input wire:model="sku" id="sku" class="block mt-1 w-full" required />
                <x-input-error :messages="$errors->get('sku')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="name" value="Product name" />
                <x-text-input wire:model="name" id="name" class="block mt-1 w-full" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label value="Product type" />
                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex cursor-pointer gap-3 rounded-2xl border p-3 {{ $kind === 'main' ? 'border-teal-600 bg-teal-50' : 'border-slate-200' }}">
                        <input type="radio" wire:model.live="kind" value="main" class="mt-1 text-teal-700">
                        <span>
                            <span class="block text-sm font-semibold">Main product</span>
                            <span class="block text-xs text-slate-500">Finished goods you sell or issue.</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer gap-3 rounded-2xl border p-3 {{ $kind === 'inner' ? 'border-teal-600 bg-teal-50' : 'border-slate-200' }}">
                        <input type="radio" wire:model.live="kind" value="inner" class="mt-1 text-teal-700">
                        <span>
                            <span class="block text-sm font-semibold">Inner product</span>
                            <span class="block text-xs text-slate-500">Lids, liners, plugs, inserts.</span>
                        </span>
                    </label>
                </div>
            </div>
            <div>
                <x-input-label for="category_id" value="Category" />
                <select wire:model="category_id" id="category_id" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">No category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="unit" value="Unit" />
                <x-text-input wire:model="unit" id="unit" class="block mt-1 w-full" required />
            </div>
            <div>
                <x-input-label for="opening_stock" value="Opening stock" />
                <x-text-input wire:model="opening_stock" id="opening_stock" type="number" step="0.001" min="0" class="block mt-1 w-full" :disabled="$editing" />
                <p class="mt-1 text-xs text-slate-500">
                    @if ($editing)
                        Opening stock is locked. Use an adjustment to correct the ledger.
                    @else
                        If this is more than zero, an OPENING ledger row is created automatically.
                    @endif
                </p>
                <x-input-error :messages="$errors->get('opening_stock')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="minimum_stock_level" value="Minimum stock level" />
                <x-text-input wire:model="minimum_stock_level" id="minimum_stock_level" type="number" step="0.001" min="0" class="block mt-1 w-full" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="default_purchase_price" value="Default purchase price" />
                    <x-text-input wire:model="default_purchase_price" id="default_purchase_price" type="number" step="0.01" min="0" class="block mt-1 w-full" />
                    <p class="mt-1 text-xs text-slate-500">Used on stock in when you do not type a line price. Landing cost on a bill can also include freight.</p>
                </div>
                <div>
                    <x-input-label for="default_selling_price" value="Default selling price" />
                    <x-text-input wire:model="default_selling_price" id="default_selling_price" type="number" step="0.01" min="0" class="block mt-1 w-full" />
                    <p class="mt-1 text-xs text-slate-500">Used on stock out and for unit profit (sell − landing).</p>
                </div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-teal-700">
                Active
            </label>
            <div class="flex gap-2">
                <x-primary-button>{{ $editing ? 'Save product' : 'Create product' }}</x-primary-button>
                <x-ui-link :href="route('products.index')" variant="secondary" wire:navigate>Cancel</x-ui-link>
            </div>
        </form>
    </x-page>
</div>
