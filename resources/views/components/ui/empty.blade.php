@props(['title' => null])

<div {{ $attributes->merge(['class' => 'ui-empty']) }}>
    @if($title)
        <h3>{{ $title }}</h3>
    @endif
    <div>{{ $slot }}</div>
</div>
