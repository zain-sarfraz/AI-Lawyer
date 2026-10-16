<x-ui.modal name="create-lawyer" title="New lawyer" subtitle="Name, services, phone, email, and experience" maxWidth="md">
    <form id="create-lawyer-form" method="POST" action="{{ route('lawyers.store') }}">
        @csrf
        @include('admin.lawyers._fields', [
            'lawyer' => $formLawyer,
            'services' => $formServices,
            'selectedServices' => [],
            'idPrefix' => 'create_lawyer',
            'modalName' => 'create-lawyer',
        ])
    </form>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
        <button type="submit" form="create-lawyer-form" class="ui-btn ui-btn-primary">Save lawyer</button>
    </x-slot:footer>
</x-ui.modal>
