@props(['href', 'variant' => 'primary'])

@php
$classes = match ($variant) {
    'primary' => 'inline-flex items-center justify-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800',
    'secondary' => 'inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50',
    default => 'inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-teal-800 hover:bg-teal-50',
};
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
