<x-ui.modal name="edit-appointment" title="Edit appointment" subtitle="Update booking details used when the appointment was created" maxWidth="md">
    <form
        id="edit-appointment-form"
        method="POST"
        x-data="{ action: '' }"
        x-on:fill-edit-appointment.window="action = $event.detail?.action || ''"
        x-bind:action="action"
    >
        @csrf
        @method('PUT')
        <div id="edit-appointment-fields">
            {{-- Fields injected when opening edit for a specific appointment --}}
        </div>
    </form>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="$dispatch('close-modal', 'edit-appointment')">Cancel</button>
        <button type="submit" form="edit-appointment-form" class="ui-btn ui-btn-primary">Save appointment</button>
    </x-slot:footer>
</x-ui.modal>

{{-- Prefill templates per appointment (kept off-DOM until edit opens) --}}
@foreach($appointments as $appointment)
    <template id="edit-appt-template-{{ $appointment->id }}">
        @include('appointments._fields', [
            'services' => $formServices,
            'lawyers' => $formLawyers,
            'idPrefix' => 'edit_appt_'.$appointment->id,
            'modalName' => 'edit-appointment',
            'appointment' => $appointment,
            'showStatus' => true,
        ])
    </template>
@endforeach
