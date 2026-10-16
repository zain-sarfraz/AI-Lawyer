<x-ui.modal name="create-client" title="New client" subtitle="AI Lawyer client record for retained matters" maxWidth="md">
    <form id="create-client-form" method="POST" action="{{ route('clients.store') }}">
        @csrf
        @include('crm.clients._fields', [
            'client' => $formClient,
            'services' => $formServices,
            'selectedServices' => [],
            'idPrefix' => 'create_client',
            'modalName' => 'create-client',
        ])
    </form>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
        <button type="submit" form="create-client-form" class="ui-btn ui-btn-primary">Save client</button>
    </x-slot:footer>
</x-ui.modal>
