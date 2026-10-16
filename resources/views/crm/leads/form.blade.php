@extends('layouts.app')

@section('content')
<x-legal.page-header
    title="Edit lead"
    subtitle="Capture contact details and assign follow-up ownership"
/>

<x-legal.card>
    <form method="POST" action="{{ route('leads.update', $lead) }}">
        @csrf
        @method('PUT')
        @include('crm.leads._fields', [
            'lead' => $lead,
            'services' => $services,
            'lawyers' => $lawyers,
        ])
        <div class="mt-4">
            <button class="ui-btn ui-btn-primary">Save lead</button>
            <a href="{{ route('leads.show', $lead) }}" class="ui-btn ui-btn-ghost ms-2">Cancel</a>
        </div>
    </form>
</x-legal.card>
@endsection
