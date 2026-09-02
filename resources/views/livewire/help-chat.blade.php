<div class="fixed bottom-4 right-4 z-[60] print:hidden flex flex-col items-end gap-3">
    @if ($open)
        <div class="flex h-[28rem] w-[22rem] max-w-[calc(100vw-2rem)] flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between bg-slate-950 px-4 py-3 text-white">
                <div>
                    <p class="text-sm font-semibold">Stock assistant</p>
                    <p class="text-[11px] text-slate-400">Live help for this ledger</p>
                </div>
                <button type="button" wire:click="toggle" class="text-slate-300 hover:text-white text-sm">Close</button>
            </div>
            <div class="flex-1 space-y-3 overflow-y-auto bg-slate-50 px-3 py-3 text-sm">
                @foreach ($messages as $row)
                    <div class="{{ $row['role'] === 'user' ? 'ml-8' : 'mr-6' }}">
                        <div class="whitespace-pre-wrap rounded-2xl px-3 py-2 {{ $row['role'] === 'user' ? 'bg-teal-700 text-white' : 'bg-white border border-slate-200 text-slate-700' }}">{{ $row['text'] }}</div>
                        @foreach ($row['links'] ?? [] as $link)
                            <a href="{{ $link['url'] }}" wire:navigate class="mt-1 inline-block text-xs font-medium text-teal-800">{{ $link['label'] }} →</a>
                        @endforeach
                    </div>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-1 border-t border-slate-100 px-3 pt-2">
                <button type="button" wire:click="openChat('how do I stock in?')" class="rounded-full bg-slate-100 px-2 py-1 text-[11px] text-slate-700">Stock in</button>
                <button type="button" wire:click="openChat('how do I stock out?')" class="rounded-full bg-slate-100 px-2 py-1 text-[11px] text-slate-700">Stock out</button>
                <button type="button" wire:click="openChat('low stock')" class="rounded-full bg-slate-100 px-2 py-1 text-[11px] text-slate-700">Low stock</button>
            </div>
            <form wire:submit="send" class="flex gap-2 p-3">
                <input wire:model="message" class="w-full rounded-xl border-slate-300 text-sm" placeholder="Ask or paste a SKU" />
                <button class="rounded-xl bg-teal-700 px-3 text-sm font-semibold text-white">Send</button>
            </form>
        </div>
    @endif
    <button type="button" wire:click="toggle" class="inline-flex items-center gap-2 rounded-full bg-teal-700 px-4 py-3 text-sm font-semibold text-white shadow-lg hover:bg-teal-800">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h5M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.942L3 20l1.06-3.185C3.388 15.71 3 13.912 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        Ask assistant
    </button>
</div>
