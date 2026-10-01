@props(['tone' => 'success'])

<div {{ $attributes->merge(['class' => 'ui-alert '.($tone === 'danger' ? 'ui-alert-danger' : 'ui-alert-success')]) }}>
    {{ $slot }}
</div>
