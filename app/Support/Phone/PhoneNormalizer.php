<?php

namespace App\Support\Phone;

use InvalidArgumentException;

final class PhoneNormalizer
{
    /** @var array<string, array{dial: string, min: int, max: int, label: string}> */
    private const RULES = [
        'PK' => ['dial' => '+92', 'min' => 10, 'max' => 10, 'label' => 'Pakistan'],
        'GB' => ['dial' => '+44', 'min' => 10, 'max' => 10, 'label' => 'United Kingdom'],
        'US' => ['dial' => '+1', 'min' => 10, 'max' => 10, 'label' => 'United States'],
        'CA' => ['dial' => '+1', 'min' => 10, 'max' => 10, 'label' => 'Canada'],
        'AU' => ['dial' => '+61', 'min' => 9, 'max' => 9, 'label' => 'Australia'],
    ];

    /** @return list<string> */
    public static function supportedCountries(): array
    {
        return array_keys(self::RULES);
    }

    /** @return array<string, string> */
    public static function countryLabels(): array
    {
        $labels = [];
        foreach (self::RULES as $code => $rule) {
            $labels[$code] = $rule['label'];
        }

        return $labels;
    }

    public static function countryLabel(?string $countryCode): string
    {
        $code = strtoupper(trim((string) $countryCode));

        return self::RULES[$code]['label'] ?? $code;
    }

    public static function dialCode(string $countryCode): string
    {
        $code = strtoupper(trim($countryCode));

        return self::RULES[$code]['dial'] ?? '';
    }

    /**
     * Normalize a local or partially international number for a selected phone country.
     * Always strips leading 0s from the national significant number before
     * applying the dial code (fixes 0324… + GB → +440324… bugs).
     */
    public static function normalize(string $countryCode, ?string $raw): string
    {
        $code = strtoupper(trim($countryCode));
        if (! isset(self::RULES[$code])) {
            throw new InvalidArgumentException("Unsupported phone country: {$code}");
        }

        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        $dialDigits = ltrim(self::RULES[$code]['dial'], '+');

        // If user already typed the country dial digits, drop them.
        if (str_starts_with($digits, $dialDigits)) {
            $digits = substr($digits, strlen($dialDigits));
        }

        // National format often starts with 0 — strip it for E.164.
        $digits = ltrim($digits, '0');

        $len = strlen($digits);
        $min = self::RULES[$code]['min'];
        $max = self::RULES[$code]['max'];
        if ($len < $min || $len > $max) {
            throw new InvalidArgumentException(
                "Phone number for {$code} must have {$min}–{$max} digits after the country code (got {$len})."
            );
        }

        return self::RULES[$code]['dial'].$digits;
    }

    public static function split(?string $e164): array
    {
        $value = trim((string) $e164);
        // Prefer longer dial codes first so +1 does not steal +44 / +92 prefixes incorrectly —
        // iterate sorted by dial length desc.
        $rules = self::RULES;
        uasort($rules, static fn ($a, $b) => strlen($b['dial']) <=> strlen($a['dial']));

        foreach ($rules as $code => $rule) {
            $dial = $rule['dial'];
            if (str_starts_with($value, $dial)) {
                return [
                    'country' => $code,
                    'local' => substr($value, strlen($dial)),
                ];
            }
        }

        return ['country' => '', 'local' => ltrim($value, '0+')];
    }
}
