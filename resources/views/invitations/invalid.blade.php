<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invitation unavailable · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-50 antialiased">
<div class="mx-auto flex min-h-screen max-w-lg items-center px-4 py-10">
    <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm text-center">
        <h1 class="font-display text-2xl font-bold">Invitation unavailable</h1>
        <p class="mt-2 text-sm text-slate-500">
            This invitation is {{ $invitation->status }} and can no longer be accepted.
            Ask your firm administrator to send a new invite.
        </p>
        <a href="{{ url('/') }}" class="ui-btn ui-btn-secondary mt-5 inline-flex">Back to home</a>
    </div>
</div>
</body>
</html>
