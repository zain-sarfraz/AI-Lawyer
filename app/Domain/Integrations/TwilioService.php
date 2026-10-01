<?php

namespace App\Domain\Integrations;

use App\Models\Integration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class TwilioService
{
    public function client(): ?Client
    {
        $sid = config('services.twilio.account_sid');
        $token = config('services.twilio.auth_token');

        if (! $sid || ! $token) {
            $integration = Integration::query()->where('provider', 'twilio')->where('status', 'active')->first();
            $creds = $integration?->encrypted_credentials ?? [];
            $sid = $creds['account_sid'] ?? null;
            $token = $creds['auth_token'] ?? $creds['api_key'] ?? null;
        }

        if (! $sid || ! $token) {
            return null;
        }

        return new Client($sid, $token);
    }

    public function fromNumber(): ?string
    {
        $number = config('services.twilio.from');
        if ($number) {
            return $number;
        }

        $integration = Integration::query()->where('provider', 'twilio')->where('status', 'active')->first();

        return $integration?->settings_json['from']
            ?? $integration?->encrypted_credentials['phone_number']
            ?? null;
    }

    /**
     * @return array{success: bool, provider_id?: string, error?: string}
     */
    public function sendSms(string $to, string $body): array
    {
        $client = $this->client();
        $from = $this->fromNumber();

        if (! $client || ! $from) {
            Log::warning('Twilio SMS skipped: credentials or from-number missing', ['to' => $to]);

            return ['success' => false, 'error' => 'Twilio is not configured'];
        }

        try {
            $message = $client->messages->create($to, [
                'from' => $from,
                'body' => $body,
            ]);

            return ['success' => true, 'provider_id' => $message->sid];
        } catch (\Throwable $e) {
            Log::error('Twilio SMS failed', ['error' => $e->getMessage(), 'to' => $to]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function transferCall(string $callSid, string $transferNumber): bool
    {
        $client = $this->client();
        if (! $client) {
            return false;
        }

        try {
            $twiml = '<Response><Say>Please hold while we connect you to a team member.</Say><Dial>'.htmlspecialchars($transferNumber, ENT_XML1).'</Dial></Response>';
            $client->calls($callSid)->update([
                'twiml' => $twiml,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Twilio transfer failed', ['error' => $e->getMessage(), 'call' => $callSid]);

            return false;
        }
    }
}
