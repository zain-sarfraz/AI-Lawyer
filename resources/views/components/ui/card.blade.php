@props([
    'title' => null,
])

<div {{ $attributes->merge(['class' => 'ui-card']) }}>
    <div class="p-5">
        @if($title || isset($header))
            <div class="mb-3 flex items-start justify-between gap-3">
                @if(isset($header))
                    {{ $header }}
                @else
                    <h2 class="font-display text-xl leading-tight">{{ $title }}</h2>
                @endif
                @isset($action)
                    <div class="shrink-0">{{ $action }}</div>
                @endisset
            </div>
        @endif
        {{ $slot }}
    </div>
</div>
