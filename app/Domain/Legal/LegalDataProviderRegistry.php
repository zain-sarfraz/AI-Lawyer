<?php

namespace App\Domain\Legal;

use App\Domain\Legal\Contracts\LegalDataProvider;

/**
 * Resolves the legal-data provider that can actually serve a country.
 *
 * CourtListener covers the United States today. Adding another jurisdiction
 * means registering one more provider class against its ISO code here — the
 * Case Research module itself does not change.
 */
class LegalDataProviderRegistry
{
    /**
     * @param  array<string, class-string<LegalDataProvider>>  $providers keyed by ISO-3166-1 alpha-2 code
     */
    public function __construct(private readonly array $providers = [])
    {
    }

    public function forCountry(?string $country): ?LegalDataProvider
    {
        $country = mb_strtoupper(trim((string) $country));

        if ($country === '' || ! isset($this->providers[$country])) {
            return null;
        }

        return app($this->providers[$country]);
    }

    /** The first configured provider, used when no country is requested. */
    public function default(): ?LegalDataProvider
    {
        foreach ($this->providers as $provider) {
            $instance = app($provider);
            if ($instance->isConfigured()) {
                return $instance;
            }
        }

        // Nothing is wired up yet: hand back the configured default so the UI
        // can explain the missing credentials instead of failing silently.
        $fallback = config('legal.default');

        return $fallback ? app($fallback) : null;
    }

    /**
     * @return list<string> every country code with a registered provider
     */
    public function supportedCountries(): array
    {
        return array_keys($this->providers);
    }

    public function supportsCountry(?string $country): bool
    {
        return isset($this->providers[mb_strtoupper(trim((string) $country))]);
    }
}
