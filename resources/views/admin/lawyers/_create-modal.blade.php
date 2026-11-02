<x-ui.modal name="create-lawyer" title="New lawyer" subtitle="Phone dial code is separate from practice jurisdictions" maxWidth="lg">
    <form id="create-lawyer-form" method="POST" action="{{ route('lawyers.store') }}">
        @csrf
        @include('admin.lawyers._fields', [
            'lawyer' => $formLawyer,
            'services' => $formServices,
            'selectedServices' => [],
            'activeJurisdictions' => $activeJurisdictions,
            'idPrefix' => 'create_lawyer',
            'modalName' => 'create-lawyer',
        ])
    </form>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
        <button type="submit" form="create-lawyer-form" class="ui-btn ui-btn-primary">Save lawyer</button>
    </x-slot:footer>
</x-ui.modal>
