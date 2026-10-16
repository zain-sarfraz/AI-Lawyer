@extends('layouts.app')

@section('content')
<script>
    window.location.replace(@json(route('appointments.index')) + '?open=create-appointment');
</script>
@endsection
