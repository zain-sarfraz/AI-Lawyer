@php
    $modalName = 'edit-lawyer-'.$lawyer->id;
    $selectedServices = $lawyer->services->pluck('id')->all();
@endphp

<x-ui.modal
    :name="$modalName"
    title="Edit lawyer"
    :subtitle="$lawyer->displayName()"
    maxWidth="md"
>
    <form id="{{ $modalName }}-form" method="POST" action="{{ route('lawyers.update', $lawyer) }}">
        @csrf
        @method('PUT')
        @include('admin.lawyers._fields', [
            'lawyer' => $lawyer,
            'services' => $formServices,
            'selectedServices' => old('_modal') === $modalName ? old('service_ids', $selectedServices) : $selectedServices,
            'idPrefix' => 'edit_lawyer_'.$lawyer->id,
            'modalName' => $modalName,
        ])
    </form>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
        <button type="submit" form="{{ $modalName }}-form" class="ui-btn ui-btn-primary">Save changes</button>
    </x-slot:footer>
</x-ui.modal>
