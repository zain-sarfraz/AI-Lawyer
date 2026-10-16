@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'mint',
    'icon' => 'chart',
])

<x-ui.kpi :label="$label" :value="$value" :hint="$hint" :tone="$tone" :icon="$icon" {{ $attributes }} />
