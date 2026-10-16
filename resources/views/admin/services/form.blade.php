@extends('layouts.app')

@section('content')
<x-legal.page-header
    title="Edit service"
    subtitle="Pricing, duration, and AI booking configuration"
/>

<x-legal.card>
    <form method="POST" action="{{ route('services.update', $service) }}">
        @csrf
        @method('PUT')
        @include('admin.services._fields', [
            'service' => $service,
            'categories' => $categories,
            'lawyers' => $lawyers,
            'selectedLawyers' => $selectedLawyers,
            'idPrefix' => 'edit_service',
        ])
        <div class="mt-4">
            <button class="ui-btn ui-btn-primary">Save service</button>
            <a href="{{ route('services.index') }}" class="ui-btn ui-btn-ghost ms-2">Cancel</a>
        </div>
    </form>
</x-legal.card>
@endsection
