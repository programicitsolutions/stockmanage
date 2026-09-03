<x-page title="Stock assistant" description="Ask how to record stock, look up a SKU, or check low stock. Answers use this company’s ledger.">
    <div class="saas-card flex min-h-[32rem] flex-col overflow-hidden">
        <div class="flex items-center justify-between bg-slate-950 px-4 py-3 text-white">
            <div>
                <p class="text-sm font-semibold">Stock assistant</p>
                <p class="text-[11px] text-slate-400">{{ $aiEnabled ? 'GPT + live ledger tools' : 'Ledger rules (add OPENAI_API_KEY for GPT)' }}</p>
            </div>
        </div>

        <div class="flex-1 space-y-3 overflow-y-auto bg-slate-50 px-3 py-3 text-sm min-h-[18rem]">
            @forelse ($messages as $index => $row)
                <div wire:key="chat-{{ $index }}" class="{{ $row['role'] === 'user' ? 'ml-8' : 'mr-6' }}">
                    <div class="whitespace-pre-wrap rounded-2xl px-3 py-2 {{ $row['role'] === 'user' ? 'bg-teal-700 text-white' : 'bg-white border border-slate-200 text-slate-700' }}">{{ $row['text'] }}</div>
                    @foreach ($row['links'] ?? [] as $linkIndex => $link)
                        <a wire:key="chat-{{ $index }}-link-{{ $linkIndex }}" href="{{ $link['url'] }}" wire:navigate class="mt-1 inline-block text-xs font-medium text-teal-800">{{ $link['label'] }} →</a>
                    @endforeach
                </div>
            @empty
                <p class="text-sm text-slate-500">No messages yet. Try “stock in” or a SKU.</p>
            @endforelse
        </div>

        <div class="flex flex-wrap gap-1 border-t border-slate-100 px-3 pt-2 bg-white">
            <button type="button" wire:click="ask('stock in')" class="rounded-full bg-slate-100 px-2 py-1 text-[11px] text-slate-700">Stock in</button>
            <button type="button" wire:click="ask('landing cost')" class="rounded-full bg-slate-100 px-2 py-1 text-[11px] text-slate-700">Landing cost</button>
            <button type="button" wire:click="ask('low stock')" class="rounded-full bg-slate-100 px-2 py-1 text-[11px] text-slate-700">Low stock</button>
            <button type="button" wire:click="ask('profit')" class="rounded-full bg-slate-100 px-2 py-1 text-[11px] text-slate-700">Profit</button>
        </div>

        <form wire:submit="send" class="flex gap-2 p-3 bg-white">
            <input
                type="text"
                wire:model.live="message"
                class="w-full rounded-xl border-slate-300 text-sm"
                placeholder="Ask or paste a SKU"
                autocomplete="off"
            />
            <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-teal-700 px-3 text-sm font-semibold text-white disabled:opacity-60">
                <span wire:loading.remove wire:target="send,ask">Send</span>
                <span wire:loading wire:target="send,ask">…</span>
            </button>
        </form>
    </div>
</x-page>
