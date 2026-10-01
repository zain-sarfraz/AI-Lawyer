@extends('layouts.app')

@section('content')
<x-legal.page-header title="Profile" subtitle="Account details and security" />

<div class="row g-3">
    <div class="col-lg-8">
        <x-legal.card title="Profile information" class="mb-3">
            @include('profile.partials.update-profile-information-form')
        </x-legal.card>
        <x-legal.card title="Update password" class="mb-3">
            @include('profile.partials.update-password-form')
        </x-legal.card>
        <x-legal.card title="Danger zone">
            @include('profile.partials.delete-user-form')
        </x-legal.card>
    </div>
</div>
@endsection
