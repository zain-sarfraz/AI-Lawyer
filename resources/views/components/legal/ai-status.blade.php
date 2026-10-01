@props([
    'online' => true,
    'label' => null,
])

<span {{ $attributes->merge(['class' => 'legal-ai-pill '.($online ? '' : 'is-offline')]) }}>
    <span class="dot"></span>
    {{ $label ?? ($online ? 'AI Online' : 'AI Offline') }}
</span>
