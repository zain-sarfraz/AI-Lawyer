<x-ui.modal name="create-service" title="New service" subtitle="Pricing, duration, and AI booking configuration" maxWidth="md">
    <form id="create-service-form" method="POST" action="{{ route('services.store') }}">
        @csrf
        @include('admin.services._fields', [
            'service' => $formService,
            'categories' => $formCategories,
            'lawyers' => $formLawyers,
            'selectedLawyers' => [],
            'idPrefix' => 'create_service',
            'modalName' => 'create-service',
        ])
    </form>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
        <button type="submit" form="create-service-form" class="ui-btn ui-btn-primary">Save service</button>
    </x-slot:footer>
</x-ui.modal>
