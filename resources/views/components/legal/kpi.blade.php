@props([
    'label',
    'value',
    'hint' => null,
])

<x-ui.kpi :label="$label" :value="$value" :hint="$hint" {{ $attributes }} />
