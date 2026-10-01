@props([
    'label',
    'value',
    'hint' => null,
])

<div class="legal-kpi">
    <div class="label">{{ $label }}</div>
    <div class="value">{{ $value }}</div>
    @if($hint)
        <div class="hint">{{ $hint }}</div>
    @endif
</div>
