@props([
    'tone' => 'soft',
])

<x-ui.badge :tone="$tone" {{ $attributes }}>{{ $slot }}</x-ui.badge>
