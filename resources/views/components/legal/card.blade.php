@props([
    'title' => null,
])

<x-ui.card :title="$title" {{ $attributes }}>
    @isset($header)
        <x-slot:header>{{ $header }}</x-slot:header>
    @endisset
    @isset($action)
        <x-slot:action>{{ $action }}</x-slot:action>
    @endisset
    {{ $slot }}
</x-ui.card>
