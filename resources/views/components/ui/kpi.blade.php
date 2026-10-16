@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'mint',
    'icon' => 'chart',
])

@php
    $toneClass = match ($tone) {
        'blue' => 'is-blue',
        'violet' => 'is-violet',
        'amber' => 'is-amber',
        'rose' => 'is-rose',
        default => 'is-mint',
    };
@endphp

<div {{ $attributes->merge(['class' => "kpi-card $toneClass h-full"]) }}>
    <div class="kpi-icon">
        <x-ui.icon :name="$icon" class="h-4 w-4" />
    </div>
    <div class="kpi-label">{{ $label }}</div>
    <div class="kpi-value">{{ $value }}</div>
    @if($hint)
        <div class="kpi-hint">{{ $hint }}</div>
    @endif
</div>
