@props([
    'title',
    'subtitle' => null,
])

<x-ui.page-header :title="$title" :subtitle="$subtitle" {{ $attributes }}>
    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset
</x-ui.page-header>
