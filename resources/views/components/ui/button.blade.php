@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'submit',
])

@php
    $classes = match ($variant) {
        'ghost' => 'ui-btn ui-btn-ghost',
        'brass' => 'ui-btn ui-btn-brass',
        'danger' => 'ui-btn ui-btn-danger',
        default => 'ui-btn ui-btn-primary',
    };
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
