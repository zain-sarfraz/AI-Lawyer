@php
    $modalName = 'edit-client-'.$client->id;
    $selectedServices = $client->services->pluck('id')->all();
@endphp

<x-ui.modal
    :name="$modalName"
    title="Edit client"
    :subtitle="$client->name"
    maxWidth="md"
>
    <form id="{{ $modalName }}-form" method="POST" action="{{ route('clients.update', $client) }}">
        @csrf
        @method('PUT')
        @include('crm.clients._fields', [
            'client' => $client,
            'services' => $formServices,
            'selectedServices' => old('_modal') === $modalName ? old('service_ids', $selectedServices) : $selectedServices,
            'idPrefix' => 'edit_client_'.$client->id,
            'modalName' => $modalName,
        ])
    </form>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
        <button type="submit" form="{{ $modalName }}-form" class="ui-btn ui-btn-primary">Save changes</button>
    </x-slot:footer>
</x-ui.modal>
