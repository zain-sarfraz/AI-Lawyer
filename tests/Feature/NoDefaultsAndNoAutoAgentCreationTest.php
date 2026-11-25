<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Firm\FirmContext;
use App\Domain\Firm\PermissionCatalog;
use App\Domain\Integrations\FineTunerService;
use App\Domain\Lawyers\LawyerService;
use App\Domain\Platform\PlatformFirmService;
use App\Enums\UserStatus;
use App\Models\AppointmentType;
use App\Models\Lawyer;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WorkingHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NoDefaultsAndNoAutoAgentCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PermissionCatalog::sync();

        // Even if global .env values exist in config, new users must NOT inherit them
        config([
            'services.synthflow.api_key' => 'global_env_key_should_not_leak',
            'services.synthflow.workspace_id' => 'global_env_workspace_should_not_leak',
            'services.synthflow.agent_id' => 'global_env_agent_id_should_not_leak',
            'services.synthflow.agent_phone' => '+16097809610',
            'services.synthflow.agent_name' => 'Qubetech AI Lawyer',
        ]);
    }

    public function test_new_firm_and_individual_account_do_not_receive_default_data_or_env_synthflow_agent(): void
    {
        // 1. New firm created via PlatformFirmService
        $firm = app(PlatformFirmService::class)->createFirm([
            'firm_name' => 'Fresh Firm LLP',
            'email' => 'fresh@firm.test',
        ]);

        $this->assertNull($firm->receptionist_name);
        $this->assertNull($firm->ai_greeting);
        $this->assertSame(0, AppointmentType::withoutGlobalScopes()->where('firm_id', $firm->id)->count());
        $this->assertSame(0, Service::withoutGlobalScopes()->where('firm_id', $firm->id)->count());

        // 2. New lawyer created via LawyerService has 0 default WorkingHours
        $lawyer = app(FirmContext::class)->run((int) $firm->id, function () {
            return app(LawyerService::class)->create(
                ['title' => 'Partner', 'status' => 'active'],
                ['name' => 'Fresh Counsel', 'email' => 'fresh.counsel@firm.test', 'password' => 'password123']
            );
        });
        $this->assertSame(0, WorkingHour::withoutGlobalScopes()->where('lawyer_id', $lawyer->id)->count());

        // 3. New Individual Lawyer activated without selecting country gets no default country/agent/greeting
        $soloUser = User::factory()->create([
            'email' => 'fresh.solo@example.com',
            'account_type' => 'individual',
            'role' => 'individual_lawyer',
            'status' => UserStatus::PendingPayment,
            'firm_id' => null,
            'onboarding' => [],
        ]);
        $soloUser->assignRole('Lawyer');

        $sessionId = 'cs_mock_fresh_solo_123';
        Subscription::create([
            'user_id' => $soloUser->id,
            'firm_id' => null,
            'plan' => 'starter',
            'interval' => 'monthly',
            'stripe_checkout_session_id' => $sessionId,
            'status' => 'incomplete',
        ]);

        $this->get(route('checkout.success', ['mock' => 1, 'session_id' => $sessionId]))->assertOk();

        $soloUser->refresh();
        $soloLawyer = Lawyer::withoutGlobalScopes()->where('user_id', $soloUser->id)->firstOrFail();
        $this->assertNull($soloLawyer->country);

        // Visiting AI Receptionist must NOT pre-fill .env Synthflow agent or greeting or show "Create a new agent instead"
        $this->actingAs($soloUser)
            ->get(route('ai.show'))
            ->assertOk()
            ->assertDontSee('global_env_agent_id_should_not_leak')
            ->assertDontSee('global_env_workspace_should_not_leak')
            ->assertDontSee('+16097809610')
            ->assertDontSee('Create a new agent instead')
            ->assertSee('Not attached');
    }

    public function test_synthflow_never_creates_a_new_agent_even_on_errors_or_telnyx_assign(): void
    {
        Http::fake([
            '*' => Http::response(['message' => 'Payment Error'], 402),
        ]);

        $soloUser = User::factory()->create([
            'email' => 'no.auto.agent@example.com',
            'account_type' => 'individual',
            'role' => 'individual_lawyer',
            'status' => UserStatus::Active,
            'firm_id' => null,
        ]);
        $soloUser->assignRole('Lawyer');

        $this->actingAs($soloUser);

        // 1. Direct service calls must never hit POST /assistants
        $fineTuner = app(FineTunerService::class);
        $this->assertFalse($fineTuner->createInboundAgent('Test')['ok']);
        $this->assertFalse($fineTuner->ensureAgent('Test')['ok']);

        // 2. Endpoint for create-agent returns error
        $this->post(route('ai.synthflow.create-agent'), ['name' => 'Test'])
            ->assertSessionHasErrors('agent');

        // 3. Telnyx number assign without agent saves number without calling POST /assistants
        $this->post(route('ai.telnyx.assign'), ['phone_number' => '+14155550199'])
            ->assertSessionHas('success');

        Http::assertNotSent(function ($request) {
            return strtoupper($request->method()) === 'POST' && str_contains($request->url(), '/assistants');
        });
    }

    public function test_english_uses_synthflow_agent_voice_id_and_urdu_uses_urdu_voice_id(): void
    {
        $firm = app(PlatformFirmService::class)->createFirm([
            'firm_name' => 'Voice Test Firm',
            'email' => 'voice@firm.test',
        ]);
        $user = User::factory()->create([
            'firm_id' => $firm->id,
            'account_type' => 'firm',
            'role' => 'firm_admin',
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('FirmAdmin');

        $synthflowVoice = 'cgSgspJ2msm6clMCkdW9';
        $urduVoice = \App\Domain\AI\AiReceptionistService::URDU_VOICE_ID;
        $liveVoiceOnSynthflow = $synthflowVoice;
        $putPayloads = [];

        Http::fake(function ($request) use (&$liveVoiceOnSynthflow, &$putPayloads) {
            $url = $request->url();
            $method = strtoupper($request->method());

            if ($method === 'GET' && str_contains($url, '/assistants/')) {
                return Http::response([
                    'status' => 'ok',
                    'response' => [
                        'assistants' => [[
                            'model_id' => 'agent_voice_1',
                            'name' => 'Test Agent',
                            'type' => 'inbound',
                            'phone_number' => '+15550001111',
                            'agent' => ['voice_id' => $liveVoiceOnSynthflow],
                            'actions' => [],
                        ]],
                    ],
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/assistants')) {
                return Http::response([
                    'status' => 'ok',
                    'response' => [
                        'assistants' => [[
                            'model_id' => 'agent_voice_1',
                            'name' => 'Test Agent',
                            'type' => 'inbound',
                            'phone_number' => '+15550001111',
                            'agent' => ['voice_id' => $liveVoiceOnSynthflow],
                        ]],
                    ],
                ], 200);
            }

            if ($method === 'POST' && str_contains($url, '/actions/')) {
                return Http::response(['status' => 'ok'], 200);
            }

            if ($method === 'POST' && str_contains($url, '/actions')) {
                return Http::response(['status' => 'ok', 'response' => ['action_id' => 'act_1']], 200);
            }

            if ($method === 'PUT' && str_contains($url, '/assistants/agent_voice_1')) {
                $body = json_decode($request->body(), true) ?? [];
                $putPayloads[] = $body;
                if (isset($body['agent']['voice_id'])) {
                    $liveVoiceOnSynthflow = $body['agent']['voice_id'];
                }

                return Http::response(['status' => 'ok'], 200);
            }

            return Http::response(['status' => 'ok'], 200);
        });

        $this->actingAs($user);
        $fineTuner = app(FineTunerService::class);

        // 1. Connect with English default -> keeps Synthflow agent voice (not Urdu voice)
        $connect = $fineTuner->connect('key_123', 'ws_123', 'https://api.us.synthflow.ai/v2');
        $this->assertTrue($connect['ok']);
        $this->assertSame($synthflowVoice, $putPayloads[0]['agent']['voice_id'] ?? null);
        $this->assertSame('en-US', $putPayloads[0]['agent']['language'] ?? null);

        // 2. Switch to Urdu -> applies Urdu voice ID
        $firm->update(['ai_language' => 'ur', 'ai_voice' => null]);
        $this->assertTrue($fineTuner->syncAgent()['ok']);
        $this->assertSame($urduVoice, $putPayloads[1]['agent']['voice_id'] ?? null);
        $this->assertSame('ur', $putPayloads[1]['agent']['language'] ?? null);

        // 3. Switch back to English -> restores original Synthflow agent voice ID
        $firm->update(['ai_language' => 'en', 'ai_voice' => null]);
        $this->assertTrue($fineTuner->syncAgent()['ok']);
        $this->assertSame($synthflowVoice, $putPayloads[2]['agent']['voice_id'] ?? null);
        $this->assertSame('en-US', $putPayloads[2]['agent']['language'] ?? null);

        // 4. Custom ai_voice override always wins
        $firm->update(['ai_language' => 'en', 'ai_voice' => 'custom_voice_override']);
        $this->assertTrue($fineTuner->syncAgent()['ok']);
        $this->assertSame('custom_voice_override', $putPayloads[3]['agent']['voice_id'] ?? null);
    }
}
