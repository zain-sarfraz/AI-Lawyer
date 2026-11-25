<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Jurisdiction;
use App\Models\Lawyer;
use App\Support\Phone\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $lawyer = Lawyer::withoutGlobalScopes()
            ->with(['jurisdictions', 'firm'])
            ->where('user_id', $user->id)
            ->first();

        // Only individual lawyers and firm admins may change their own country code / jurisdictions;
        // firm-managed lawyers view theirs read-only and must have their Firm Admin change them.
        $canEditPractice = $lawyer !== null && ($user->isIndividual() || $user->isFirmAdmin());

        return view('profile.edit', [
            'user' => $user,
            'lawyer' => $lawyer,
            'canEditPractice' => $canEditPractice,
            'activeJurisdictions' => Jurisdiction::activeGroupedByCountry(),
            'countryLabels' => PhoneNormalizer::countryLabels(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $lawyer = Lawyer::withoutGlobalScopes()->where('user_id', $user->id)->first();
        if ($lawyer && ($user->isIndividual() || $user->isFirmAdmin())) {
            $previousCountry = strtoupper((string) ($lawyer->country ?: ''));
            $country = strtoupper((string) ($validated['phone_country'] ?? $lawyer->country ?: 'US'));
            $phone = $lawyer->phone;
            $phoneLocal = trim((string) ($validated['phone_local'] ?? ''));

            if ($phoneLocal !== '') {
                try {
                    $normalized = PhoneNormalizer::normalize($country, $phoneLocal);
                    $phone = $normalized !== '' ? $normalized : null;
                } catch (\InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['phone_local' => $e->getMessage()]);
                }
            } elseif (array_key_exists('phone_local', $validated)) {
                $phone = null;
            }

            $lawyer->forceFill([
                'country' => $country,
                'phone' => $phone,
            ])->save();

            $countryChanged = $country !== $previousCountry;
            $jurisdictionIds = collect($validated['jurisdiction_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            // When switching countries or when no country-specific jurisdictions are checked,
            // automatically assign all active jurisdictions matching the selected country.
            $countryAllowedIds = Jurisdiction::query()
                ->active()
                ->where('country_code', $country)
                ->whereIn('id', $jurisdictionIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($countryChanged && $countryAllowedIds === []) {
                $allowedIds = Jurisdiction::query()
                    ->active()
                    ->where('country_code', $country)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            } elseif ($countryChanged) {
                $allowedIds = $countryAllowedIds;
            } else {
                $allowedIds = $jurisdictionIds !== []
                    ? Jurisdiction::query()->active()->whereIn('id', $jurisdictionIds)->pluck('id')->map(fn ($id) => (int) $id)->all()
                    : Jurisdiction::query()->active()->where('country_code', $country)->pluck('id')->map(fn ($id) => (int) $id)->all();
            }

            $lawyer->jurisdictions()->sync($allowedIds);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
