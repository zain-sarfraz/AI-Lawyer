@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'mb-5 flex flex-wrap items-end justify-between gap-3']) }}>
    <div>
        <h1 class="font-display text-3xl leading-tight">{{ $title }}</h1>
        @if($subtitle)
            <p class="mt-1 text-sm text-(--muted)">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
