@php
    $modalName = 'edit-service-'.$service->id;
    $selectedLawyers = $service->lawyers->pluck('id')->all();
@endphp

<x-ui.modal
    :name="$modalName"
    title="Edit service"
    :subtitle="$service->name"
    maxWidth="md"
>
    <form id="{{ $modalName }}-form" method="POST" action="{{ route('services.update', $service) }}">
        @csrf
        @method('PUT')
        @include('admin.services._fields', [
            'service' => $service,
            'categories' => $formCategories,
            'lawyers' => $formLawyers,
            'selectedLawyers' => old('_modal') === $modalName ? old('lawyer_ids', $selectedLawyers) : $selectedLawyers,
            'idPrefix' => 'edit_service_'.$service->id,
            'modalName' => $modalName,
        ])
    </form>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
        <button type="submit" form="{{ $modalName }}-form" class="ui-btn ui-btn-primary">Save changes</button>
    </x-slot:footer>
</x-ui.modal>
