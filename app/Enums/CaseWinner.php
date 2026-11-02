<?php

namespace App\Enums;

enum CaseWinner: string
{
    case PartyOne = 'party_one';
    case PartyTwo = 'party_two';
    case Split = 'split';
    case None = 'none';
    case NotStated = 'not_stated';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function tryNormalize(?string $value): ?self
    {
        if ($value === null || trim($value) === '') {
            return self::NotStated;
        }

        $raw = strtolower(trim(str_replace([' ', '-'], '_', $value)));

        return self::tryFrom($raw) ?? self::NotStated;
    }
}
