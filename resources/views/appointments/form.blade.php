@extends('layouts.app')

@section('content')
<x-legal.page-header title="Book appointment" subtitle="Staff booking against verified availability" />

<x-legal.card>
    <form method="POST" action="{{ route('appointments.store') }}">
        @csrf
        <div class="legal-form-section">
            <h3>Schedule</h3>
            <p>Slots load from conflict-safe availability.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="ui-label">Service</label>
                    <select name="service_id" id="service_id" class="ui-field" required>
                        <option value="">Select service</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->duration_minutes }} min · {{ $service->currency }} {{ $service->price }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="ui-label">Lawyer</label>
                    <select name="lawyer_id" id="lawyer_id" class="ui-field" required>
                        <option value="">Select lawyer</option>
                        @foreach($lawyers as $lawyer)
                            <option value="{{ $lawyer->id }}">{{ $lawyer->displayName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="ui-label">Date</label>
                    <input type="date" id="date" class="ui-field" value="{{ now()->toDateString() }}">
                </div>
                <div class="col-md-8">
                    <label class="ui-label">Start time</label>
                    <select name="start_at" id="start_at" class="ui-field" required>
                        <option value="">Load slots…</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="legal-form-section">
            <h3>Caller</h3>
            <p>Creates or matches a lead for this booking.</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="ui-label">Name</label>
                    <input name="name" class="ui-field" value="{{ old('name') }}">
                </div>
                <div class="col-md-4">
                    <label class="ui-label">Phone</label>
                    <input name="phone" class="ui-field" value="{{ old('phone') }}">
                </div>
                <div class="col-md-4">
                    <label class="ui-label">Email</label>
                    <input type="email" name="email" class="ui-field" value="{{ old('email') }}">
                </div>
                <div class="col-12">
                    <label class="ui-label">Notes</label>
                    <textarea name="notes" class="ui-field" rows="3">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <button class="ui-btn ui-btn-primary">Book appointment</button>
        <a href="{{ route('appointments.index') }}" class="ui-btn ui-btn-ghost ms-2">Cancel</a>
    </form>
</x-legal.card>
@endsection

@push('scripts')
<script>
async function loadSlots() {
  const serviceId = document.getElementById('service_id').value;
  const lawyerId = document.getElementById('lawyer_id').value;
  const date = document.getElementById('date').value;
  const select = document.getElementById('start_at');
  select.innerHTML = '<option value="">Loading…</option>';
  if (!serviceId || !date) return;
  const params = new URLSearchParams({ service_id: serviceId, date, lawyer_id: lawyerId || '' });
  const res = await fetch(`{{ route('appointments.availability') }}?` + params);
  const json = await res.json();
  select.innerHTML = '';
  (json.slots || []).forEach(slot => {
    const opt = document.createElement('option');
    opt.value = slot.start_at;
    opt.textContent = new Date(slot.start_at).toLocaleString();
    select.appendChild(opt);
  });
  if (!json.slots?.length) select.innerHTML = '<option value="">No slots available</option>';
}
['service_id','lawyer_id','date'].forEach(id => document.getElementById(id).addEventListener('change', loadSlots));
loadSlots();
</script>
@endpush
