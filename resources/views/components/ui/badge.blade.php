@props([
    'tone' => 'soft',
])

@php
    $classes = match ($tone) {
        'navy' => 'bg-navy-900 text-stone-100',
        'brass', 'teal' => 'bg-teal-500/15 text-teal-700 dark:text-teal-300',
        'success' => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',
        'danger' => 'bg-rose-500/15 text-rose-700 dark:text-rose-300',
        'info' => 'bg-sky-500/15 text-sky-800 dark:text-sky-300',
        'warning', 'pending' => 'bg-amber-500/15 text-amber-800 dark:text-amber-300',
        'violet' => 'bg-violet-500/15 text-violet-700 dark:text-violet-300',
        default => 'bg-slate-200/80 text-slate-700 dark:bg-white/10 dark:text-slate-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold capitalize $classes"]) }}>{{ $slot }}</span>
