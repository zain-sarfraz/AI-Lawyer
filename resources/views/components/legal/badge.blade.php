@props([
    'tone' => 'soft',
])

@php
    $class = match ($tone) {
        'navy' => 'legal-badge legal-badge-navy',
        'brass' => 'legal-badge legal-badge-brass',
        'success' => 'legal-badge legal-badge-success',
        'danger' => 'legal-badge legal-badge-danger',
        'info' => 'legal-badge legal-badge-info',
        default => 'legal-badge',
    };
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</span>
