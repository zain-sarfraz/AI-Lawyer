<?php

declare(strict_types=1);

namespace App\Domain\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelnyxService
{
    public function configured(): bool
    {
        return filled(config('services.telnyx.api_key'));
    }

    /**
     * @return array{ok: bool, numbers?: list<array{number: string, locality?: string, region?: string, monthly?: mixed}>, error?: string}
     */
    public function searchAvailable(?string $areaCode = null, string $country = 'US', int $limit = 8): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'error' => 'Add TELNYX_API_KEY to .env first.'];
        }

        $query = [
            'filter[country_code]' => strtoupper($country),
            'filter[phone_number_type]' => 'local',
            'filter[limit]' => max(1, min(20, $limit)),
            'filter[features]' => 'voice',
        ];

        if (filled($areaCode)) {
            $query['filter[national_destination_code]'] = preg_replace('/\D+/', '', $areaCode);
        }

        try {
            $response = Http::withToken((string) config('services.telnyx.api_key'))
                ->acceptJson()
                ->timeout(20)
                ->get('https://api.telnyx.com/v2/available_phone_numbers', $query);
        } catch (\Throwable $exception) {
            Log::warning('Telnyx search failed', ['message' => $exception->getMessage()]);

            return ['ok' => false, 'error' => 'Could not reach Telnyx.'];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'error' => $this->errorMessage($response->json(), $response->status())];
        }

        $rows = data_get($response->json(), 'data', []);
        $numbers = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $number = (string) ($row['phone_number'] ?? '');
            if ($number === '') {
                continue;
            }
            $numbers[] = [
                'number' => $number,
                'locality' => (string) data_get($row, 'region_information.0.region_name', data_get($row, 'locality', '')),
                'region' => (string) data_get($row, 'region_information.1.region_name', ''),
                'monthly' => data_get($row, 'cost_information.monthly_cost'),
            ];
        }

        return ['ok' => true, 'numbers' => $numbers];
    }

    /**
     * @return array{ok: bool, number?: string, error?: string}
     */
    public function orderNumber(string $phoneNumber): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'error' => 'Add TELNYX_API_KEY to .env first.'];
        }

        $phoneNumber = $this->normalizeE164($phoneNumber);
        if ($phoneNumber === null) {
            return ['ok' => false, 'error' => 'Phone number must be E.164 (e.g. +12202650290).'];
        }

        try {
            $response = Http::withToken((string) config('services.telnyx.api_key'))
                ->acceptJson()
                ->timeout(30)
                ->post('https://api.telnyx.com/v2/number_orders', [
                    'phone_numbers' => [
                        ['phone_number' => $phoneNumber],
                    ],
                ]);
        } catch (\Throwable $exception) {
            Log::warning('Telnyx order failed', ['message' => $exception->getMessage()]);

            return ['ok' => false, 'error' => 'Could not reach Telnyx to order the number.'];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'error' => $this->errorMessage($response->json(), $response->status())];
        }

        return ['ok' => true, 'number' => $phoneNumber];
    }

    public function normalizeE164(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (! is_string($digits) || strlen($digits) < 10) {
            return null;
        }

        if ($phone[0] === '+') {
            return '+'.$digits;
        }

        if (strlen($digits) === 10) {
            return '+1'.$digits;
        }

        return '+'.$digits;
    }

    private function errorMessage(mixed $json, int $status): string
    {
        $message = is_array($json)
            ? (data_get($json, 'errors.0.detail') ?? data_get($json, 'errors.0.title') ?? $json['message'] ?? null)
            : null;

        if (is_string($message) && $message !== '') {
            return $message;
        }

        return 'Telnyx returned an error ('.$status.').';
    }
}
