@props([
    'label',
    'value',
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'ui-card h-full p-4']) }}>
    <div class="text-xs font-semibold uppercase tracking-wide text-(--muted)">{{ $label }}</div>
    <div class="font-display mt-1 text-3xl leading-none">{{ $value }}</div>
    @if($hint)
        <div class="mt-2 text-sm text-(--muted)">{{ $hint }}</div>
    @endif
</div>
