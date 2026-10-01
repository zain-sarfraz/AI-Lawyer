@props([
    'tone' => 'soft',
])

@php
    $classes = match ($tone) {
        'navy' => 'bg-navy-900 text-stone-100',
        'brass' => 'bg-gold-400/25 text-[#6b5420]',
        'success' => 'bg-emerald-600/10 text-emerald-700',
        'danger' => 'bg-red-600/10 text-red-700',
        'info' => 'bg-sky-500/10 text-sky-800',
        default => 'bg-stone-200/80 text-stone-700',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold $classes"]) }}>{{ $slot }}</span>
