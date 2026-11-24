@extends('layouts.app')

@section('title', 'Subscription & Billing')

@section('content')
<x-legal.page-header title="Subscription & Billing" subtitle="Firm-level plan for this workspace. Lawyers share this subscription." />

<div class="grid gap-4 lg:grid-cols-2">
    <x-legal.card>
        <h2 class="mb-3 font-semibold">Current plan</h2>
        <dl class="grid gap-3 text-sm">
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Firm</dt><dd class="font-medium">{{ $firm->firm_name }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Account type</dt><dd class="font-medium">{{ ucfirst((string) ($firm->account_type ?: 'firm')) }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Plan</dt><dd class="font-medium">{{ $summary['name'] ?? ucfirst((string) ($firm->plan ?: 'Custom')) }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Billing interval</dt><dd class="font-medium">{{ ucfirst((string) ($firm->billing_interval ?: '—')) }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Status</dt><dd class="font-medium">{{ ucfirst((string) ($firm->subscription_status ?: 'unknown')) }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Amount</dt><dd class="font-medium">{{ $summary ? ($summary['amount_label'].$summary['interval_label']) : '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Lawyer seats</dt><dd class="font-medium">{{ $firm->resolvedMaxLawyers() === null ? 'Unlimited' : $firm->resolvedMaxLawyers() }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Subscribed</dt><dd class="font-medium">{{ optional($firm->subscribed_at)->format('M j, Y') ?: '—' }}</dd></div>
        </dl>
    </x-legal.card>

    <x-legal.card>
        <h2 class="mb-3 font-semibold">Plan conditions</h2>
        @if ($summary)
            <ul class="mb-4 list-disc space-y-2 pl-5 text-sm text-slate-600 dark:text-slate-300">
                @foreach ($summary['features'] as $feature)
                    <li>{{ $feature }}</li>
                @endforeach
            </ul>
            <ol class="list-decimal space-y-2 pl-5 text-sm text-slate-500">
                @foreach ($summary['conditions'] as $condition)
                    <li>{{ $condition }}</li>
                @endforeach
            </ol>
        @else
            <p class="text-sm text-slate-500">No structured plan metadata is attached to this firm yet.</p>
        @endif

        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
            Stripe customer: <code>{{ $firm->stripe_customer_id ?: 'n/a' }}</code><br>
            Stripe subscription: <code>{{ $firm->stripe_subscription_id ?: 'n/a' }}</code><br>
            In-app plan changes / invoices are not implemented yet — this page reflects the firm’s stored subscription fields.
        </div>
    </x-legal.card>
</div>
@endsection
