<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accept invitation · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 antialiased">
<div class="mx-auto flex min-h-screen max-w-lg items-center px-4 py-10">
    <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-600">Firm invitation</p>
        <h1 class="mt-2 font-display text-2xl font-bold">Join {{ $invitation->firm?->firm_name }}</h1>
        <p class="mt-2 text-sm text-slate-500">
            Hi {{ $invitation->name }}, set a password to activate your <strong>{{ $invitation->role }}</strong> account
            ({{ $invitation->email }}).
        </p>

        @if ($errors->any())
            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('invitations.accept.store', $invitation->token) }}" class="mt-5 space-y-4">
            @csrf
            <div>
                <label class="ui-label">Password</label>
                <input class="ui-field" type="password" name="password" required minlength="8">
            </div>
            <div>
                <label class="ui-label">Confirm password</label>
                <input class="ui-field" type="password" name="password_confirmation" required minlength="8">
            </div>
            <button type="submit" class="ui-btn ui-btn-primary w-full">Activate account</button>
        </form>
    </div>
</div>
</body>
</html>
