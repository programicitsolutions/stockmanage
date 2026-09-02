@props(['status'])

@php
    $label = \App\Support\StockStatus::label($status);
    $classes = match ($status) {
        'out' => 'bg-slate-800 text-white',
        'critical' => 'bg-red-100 text-red-800',
        'low' => 'bg-amber-100 text-amber-900',
        default => 'bg-emerald-50 text-emerald-800',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium '.$classes]) }}>
    {{ $label }}
</span>
