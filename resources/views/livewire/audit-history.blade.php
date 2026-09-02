<div>
    <x-page title="Audit history" description="Who entered each ledger row, when, product, quantity, price, reference, and notes.">
        @if ($logs->isEmpty())
            <p class="text-sm text-slate-500">No audit entries yet. Posting stock in or out will appear here.</p>
        @else
            <div class="space-y-3">
                @foreach ($logs as $log)
                    <article class="rounded-2xl bg-white border border-slate-200 p-4 text-sm">
                        <p class="font-medium text-slate-900">{{ $log->user?->name ?? 'System' }} · {{ $log->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</p>
                        <p class="text-slate-500 text-xs mt-1">{{ $log->action }}</p>
                        @if (is_array($log->properties))
                            <dl class="mt-3 grid grid-cols-2 gap-2 text-slate-700">
                                @foreach ($log->properties as $key => $value)
                                    @if (! is_array($value))
                                        <div>
                                            <dt class="text-xs uppercase tracking-wide text-slate-400">{{ str_replace('_', ' ', $key) }}</dt>
                                            <dd>{{ $value === null || $value === '' ? '—' : $value }}</dd>
                                        </div>
                                    @endif
                                @endforeach
                            </dl>
                        @endif
                    </article>
                @endforeach
            </div>
            <div>{{ $logs->links() }}</div>
        @endif
    </x-page>
</div>
