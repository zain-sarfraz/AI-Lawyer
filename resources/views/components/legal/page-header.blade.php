@props([
    'title',
    'subtitle' => null,
])

<div class="legal-page-header">
    <div>
        <h1>{{ $title }}</h1>
        @if($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($actions))
        <div class="d-flex align-items-center gap-2 flex-wrap">{{ $actions }}</div>
    @endif
</div>
