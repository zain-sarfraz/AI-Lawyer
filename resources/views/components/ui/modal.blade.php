@props([
    'name',
    'title',
    'subtitle' => null,
    'maxWidth' => 'md',
    'show' => false,
])

@php
    $maxWidthClass = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
    ][$maxWidth] ?? 'max-w-md';

    $shouldShow = $show
        || session('open_modal') === $name
        || request('open') === $name
        || (isset($errors) && $errors->any() && old('_modal') === $name);
@endphp

<div
    x-data="{ show: @js($shouldShow) }"
    x-init="$watch('show', value => document.body.classList.toggle('overflow-y-hidden', value))"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') show = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') show = false"
    x-on:keydown.escape.window="if (show) show = false"
    x-show="show"
    x-cloak
    class="ui-modal-root fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-label="{{ $title }}"
>
    <div
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-navy-950/60 backdrop-blur-[2px]"
        @click="show = false"
    ></div>

    <div
        x-show="show"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-3 scale-95"
        class="ui-modal-panel relative flex w-full {{ $maxWidthClass }} max-h-[min(92vh,46rem)] flex-col overflow-hidden rounded-2xl border border-(--line) bg-(--surface) shadow-2xl"
        @click.stop
    >
        <div class="flex shrink-0 items-start justify-between gap-3 border-b border-(--line) px-5 py-4">
            <div class="min-w-0">
                <h2 class="font-display text-xl leading-tight text-(--ink)">{{ $title }}</h2>
                @if($subtitle)
                    <p class="mt-1 text-sm text-(--muted)">{{ $subtitle }}</p>
                @endif
            </div>
            <button
                type="button"
                class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-(--line) text-(--muted) hover:bg-(--canvas) hover:text-(--ink)"
                @click="show = false"
                aria-label="Close"
            >
                <span class="text-lg leading-none">&times;</span>
            </button>
        </div>

        <div class="ui-modal-body min-h-0 flex-1 overflow-y-auto px-5 py-4">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-(--line) px-5 py-4">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
