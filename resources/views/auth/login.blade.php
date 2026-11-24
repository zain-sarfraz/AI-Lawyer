<x-guest-layout>
    <div x-data="{
        fill(email, password) {
            const emailEl = document.getElementById('email');
            const passwordEl = document.getElementById('password');
            if (emailEl) emailEl.value = email;
            if (passwordEl) passwordEl.value = password;
            emailEl?.focus();
        }
    }">
        <div style="margin-bottom: 24px;">
            <h2 style="font-family: 'Fraunces', Georgia, serif; font-size: 28px; font-weight: 700; color: #0F172A; line-height: 1.15; margin: 0;">Welcome back</h2>
            <p style="color: #64748B; font-size: 14px; margin-top: 6px;">Sign in to the platform portal.</p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" style="display: block; font-size: 13px; font-weight: 600; color: #1E293B; margin-bottom: 6px;">Email Address</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="admin@admin.com"
                    style="display: block; width: 100%; border: 1px solid #CBD5E1; background-color: #FFFFFF; color: #0F172A; border-radius: 12px; padding: 10px 14px; font-size: 14px; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
                    onfocus="this.style.borderColor='#D4AF37'; this.style.boxShadow='0 0 0 3px rgba(212,175,55,0.2)';"
                    onblur="this.style.borderColor='#CBD5E1'; this.style.boxShadow='none';"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-xs text-red-600" />
            </div>

            <div>
                <label for="password" style="display: block; font-size: 13px; font-weight: 600; color: #1E293B; margin-bottom: 6px;">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    style="display: block; width: 100%; border: 1px solid #CBD5E1; background-color: #FFFFFF; color: #0F172A; border-radius: 12px; padding: 10px 14px; font-size: 14px; outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
                    onfocus="this.style.borderColor='#D4AF37'; this.style.boxShadow='0 0 0 3px rgba(212,175,55,0.2)';"
                    onblur="this.style.borderColor='#CBD5E1'; this.style.boxShadow='none';"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-xs text-red-600" />
            </div>

            <div class="flex items-center justify-between text-sm" style="margin-top: 14px; margin-bottom: 20px;">
                <label class="inline-flex items-center gap-2 cursor-pointer" style="color: #475569; font-size: 13px;">
                    <input type="checkbox" name="remember" style="accent-color: #D4AF37; width: 16px; height: 16px; border-radius: 4px;">
                    <span>Remember me</span>
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" style="color: #9A773A; font-weight: 600; font-size: 13px; text-decoration: none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Forgot password?</a>
                @endif
            </div>

            <button
                type="submit"
                style="width: 100%; padding: 12px 20px; border-radius: 12px; background: linear-gradient(135deg, #F5DE9B 0%, #E3BC62 50%, #CD9A35 100%); color: #17130A; font-weight: 700; font-size: 14.5px; border: 0; cursor: pointer; box-shadow: 0 4px 14px rgba(212,175,55,0.3); transition: transform 0.2s, box-shadow 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;"
                onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 18px rgba(212,175,55,0.4)';"
                onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 14px rgba(212,175,55,0.3)';"
            >
                <span>Sign in</span>
                <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </button>
        </form>

        <!-- Quick Superadmin Autofill Button -->
        <button
            type="button"
            @click="fill('admin@admin.com', '11223344')"
            style="margin-top: 24px; width: 100%; border-radius: 14px; border: 1px solid #E2E8F0; background-color: #F8FAFC; padding: 14px 16px; text-align: left; cursor: pointer; transition: all 0.2s ease;"
            onmouseover="this.style.borderColor='#D4AF37'; this.style.backgroundColor='#FFFDF8';"
            onmouseout="this.style.borderColor='#E2E8F0'; this.style.backgroundColor='#F8FAFC';"
        >
            <div style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 8px; border-radius: 999px; background: #FFF8E8; border: 1px solid #F5D998; color: #9B6E16; font-size: 10.5px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;">
                Superadmin
            </div>
            <div style="margin-top: 8px; font-size: 13.5px; color: #0F172A; display: flex; align-items: center;">
                <span style="font-weight: 700;">admin@admin.com</span>
                <span style="margin: 0 8px; color: #94A3B8;">·</span>
                <span style="font-weight: 600; letter-spacing: 0.05em; font-family: monospace;">11223344</span>
            </div>
            <div style="margin-top: 4px; font-size: 12px; color: #64748B;">Click to autofill credentials &amp; login</div>
        </button>

        @php
            $sampleLawyer = \App\Models\Lawyer::withoutGlobalScopes()->with(['user' => fn($q) => $q->withoutGlobalScopes(), 'firm'])->latest('id')->first();
        @endphp
        @if($sampleLawyer && $sampleLawyer->user)
        <!-- Quick Lawyer Autofill Button -->
        <button
            type="button"
            @click="fill('{{ $sampleLawyer->user->email }}', '11223344')"
            style="margin-top: 10px; width: 100%; border-radius: 14px; border: 1px solid #E2E8F0; background-color: #F8FAFC; padding: 14px 16px; text-align: left; cursor: pointer; transition: all 0.2s ease;"
            onmouseover="this.style.borderColor='#38BDF8'; this.style.backgroundColor='#F0F9FF';"
            onmouseout="this.style.borderColor='#E2E8F0'; this.style.backgroundColor='#F8FAFC';"
        >
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 8px; border-radius: 999px; background: #E0F2FE; border: 1px solid #BAE6FD; color: #0369A1; font-size: 10.5px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;">
                    Counsel / Lawyer ({{ $sampleLawyer->firm?->firm_name ?? 'Firm' }})
                </div>
            </div>
            <div style="margin-top: 8px; font-size: 13.5px; color: #0F172A; display: flex; align-items: center;">
                <span style="font-weight: 700;">{{ $sampleLawyer->user->email }}</span>
                <span style="margin: 0 8px; color: #94A3B8;">·</span>
                <span style="font-weight: 600; letter-spacing: 0.05em; font-family: monospace;">11223344</span>
            </div>
            <div style="margin-top: 4px; font-size: 12px; color: #64748B;">Click to autofill {{ $sampleLawyer->displayName() }}'s credentials</div>
        </button>
        @endif
    </div>
</x-guest-layout>
