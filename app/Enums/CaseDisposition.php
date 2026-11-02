<?php

namespace App\Enums;

enum CaseDisposition: string
{
    case Allowed = 'allowed';
    case Dismissed = 'dismissed';
    case PartlyAllowed = 'partly_allowed';
    case Reversed = 'reversed';
    case Upheld = 'upheld';
    case Remanded = 'remanded';
    case Acquitted = 'acquitted';
    case Convicted = 'convicted';
    case Withdrawn = 'withdrawn';
    case NotStated = 'not_stated';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Allowed => 'Allowed',
            self::Dismissed => 'Dismissed',
            self::PartlyAllowed => 'Partly allowed',
            self::Reversed => 'Reversed',
            self::Upheld => 'Upheld',
            self::Remanded => 'Remanded',
            self::Acquitted => 'Acquitted',
            self::Convicted => 'Convicted',
            self::Withdrawn => 'Withdrawn',
            self::NotStated => 'Not stated',
        };
    }

    public static function tryNormalize(?string $value): ?self
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $raw = strtolower(trim(str_replace([' ', '-'], '_', $value)));

        return self::tryFrom($raw) ?? match (true) {
            str_contains($raw, 'grant') || $raw === 'allowed' => self::Allowed,
            str_contains($raw, 'partly') => self::PartlyAllowed,
            str_contains($raw, 'dismiss') => self::Dismissed,
            str_contains($raw, 'revers') => self::Reversed,
            str_contains($raw, 'affirm') || str_contains($raw, 'uphold') => self::Upheld,
            str_contains($raw, 'remand') => self::Remanded,
            str_contains($raw, 'acquit') => self::Acquitted,
            str_contains($raw, 'convict') => self::Convicted,
            str_contains($raw, 'withdraw') => self::Withdrawn,
            default => self::NotStated,
        };
    }
}
