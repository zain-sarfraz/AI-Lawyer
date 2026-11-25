<?php

namespace Tests\Unit;

use App\Domain\AI\AiReceptionistService;
use App\Domain\AI\AiToolRegistry;
use App\Domain\Firm\FirmContext;
use App\Models\Call;
use App\Models\Firm;
use App\Models\Lawyer;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkingHour;
use App\Support\SpokenFormat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantVoicePromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_accounts_share_identical_static_template_and_never_leak_data_or_leftovers(): void
    {
        /** @var AiReceptionistService $receptionist */
        $receptionist = $this->app->make(AiReceptionistService::class);

        // Account 1: PKR firm with service duration 45 min
        $firmOne = Firm::factory()->create([
            'firm_name' => 'Lahore Civic Counsel',
            'legal_name' => 'Lahore Civic Counsel',
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
            'phone' => '+92421112223',
            'email' => 'desk@lahorecivic.test',
            'cancellation_window_hours' => 24,
            'after_hours_behavior' => 'callback',
            'ai_system_prompt' => null,
        ]);

        app(FirmContext::class)->run((int) $firmOne->id, function () {
            $u1 = User::factory()->create(['firm_id' => app(FirmContext::class)->id(), 'name' => 'Tariq Mahmood']);
            $l1 = Lawyer::query()->create(['user_id' => $u1->id, 'title' => 'Senior Counsel', 'status' => 'active']);
            $s1 = Service::query()->create([
                'name' => 'Civil Litigation Advisory',
                'price' => 5000,
                'currency' => 'PKR',
                'duration_minutes' => 45,
                'ai_enabled' => true,
                'booking_enabled' => true,
                'status' => 'active',
            ]);
            $l1->services()->sync([$s1->id]);
            WorkingHour::query()->create([
                'lawyer_id' => $l1->id,
                'day_of_week' => 1,
                'start_time' => '10:00:00',
                'end_time' => '14:00:00',
                'status' => 'active',
            ]);
        });

        // Account 2: USD firm with 60 min service + 0-duration service
        $firmTwo = Firm::factory()->create([
            'firm_name' => 'Harbor Bridge Law LLP',
            'legal_name' => 'Harbor Bridge Law LLP',
            'currency' => 'USD',
            'timezone' => 'America/New_York',
            'phone' => 'n/a',
            'email' => null,
            'transfer_number' => null,
            'cancellation_window_hours' => 48,
            'after_hours_behavior' => 'voicemail',
            'ai_system_prompt' => null,
        ]);

        app(FirmContext::class)->run((int) $firmTwo->id, function () {
            $u2 = User::factory()->create(['firm_id' => app(FirmContext::class)->id(), 'name' => 'Claire Bennett']);
            $l2 = Lawyer::query()->create(['user_id' => $u2->id, 'title' => 'Partner', 'status' => 'active']);
            $s2a = Service::query()->create([
                'name' => 'Contract Review',
                'price' => 125,
                'currency' => 'USD',
                'duration_minutes' => 60,
                'ai_enabled' => true,
                'booking_enabled' => true,
                'status' => 'active',
            ]);
            $s2b = Service::query()->create([
                'name' => 'Quick Intake',
                'price' => 45,
                'currency' => 'USD',
                'duration_minutes' => 0,
                'ai_enabled' => true,
                'booking_enabled' => true,
                'status' => 'active',
            ]);
            $l2->services()->sync([$s2a->id, $s2b->id]);
            WorkingHour::query()->create([
                'lawyer_id' => $l2->id,
                'day_of_week' => 2,
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
                'status' => 'active',
            ]);
        });

        // Account 3: Empty account (no name, no services, no lawyers, unavailable contact fields)
        $firmEmpty = Firm::factory()->create([
            'firm_name' => '',
            'legal_name' => null,
            'currency' => 'EUR',
            'timezone' => 'UTC',
            'phone' => '',
            'email' => 'n/a',
            'website' => '',
            'address' => null,
            'transfer_number' => '',
            'cancellation_window_hours' => 12,
            'after_hours_behavior' => 'callback',
            'ai_system_prompt' => null,
        ]);

        $staticOne = $receptionist->defaultSystemPrompt($firmOne);
        $staticTwo = $receptionist->defaultSystemPrompt($firmTwo);
        $staticThree = $receptionist->defaultSystemPrompt($firmEmpty);

        // Normalize only the 6 tenant placeholders to prove the static section is identical across all accounts.
        $normalize = function (string $text, array $replacements): string {
            return strtr($text, $replacements);
        };

        $normOne = $normalize($staticOne, [
            'Lahore Civic Counsel' => '{FIRM}',
            'PKR' => '{CODE}',
            'rupees' => '{UNIT}',
            'Asia/Karachi' => '{TZ}',
            '24 hours' => '{CXL} hours',
            'follow callback' => 'follow {AH}',
        ]);
        $normTwo = $normalize($staticTwo, [
            'Harbor Bridge Law LLP' => '{FIRM}',
            'USD' => '{CODE}',
            'dollars' => '{UNIT}',
            'America/New_York' => '{TZ}',
            '48 hours' => '{CXL} hours',
            'follow voicemail' => 'follow {AH}',
        ]);
        $normThree = $normalize($staticThree, [
            'our law office' => '{FIRM}',
            'EUR' => '{CODE}',
            'euros' => '{UNIT}',
            'UTC' => '{TZ}',
            '12 hours' => '{CXL} hours',
            'follow callback' => 'follow {AH}',
        ]);

        $this->assertSame($normOne, $normTwo);
        $this->assertSame($normOne, $normThree);

        // Build full prompts WITHOUT FirmContext set to verify query tenant isolation!
        app(FirmContext::class)->forget();

        $promptOne = $receptionist->systemPrompt(true, $firmOne);
        $promptTwo = $receptionist->systemPrompt(true, $firmTwo);
        $promptThree = $receptionist->systemPrompt(true, $firmEmpty);

        // Account 1 checks
        $this->assertStringContainsString('Lahore Civic Counsel', $promptOne);
        $this->assertStringContainsString('Civil Litigation Advisory', $promptOne);
        $this->assertStringContainsString('five thousand rupees', $promptOne);
        $this->assertStringContainsString('duration: 45 minutes', $promptOne);
        $this->assertStringContainsString('Tariq Mahmood', $promptOne);
        $this->assertStringNotContainsString('Harbor Bridge Law LLP', $promptOne);
        $this->assertStringNotContainsString('Contract Review', $promptOne);
        $this->assertStringNotContainsString('Claire Bennett', $promptOne);
        $this->assertStringNotContainsString('dollars', $promptOne);

        // Account 2 checks
        $this->assertStringContainsString('Harbor Bridge Law LLP', $promptTwo);
        $this->assertStringContainsString('Contract Review', $promptTwo);
        $this->assertStringContainsString('one hundred twenty five dollars', $promptTwo);
        $this->assertStringContainsString('duration: 60 minutes', $promptTwo);
        $this->assertStringContainsString('Quick Intake', $promptTwo);
        $this->assertStringContainsString('forty five dollars', $promptTwo);
        $this->assertStringContainsString('Claire Bennett', $promptTwo);
        $this->assertStringContainsString('Office phone: not available', $promptTwo);
        $this->assertStringContainsString('Transfer number: not available', $promptTwo);
        $this->assertMatchesRegularExpression('/- Quick Intake \(id \d+\) \| spoken fee: forty five dollars \| lawyers: Claire Bennett/', $promptTwo);
        $this->assertStringNotContainsString('Lahore Civic Counsel', $promptTwo);
        $this->assertStringNotContainsString('Civil Litigation Advisory', $promptTwo);
        $this->assertStringNotContainsString('Tariq Mahmood', $promptTwo);

        // Account 3 (empty) fallback checks
        $this->assertStringContainsString('Hello, thank you for calling our law office.', $promptThree);
        $this->assertStringContainsString('Office phone: not available', $promptThree);
        $this->assertStringContainsString('Office email: not available', $promptThree);
        $this->assertStringContainsString('Booking status: unavailable', $promptThree);
        $this->assertStringContainsString('- No services configured.', $promptThree);
        $this->assertStringContainsString('- No lawyers configured.', $promptThree);
        $this->assertStringNotContainsString('Civil Litigation Advisory', $promptThree);
        $this->assertStringNotContainsString('Contract Review', $promptThree);

        // No hardcoded account leftovers or Roman Urdu anywhere across all 3 prompts
        $forbidden = [
            'Zain',
            'Family Case',
            'Murder Case',
            'Murder',
            'USD 45',
            'USD 50',
            '15 minutes',
            'Qubetech',
            'Individual Lawyer',
            'chaheen',
            'Assalamualaikum',
            'Maazrat',
            'Allah hafiz',
            'Bohot shukriya',
            'Roman Urdu',
            'Pizza Go',
            'place_order',
        ];

        foreach ([$promptOne, $promptTwo, $promptThree] as $idx => $prompt) {
            foreach ($forbidden as $term) {
                $this->assertStringNotContainsStringIgnoringCase($term, $prompt, "Account {$idx} leaked forbidden term: {$term}");
            }
            $this->assertStringNotContainsString('...', $prompt);
            $this->assertStringNotContainsString('…', $prompt);
        }

        // Extra check: Account 2 must NOT contain "30 min" or "Asia/Karachi" anywhere
        $this->assertStringNotContainsString('Asia/Karachi', $promptTwo);
        $this->assertStringNotContainsString('30 min', $promptTwo);
    }

    public function test_spoken_money_and_customer_phone_same_and_tenant_webhook_isolation(): void
    {
        $this->assertSame('forty five dollars', SpokenFormat::money(45.00, 'USD'));
        $this->assertSame('five thousand rupees', SpokenFormat::money(5000, 'PKR'));
        $this->assertSame('one hundred twenty euros', SpokenFormat::money(120, 'EUR'));
        $this->assertSame('eighty pounds', SpokenFormat::money(80, 'GBP'));

        $firmA = Firm::factory()->create(['firm_name' => 'Alpha Firm', 'currency' => 'PKR', 'timezone' => 'Asia/Karachi']);
        $firmB = Firm::factory()->create(['firm_name' => 'Beta Firm', 'currency' => 'USD', 'timezone' => 'America/New_York']);

        [$lawyerA, $svcA] = app(FirmContext::class)->run((int) $firmA->id, function () use ($firmA) {
            $u = User::factory()->create(['firm_id' => $firmA->id, 'name' => 'Counsel Alpha']);
            $l = Lawyer::query()->create(['user_id' => $u->id, 'title' => 'Counsel', 'status' => 'active']);
            $s = Service::query()->create([
                'name' => 'Alpha Consultation',
                'price' => 3000,
                'currency' => 'PKR',
                'duration_minutes' => 40,
                'ai_enabled' => true,
                'booking_enabled' => true,
                'status' => 'active',
            ]);
            $l->services()->sync([$s->id]);
            WorkingHour::query()->create([
                'lawyer_id' => $l->id,
                'day_of_week' => now('Asia/Karachi')->next(\Carbon\Carbon::MONDAY)->dayOfWeek,
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'status' => 'active',
            ]);

            return [$l, $s];
        });

        app(FirmContext::class)->run((int) $firmB->id, function () use ($firmB) {
            $u = User::factory()->create(['firm_id' => $firmB->id, 'name' => 'Counsel Beta']);
            $l = Lawyer::query()->create(['user_id' => $u->id, 'title' => 'Counsel', 'status' => 'active']);
            $s = Service::query()->create([
                'name' => 'Beta Consultation',
                'price' => 99,
                'currency' => 'USD',
                'duration_minutes' => 25,
                'ai_enabled' => true,
                'booking_enabled' => true,
                'status' => 'active',
            ]);
            $l->services()->sync([$s->id]);
        });

        // 1. Cross-tenant isolation: unresolved webhook with 2 firms must NOT leak across firms
        $this->postJson('/api/webhooks/finetuner/inbound', ['call_id' => 'X_LEAK', 'from' => '+15550001111'])
            ->assertNotFound();

        $this->postJson('/api/ai/finetuner/tools/get_services', ['provider_call_id' => 'X_TOOL_LEAK'])
            ->assertStatus(422);

        // 2. Spoken details & "same" caller phone resolution scoped strictly to Firm A
        app(FirmContext::class)->run((int) $firmA->id, function () use ($firmA, $lawyerA, $svcA) {
            $call = Call::query()->create([
                'firm_id' => $firmA->id,
                'provider_call_id' => 'CALL_SAME_1',
                'direction' => 'inbound',
                'caller_phone' => '+923009876543',
                'status' => 'in_progress',
                'started_at' => now(),
            ]);

            /** @var AiToolRegistry $registry */
            $registry = app(AiToolRegistry::class);

            $details = $registry->invoke('get_service_details', ['service_id' => $svcA->id]);
            $this->assertStringContainsString('three thousand rupees', $details['speak']);
            $this->assertStringContainsString('40 minutes', $details['speak']);
            $this->assertStringNotContainsString('Beta Consultation', json_encode($details));
            $this->assertStringNotContainsString('$', $details['speak']);
            $this->assertStringNotContainsString('.00', $details['speak']);

            $slots = $registry->invoke('find_available_slots', [
                'service_id' => $svcA->id,
                'lawyer_id' => $lawyerA->id,
                'date' => now('Asia/Karachi')->next(\Carbon\Carbon::MONDAY)->toDateString(),
            ]);
            $this->assertStringContainsString('40 minutes', $slots['speak']);
            $firstStart = $slots['slots'][0]['start_at'];

            $booked = $registry->invoke('book_appointment', [
                'call_id' => $call->id,
                'provider_call_id' => 'CALL_SAME_1',
                'service_id' => $svcA->id,
                'lawyer_id' => $lawyerA->id,
                'start_at' => $firstStart,
                'name' => 'Tariq Ali',
                'customer_phone' => 'same',
            ]);
            $this->assertTrue($booked['success'] ?? false);
            $this->assertStringContainsString('booked and confirmed', $booked['speak']);
            $this->assertDatabaseHas('leads', [
                'id' => $booked['appointment']['lead_id'],
                'firm_id' => $firmA->id,
                'phone' => '923009876543',
            ]);
        });
    }
}
