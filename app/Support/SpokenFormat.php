<?php

declare(strict_types=1);

namespace App\Support;

final class SpokenFormat
{
    private const ONES = [
        0 => 'zero',
        1 => 'one',
        2 => 'two',
        3 => 'three',
        4 => 'four',
        5 => 'five',
        6 => 'six',
        7 => 'seven',
        8 => 'eight',
        9 => 'nine',
        10 => 'ten',
        11 => 'eleven',
        12 => 'twelve',
        13 => 'thirteen',
        14 => 'fourteen',
        15 => 'fifteen',
        16 => 'sixteen',
        17 => 'seventeen',
        18 => 'eighteen',
        19 => 'nineteen',
    ];

    private const TENS = [
        20 => 'twenty',
        30 => 'thirty',
        40 => 'forty',
        50 => 'fifty',
        60 => 'sixty',
        70 => 'seventy',
        80 => 'eighty',
        90 => 'ninety',
    ];

    public static function currencyUnit(?string $currencyCode): string
    {
        $code = strtoupper(trim((string) $currencyCode));

        return match ($code) {
            'PKR', 'INR' => 'rupees',
            'USD', '' => 'dollars',
            'EUR' => 'euros',
            'GBP' => 'pounds',
            'CAD' => 'Canadian dollars',
            'AUD' => 'Australian dollars',
            'NZD' => 'New Zealand dollars',
            'SGD' => 'Singapore dollars',
            'AED' => 'dirhams',
            'SAR' => 'riyals',
            'CHF' => 'Swiss francs',
            'JPY' => 'yen',
            'CNY' => 'yuan',
            default => strtolower($code),
        };
    }

    public static function money(float|int|string|null $amount, ?string $currencyCode): string
    {
        $whole = max(0, (int) round((float) ($amount ?? 0)));

        return self::integerToWords($whole).' '.self::currencyUnit($currencyCode);
    }

    public static function integerToWords(int $number): string
    {
        if ($number < 0) {
            return 'minus '.self::integerToWords(abs($number));
        }

        if ($number < 20) {
            return self::ONES[$number];
        }

        if ($number < 100) {
            $ten = intdiv($number, 10) * 10;
            $rem = $number % 10;

            return $rem === 0 ? self::TENS[$ten] : self::TENS[$ten].' '.self::ONES[$rem];
        }

        if ($number < 1000) {
            $hundreds = intdiv($number, 100);
            $rem = $number % 100;

            return $rem === 0
                ? self::ONES[$hundreds].' hundred'
                : self::ONES[$hundreds].' hundred '.self::integerToWords($rem);
        }

        $scales = [
            1_000_000_000 => 'billion',
            1_000_000 => 'million',
            1_000 => 'thousand',
        ];

        foreach ($scales as $divisor => $label) {
            if ($number >= $divisor) {
                $head = intdiv($number, $divisor);
                $rem = $number % $divisor;

                return $rem === 0
                    ? self::integerToWords($head).' '.$label
                    : self::integerToWords($head).' '.$label.' '.self::integerToWords($rem);
            }
        }

        return (string) $number;
    }
}
