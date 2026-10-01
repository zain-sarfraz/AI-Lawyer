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
                    <label class="form-label">Service</label>
                    <select name="service_id" id="service_id" class="form-select" required>
                        <option value="">Select service</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->duration_minutes }} min · {{ $service->currency }} {{ $service->price }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Lawyer</label>
                    <select name="lawyer_id" id="lawyer_id" class="form-select" required>
                        <option value="">Select lawyer</option>
                        @foreach($lawyers as $lawyer)
                            <option value="{{ $lawyer->id }}">{{ $lawyer->displayName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date</label>
                    <input type="date" id="date" class="form-control" value="{{ now()->toDateString() }}">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Start time</label>
                    <select name="start_at" id="start_at" class="form-select" required>
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
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" value="{{ old('name') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input name="phone" class="form-control" value="{{ old('phone') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <button class="btn btn-legal-primary">Book appointment</button>
        <a href="{{ route('appointments.index') }}" class="btn btn-legal-secondary ms-2">Cancel</a>
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
