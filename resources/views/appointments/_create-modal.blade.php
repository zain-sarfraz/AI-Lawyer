<x-ui.modal name="create-appointment" title="Book appointment" subtitle="Staff booking against verified availability" maxWidth="md">
    <form id="create-appointment-form" method="POST" action="{{ route('appointments.store') }}">
        @csrf
        @include('appointments._fields', [
            'services' => $formServices,
            'lawyers' => $formLawyers,
            'idPrefix' => 'create_appt',
            'modalName' => 'create-appointment',
        ])
    </form>

    <x-slot:footer>
        <button type="button" class="ui-btn ui-btn-ghost" @click="show = false">Cancel</button>
        <button type="submit" form="create-appointment-form" class="ui-btn ui-btn-primary">Book appointment</button>
    </x-slot:footer>
</x-ui.modal>

@push('scripts')
<script>
(() => {
  const availabilityUrl = @json(route('appointments.availability'));
  const oldStart = @json(old('start_at'));

  async function loadSlots(prefix) {
    const service = document.getElementById(`${prefix}_service_id`);
    const lawyer = document.getElementById(`${prefix}_lawyer_id`);
    const date = document.getElementById(`${prefix}_date`);
    const select = document.getElementById(`${prefix}_start_at`);
    if (!service || !date || !select) return;

    const preferred = select.dataset.currentStart || oldStart || '';
    select.innerHTML = '<option value="">Loading…</option>';
    if (!service.value || !date.value) {
      select.innerHTML = '<option value="">Load slots…</option>';
      return;
    }

    const params = new URLSearchParams({
      service_id: service.value,
      date: date.value,
      lawyer_id: lawyer?.value || '',
    });
    const res = await fetch(`${availabilityUrl}?${params}`);
    const json = await res.json();
    select.innerHTML = '';

    let hasPreferred = false;
    (json.slots || []).forEach((slot) => {
      const opt = document.createElement('option');
      opt.value = slot.start_at;
      opt.textContent = new Date(slot.start_at).toLocaleString();
      if (preferred && preferred === slot.start_at) {
        opt.selected = true;
        hasPreferred = true;
      }
      select.appendChild(opt);
    });

    // Keep the current booked slot selectable even if it no longer appears as free.
    if (preferred && !hasPreferred) {
      const opt = document.createElement('option');
      opt.value = preferred;
      opt.textContent = new Date(preferred).toLocaleString() + ' (current)';
      opt.selected = true;
      select.appendChild(opt);
    }

    if (!select.options.length) {
      select.innerHTML = '<option value="">No slots available</option>';
    }
  }

  function bindSlotForm(root) {
    const prefix = root.getAttribute('data-appt-slot-form');
    if (!prefix || root.dataset.bound === '1') return;
    root.dataset.bound = '1';
    root.querySelectorAll('[data-slot-source]').forEach((el) => {
      el.addEventListener('change', () => loadSlots(prefix));
    });
    loadSlots(prefix);
  }

  function bindAllSlotForms() {
    document.querySelectorAll('[data-appt-slot-form]').forEach(bindSlotForm);
  }

  window.loadAppointmentSlots = loadSlots;
  window.bindAppointmentSlotForms = bindAllSlotForms;

  document.addEventListener('DOMContentLoaded', bindAllSlotForms);
  window.addEventListener('bind-appt-slots', bindAllSlotForms);

  window.addEventListener('open-modal', (event) => {
    if (event.detail === 'create-appointment') {
      setTimeout(() => loadSlots('create_appt'), 50);
    }
    if (event.detail === 'edit-appointment') {
      setTimeout(bindAllSlotForms, 50);
    }
  });
})();
</script>
@endpush
