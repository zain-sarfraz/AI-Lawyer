<x-ui.modal name="create-lead" title="New lead" subtitle="Capture contact details and assign follow-up ownership" maxWidth="md">
    <form id="create-lead-form" method="POST" action="{{ route('leads.store') }}">
        @csrf
        @include('crm.leads._fields', [
            'lead' => $formLead,
            'services' => $formServices,
            'lawyers' => $formLawyers,
            'modalName' => 'create-lead',
        ])
    </form>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
        <button type="submit" form="create-lead-form" class="ui-btn ui-btn-primary">Save lead</button>
    </x-slot:footer>
</x-ui.modal>
