@props([
    'title' => null,
])

<div {{ $attributes->merge(['class' => 'legal-card']) }}>
    <div class="legal-card-body">
        @if($title || isset($header))
            <div class="legal-card-title-row">
                @if(isset($header))
                    {{ $header }}
                @else
                    <h2 class="legal-card-title">{{ $title }}</h2>
                @endif
                @isset($action)
                    <div>{{ $action }}</div>
                @endisset
            </div>
        @endif
        {{ $slot }}
    </div>
</div>
