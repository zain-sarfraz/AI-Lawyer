<?php

namespace Tests\Feature;

use App\Domain\CaseLaw\Adapters\CourtListenerAdapter;
use App\Domain\CaseLaw\CaseLawUpserter;
use App\Domain\CaseLaw\CaseSourceRegistry;
use App\Domain\CaseLaw\CourtListenerRateLimiter;
use App\Domain\Firm\FirmContext;
use App\Domain\Firm\PermissionCatalog;
use App\Jobs\FetchCasesForJurisdictionJob;
use App\Models\CaseLawRecord;
use App\Models\Firm;
use App\Models\Jurisdiction;
use App\Models\Lawyer;
use App\Models\User;
use App\Support\Phone\PhoneNormalizer;
use Database\Seeders\JurisdictionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CourtListenerCaseLawTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PermissionCatalog::sync();
        $this->seed(JurisdictionSeeder::class);
        config([
            'case_law.courtlistener.enabled' => true,
            'case_law.courtlistener.api_token' => 'test-token',
            'case_law.courtlistener.max_per_minute' => 100,
            'case_law.courtlistener.max_per_hour' => 1000,
            'case_law.courtlistener.max_per_day' => 5,
            'case_law.courtlistener.base_url' => 'https://www.courtlistener.com/api/rest/v4',
        ]);
        Cache::flush();
    }

    public function test_demo_and_incomplete_rows_never_appear_in_lists_or_details(): void
    {
        [$admin] = $this->adminUser();
        $us = Jurisdiction::query()->where('code', 'US-FED')->firstOrFail();

        $real = CaseLawRecord::query()->create([
            'jurisdiction_id' => $us->id,
            'source' => 'courtlistener',
            'external_id' => 'cl-1',
            'title' => 'Real US Matter',
            'judgment_url' => 'https://www.courtlistener.com/opinion/1/real/',
            'is_demo' => false,
            'decision_date' => now()->toDateString(),
            'fetched_at' => now(),
        ]);
        CaseLawRecord::query()->create([
            'jurisdiction_id' => $us->id,
            'source' => 'demo',
            'external_id' => 'demo-1',
            'title' => 'State v. Bilal Khan',
            'judgment_url' => 'https://example.test/demo',
            'is_demo' => true,
            'decision_date' => now()->toDateString(),
            'fetched_at' => now(),
        ]);
        CaseLawRecord::query()->create([
            'jurisdiction_id' => $us->id,
            'source' => 'courtlistener',
            'external_id' => 'cl-missing-url',
            'title' => 'Missing URL Matter',
            'judgment_url' => null,
            'is_demo' => false,
            'decision_date' => now()->toDateString(),
            'fetched_at' => now(),
        ]);

        $lawyer = $this->makeLawyer($admin, [$us->id], 'US');

        $this->actingAs($admin)
            ->get(route('lawyers.relevant-cases', $lawyer))
            ->assertOk()
            ->assertSee('Real US Matter')
            ->assertDontSee('State v. Bilal Khan')
            ->assertDontSee('Missing URL Matter');

        $this->actingAs($admin)
            ->get(route('case-research.index'))
            ->assertOk()
            ->assertSee('Real US Matter')
            ->assertDontSee('State v. Bilal Khan');

        $this->actingAs($admin)
            ->get(route('case-research.show', $real))
            ->assertOk();

        $demo = CaseLawRecord::query()->where('title', 'State v. Bilal Khan')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('case-research.show', $demo))
            ->assertNotFound();
    }

    public function test_us_lawyer_sees_only_us_real_cases(): void
    {
        [$admin] = $this->adminUser();
        $us = Jurisdiction::query()->where('code', 'US-FED')->firstOrFail();
        $pk = Jurisdiction::query()->where('code', 'PK-SD')->firstOrFail();

        CaseLawRecord::query()->create([
            'jurisdiction_id' => $us->id,
            'source' => 'courtlistener',
            'external_id' => 'cl-us-9',
            'title' => 'United States v. Example',
            'judgment_url' => 'https://www.courtlistener.com/opinion/9/us/',
            'is_demo' => false,
            'decision_date' => now()->toDateString(),
            'fetched_at' => now(),
        ]);
        CaseLawRecord::query()->create([
            'jurisdiction_id' => $pk->id,
            'source' => 'pakistan_case_law',
            'external_id' => 'pk-1',
            'title' => 'Sindh Matter Only',
            'judgment_url' => 'https://example.test/pk/1',
            'is_demo' => false,
            'decision_date' => now()->toDateString(),
            'fetched_at' => now(),
        ]);

        $usLawyer = $this->makeLawyer($admin, [$us->id], 'US');
        $pkLawyer = $this->makeLawyer($admin, [$pk->id], 'PK', 'other@example.test');

        $this->actingAs($admin)
            ->get(route('lawyers.relevant-cases', $usLawyer))
            ->assertOk()
            ->assertSee('United States v. Example')
            ->assertDontSee('Sindh Matter Only');

        $this->actingAs($admin)
            ->get(route('lawyers.relevant-cases', $pkLawyer))
            ->assertOk()
            ->assertSee('Sindh Matter Only')
            ->assertDontSee('United States v. Example');
    }

    public function test_courtlistener_upsert_has_no_duplicates_on_repeat(): void
    {
        Http::fake([
            '*/search/*' => Http::response([
                'count' => 1,
                'next' => null,
                'results' => [[
                    'cluster_id' => 42,
                    'caseName' => 'Foo v. Bar',
                    'dateFiled' => '2024-06-01',
                    'court_id' => 'scotus',
                    'absolute_url' => '/opinion/42/foo-v-bar/',
                    'citation' => ['1 U.S. 1'],
                ]],
            ], 200),
        ]);

        $job = new FetchCasesForJurisdictionJob('US-FED', 5, [
            'court' => 'scotus',
            'from' => '2023-01-01',
            'to' => '2025-12-31',
            'enrich' => false,
        ]);
        $job->handle(app(CaseSourceRegistry::class), app(CaseLawUpserter::class));
        $job->handle(app(CaseSourceRegistry::class), app(CaseLawUpserter::class));

        $this->assertSame(1, CaseLawRecord::query()->where('source', 'courtlistener')->where('external_id', 'cl-42')->count());
        $row = CaseLawRecord::query()->where('external_id', 'cl-42')->firstOrFail();
        $this->assertSame('https://www.courtlistener.com/opinion/42/foo-v-bar/', $row->judgment_url);
        $this->assertFalse($row->is_demo);
    }

    public function test_rate_limiter_stops_on_daily_budget_and_saves_cursor(): void
    {
        // Search-only path: 1 req for page 1, then next page trips daily budget (max_per_day=1).
        config(['case_law.courtlistener.max_per_day' => 1]);
        Cache::flush();

        Http::fake([
            '*/search/*' => Http::sequence()
                ->push([
                    'count' => 2,
                    'next' => 'https://www.courtlistener.com/api/rest/v4/search/?cursor=abc',
                    'results' => [[
                        'cluster_id' => 1,
                        'caseName' => 'One',
                        'dateFiled' => '2024-01-01',
                        'court_id' => 'scotus',
                        'absolute_url' => '/opinion/1/one/',
                    ]],
                ], 200)
                ->push([
                    'count' => 1,
                    'next' => null,
                    'results' => [[
                        'cluster_id' => 2,
                        'caseName' => 'Two',
                        'dateFiled' => '2024-01-02',
                        'court_id' => 'scotus',
                        'absolute_url' => '/opinion/2/two/',
                    ]],
                ], 200),
        ]);

        $adapter = app(CourtListenerAdapter::class);
        $collected = [];
        try {
            foreach ($adapter->search([
                'limit' => 10,
                'court' => 'scotus',
                'from' => '2023-01-01',
                'to' => '2025-12-31',
                'jurisdiction_code' => 'US-FED',
                'enrich' => false,
            ]) as $row) {
                $collected[] = $row;
            }
            $this->fail('Expected daily budget RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('daily budget', strtolower($e->getMessage()));
        }

        $this->assertCount(1, $collected);
        $this->assertSame(
            'https://www.courtlistener.com/api/rest/v4/search/?cursor=abc',
            Cache::get('courtlistener:cursor:US-FED:scotus:2023-01-01:2025-12-31')
        );
    }

    public function test_rate_limiter_honors_http_429(): void
    {
        $limiter = app(CourtListenerRateLimiter::class);
        config(['case_law.courtlistener.retry_after_default' => 0]);

        Http::fake([
            '*/clusters/7/*' => Http::sequence()
                ->push(['detail' => 'slow down'], 429, ['Retry-After' => '0'])
                ->push([
                    'id' => 7,
                    'case_name' => 'Retry Case',
                    'date_filed' => '2024-01-01',
                    'absolute_url' => '/opinion/7/retry/',
                    'sub_opinions' => [],
                ], 200),
        ]);

        // Exercise getJson path via fetch()
        $row = app(CourtListenerAdapter::class)->fetch('7');
        $this->assertSame('cl-7', $row['external_id']);
        $this->assertSame('Retry Case', $row['title']);
        $this->assertGreaterThanOrEqual(0, $limiter->remaining('day'));
    }

    public function test_lawyer_form_saves_practice_jurisdictions_separately_from_phone(): void
    {
        [$admin] = $this->adminUser();
        $us = Jurisdiction::query()->where('code', 'US-FED')->firstOrFail();
        $pkFed = Jurisdiction::query()->where('code', 'PK-FED')->firstOrFail();
        $pkSd = Jurisdiction::query()->where('code', 'PK-SD')->firstOrFail();

        $this->assertSame('+12025550123', PhoneNormalizer::normalize('US', '2025550123'));
        $this->assertSame('+923247087907', PhoneNormalizer::normalize('PK', '03247087907'));

        $this->actingAs($admin)->post(route('lawyers.store'), [
            'name' => 'US Counsel',
            'email' => 'us.counsel@example.test',
            'phone_country' => 'US',
            'phone_local' => '2025550123',
            'jurisdiction_ids' => [$us->id, $pkFed->id],
            'status' => 'active',
        ])->assertRedirect(route('lawyers.index'));

        $lawyer = Lawyer::query()->whereHas('user', fn ($q) => $q->where('email', 'us.counsel@example.test'))->firstOrFail();
        $this->assertSame('US', $lawyer->country);
        $this->assertSame('+12025550123', $lawyer->phone);
        $this->assertEqualsCanonicalizing([$us->id, $pkFed->id], $lawyer->jurisdictions()->pluck('jurisdictions.id')->all());

        $this->actingAs($admin)->put(route('lawyers.update', $lawyer), [
            'name' => 'US Counsel',
            'email' => 'us.counsel@example.test',
            'phone_country' => 'PK',
            'phone_local' => '03247087907',
            'jurisdiction_ids' => [$us->id],
            'status' => 'active',
        ])->assertRedirect(route('lawyers.index'));

        $lawyer->refresh();
        $this->assertSame('PK', $lawyer->country);
        $this->assertSame('+923247087907', $lawyer->phone);
        $this->assertEqualsCanonicalizing([$us->id], $lawyer->jurisdictions()->pluck('jurisdictions.id')->all());
        $this->assertNotContains($pkSd->id, $lawyer->jurisdictions()->pluck('jurisdictions.id')->all());
    }

    public function test_adapter_refuses_when_disabled_or_token_missing(): void
    {
        config(['case_law.courtlistener.enabled' => false]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('COURTLISTENER_ENABLED');
        iterator_to_array(app(CourtListenerAdapter::class)->search(['limit' => 1]));
    }

    private function makeLawyer(User $admin, array $jurisdictionIds, string $country, string $email = 'lawyer@example.test'): Lawyer
    {
        $lawyer = null;
        app(FirmContext::class)->run($admin->firm_id, function () use ($admin, $jurisdictionIds, $country, $email, &$lawyer) {
            $lawyer = Lawyer::query()->create([
                'user_id' => User::factory()->create(['firm_id' => $admin->firm_id, 'email' => $email])->id,
                'title' => 'Associate',
                'country' => $country,
                'phone' => '+10000000000',
                'status' => 'active',
            ]);
            $lawyer->jurisdictions()->sync($jurisdictionIds);
        });

        return $lawyer;
    }

    /** @return array{0: User, 1: Firm} */
    private function adminUser(): array
    {
        $context = app(FirmContext::class);
        $context->forget();
        $firm = Firm::factory()->create();

        $user = $context->run($firm->id, function () use ($firm) {
            $user = User::factory()->create(['firm_id' => $firm->id]);
            $user->assignRole('Lawyer');

            return $user;
        });

        return [$user, $firm];
    }
}
