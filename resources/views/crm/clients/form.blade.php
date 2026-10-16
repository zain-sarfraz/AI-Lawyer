@extends('layouts.app')

@section('content')
<x-legal.page-header
    title="Edit client"
    subtitle="AI Lawyer client record for retained matters"
/>

<x-legal.card>
    <form method="POST" action="{{ route('clients.update', $client) }}">
        @csrf
        @method('PUT')
        @include('crm.clients._fields', [
            'client' => $client,
            'services' => $services,
            'selectedServices' => $selectedServices,
            'idPrefix' => 'edit_client',
        ])
        <div class="mt-4">
            <button class="ui-btn ui-btn-primary">Save client</button>
            <a href="{{ route('clients.show', $client) }}" class="ui-btn ui-btn-ghost ms-2">Cancel</a>
        </div>
    </form>
</x-legal.card>
@endsection
