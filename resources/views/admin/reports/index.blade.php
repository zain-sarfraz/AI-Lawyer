@extends('layouts.app')

@section('content')
<x-legal.page-header title="Reports" subtitle="Operational snapshot for firm leadership" />

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><x-legal.kpi label="Leads today" :value="$stats['leads']" /></div>
    <div class="col-6 col-lg-3"><x-legal.kpi label="Calls today" :value="$stats['calls']" /></div>
    <div class="col-6 col-lg-3"><x-legal.kpi label="Appointments today" :value="$stats['appointments']" /></div>
    <div class="col-6 col-lg-3"><x-legal.kpi label="Conversions today" :value="$stats['conversions']" /></div>
</div>

<x-legal.card>
    <div class="legal-empty">
        <h3>Insights workspace</h3>
        <p class="mb-0">Detailed attribution and trend reports will expand here. Today’s operational KPIs are available above.</p>
    </div>
</x-legal.card>
@endsection
