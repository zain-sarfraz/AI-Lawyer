<?php

namespace Tests\Feature;

use App\Domain\Firm\FirmContext;
use App\Models\Call;
use App\Models\FirmSetting;
use App\Models\Integration;
use App\Models\Service;
use App\Support\CallTranscript;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FineTunerWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_webhook_creates_call(): void
    {
        $firm = FirmSetting::query()->create([
            'firm_name' => 'FineTuner Firm',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'receptionist_name' => 'Alex',
            'slot_interval_minutes' => 15,
            'default_duration_minutes' => 30,
            'buffer_minutes' => 0,
            'booking_horizon_days' => 30,
            'cancellation_window_hours' => 24,
        ]);

        Integration::query()->create([
            'firm_id' => $firm->id,
            'provider' => 'finetuner',
            'status' => 'active',
            'settings_json' => ['model_id' => 'model_abc'],
        ]);

        $response = $this->postJson('/api/webhooks/finetuner/inbound', [
            'call_id' => 'FT_CALL_1',
            'from' => '+15559876543',
            'model_id' => 'model_abc',
        ]);

        $response->assertOk()
            ->assertJsonPath('call_inbound.metadata.firm_id', $firm->id)
            ->assertJsonPath('call_inbound.prompt_variables.receptionist_name', 'Alex');

        $this->assertDatabaseHas('calls', [
            'provider_call_id' => 'FT_CALL_1',
            'caller_phone' => '+15559876543',
            'status' => 'in_progress',
        ]);
    }

    public function test_post_call_webhook_stores_transcript_for_admin(): void
    {
        $firm = FirmSetting::query()->create([
            'firm_name' => 'FineTuner Firm',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'slot_interval_minutes' => 15,
            'default_duration_minutes' => 30,
            'buffer_minutes' => 0,
            'booking_horizon_days' => 30,
            'cancellation_window_hours' => 24,
        ]);

        Integration::query()->create([
            'firm_id' => $firm->id,
            'provider' => 'finetuner',
            'status' => 'active',
            'settings_json' => ['model_id' => 'model_abc'],
        ]);

        app(FirmContext::class)->run((int) $firm->id, function () {
            Call::query()->create([
                'provider_call_id' => 'FT_CALL_2',
                'direction' => 'inbound',
                'caller_phone' => '+15551112222',
                'started_at' => now(),
                'status' => 'in_progress',
            ]);
        });

        $response = $this->postJson('/api/webhooks/finetuner/post-call', [
            'call_id' => 'FT_CALL_2',
            'model_id' => 'model_abc',
            'from' => '+15551112222',
            'transcript' => "Bot: Hello, thanks for calling.\nHuman: I need a consult.\nAssistant: Our family law consult is USD 150.",
            'duration' => 95,
            'status' => 'completed',
        ]);

        $response->assertOk()->assertJsonPath('ok', true);

        $call = Call::query()->withoutGlobalScopes()->where('provider_call_id', 'FT_CALL_2')->first();
        $this->assertNotNull($call);
        $this->assertSame('completed', $call->status);
        $this->assertSame(95, $call->duration_seconds);
        $this->assertStringContainsString('Receptionist: Hello, thanks for calling.', (string) $call->transcript);
        $this->assertStringContainsString('Caller: I need a consult.', (string) $call->transcript);

        $lines = CallTranscript::toLines($call->transcript);
        $this->assertSame('ai', $lines[0]['role']);
        $this->assertSame('caller', $lines[1]['role']);
    }

    public function test_finetuner_tool_get_services_returns_fees_and_lawyers(): void
    {
        config(['services.voice_gateway.secret' => '']);

        $firm = FirmSetting::query()->create([
            'firm_name' => 'FineTuner Firm',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'slot_interval_minutes' => 15,
            'default_duration_minutes' => 30,
            'buffer_minutes' => 0,
            'booking_horizon_days' => 30,
            'cancellation_window_hours' => 24,
        ]);

        app(FirmContext::class)->run((int) $firm->id, function () {
            Service::query()->create([
                'name' => 'Family Consult',
                'price' => 150,
                'currency' => 'USD',
                'duration_minutes' => 30,
                'ai_enabled' => true,
                'booking_enabled' => true,
                'status' => 'active',
            ]);
        });

        $response = $this->postJson('/api/ai/finetuner/tools/get_services', [
            'firm_id' => $firm->id,
            'provider_call_id' => 'FT_TOOL_1',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Family Consult']);
        $services = $response->json('data');
        $this->assertIsArray($services);
        $this->assertNotEmpty($services);
        $this->assertArrayHasKey('fee_label', $services[0]);
        $this->assertArrayHasKey('speak', $services[0]);
        $this->assertStringContainsString('150', $services[0]['speak']);
    }
}
