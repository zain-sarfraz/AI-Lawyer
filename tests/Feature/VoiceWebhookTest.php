<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\FirmSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_incoming_webhook_creates_call_and_returns_twiml(): void
    {
        config([
            'services.twilio.validate_signature' => false,
            'services.voice_gateway.public_ws_url' => 'wss://gateway.test/media',
        ]);

        FirmSetting::query()->create([
            'firm_name' => 'Test Firm',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'ai_greeting' => 'Hello from test firm',
            'slot_interval_minutes' => 15,
            'default_duration_minutes' => 30,
            'buffer_minutes' => 0,
            'booking_horizon_days' => 30,
            'cancellation_window_hours' => 24,
        ]);

        $response = $this->post('/api/webhooks/twilio/voice/incoming', [
            'CallSid' => 'CA_TEST_123',
            'From' => '+15551234567',
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
        $response->assertSee('wss://gateway.test/media', false);
        $response->assertSee('CA_TEST_123', false);

        $this->assertDatabaseHas('calls', [
            'provider_call_id' => 'CA_TEST_123',
            'caller_phone' => '+15551234567',
        ]);
        $this->assertSame(1, Call::query()->count());
        $this->assertNotNull(Call::query()->first()?->aiSession);
    }

    public function test_ai_context_requires_gateway_secret_in_non_local_mode(): void
    {
        // In testing env, empty secret is allowed by middleware.
        config(['services.voice_gateway.secret' => '']);

        FirmSetting::query()->create([
            'firm_name' => 'Test Firm',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'slot_interval_minutes' => 15,
            'default_duration_minutes' => 30,
            'buffer_minutes' => 0,
            'booking_horizon_days' => 30,
            'cancellation_window_hours' => 24,
        ]);

        $response = $this->getJson('/api/ai/context');
        $response->assertOk();
        $response->assertJsonStructure([
            'system_prompt',
            'session_context',
            'realtime_tools',
        ]);
    }

    public function test_ai_context_rejects_bad_secret(): void
    {
        config(['services.voice_gateway.secret' => 'expected-secret']);

        $response = $this->withHeader('X-Voice-Gateway-Secret', 'wrong')
            ->getJson('/api/ai/context');

        $response->assertStatus(401);
    }
}
