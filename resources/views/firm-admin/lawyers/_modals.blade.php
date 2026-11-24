@include('firm-admin.lawyers._view-modal')

@foreach ($lawyers as $member)
    @include('firm-admin.lawyers._edit-modal', ['member' => $member])
@endforeach

@include('firm-admin.lawyers._add-modal')
