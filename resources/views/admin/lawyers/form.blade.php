@extends('layouts.app')

@section('content')
<x-legal.page-header
    title="Edit lawyer"
    subtitle="AI Lawyer counsel — name, services, phone, email, and experience"
/>

<x-legal.card>
    <form method="POST" action="{{ route('lawyers.update', $lawyer) }}">
        @csrf
        @method('PUT')
        @include('admin.lawyers._fields', [
            'lawyer' => $lawyer,
            'services' => $services,
            'selectedServices' => $selectedServices,
            'idPrefix' => 'edit_lawyer',
        ])
        <div class="mt-4">
            <button class="ui-btn ui-btn-primary">Save lawyer</button>
            <a href="{{ route('lawyers.index') }}" class="ui-btn ui-btn-ghost ms-2">Cancel</a>
        </div>
    </form>
</x-legal.card>
@endsection
