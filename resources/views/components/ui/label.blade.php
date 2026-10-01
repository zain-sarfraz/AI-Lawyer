@props(['value' => null])

<label {{ $attributes->merge(['class' => 'ui-label']) }}>
    {{ $value ?? $slot }}
</label>
