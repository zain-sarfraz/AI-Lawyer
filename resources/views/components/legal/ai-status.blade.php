@props([
    'online' => true,
    'label' => null,
])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-2.5 py-1 text-xs font-semibold text-stone-100']) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $online ? 'bg-emerald-400' : 'bg-stone-400' }}"></span>
    {{ $label ?? ($online ? 'AI Online' : 'AI Offline') }}
</span>
