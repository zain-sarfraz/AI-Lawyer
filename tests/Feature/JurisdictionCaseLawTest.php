<?php

namespace Tests\Feature;

use App\Domain\CaseLaw\CaseLawUpserter;
use App\Domain\CaseLaw\Contracts\CaseSourceAdapter;
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
use Tests\TestCase;

class JurisdictionCaseLawTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PermissionCatalog::sync();
        $this->seed(JurisdictionSeeder::class);
    }

    public function test_phone_normalizer_strips_leading_zero_for_pk_gb_and_us(): void
    {
        $this->assertSame('+923247087907', PhoneNormalizer::normalize('PK', '03247087907'));
        $this->assertSame('+923247087907', PhoneNormalizer::normalize('PK', '3247087907'));
        $this->assertSame('+443247087907', PhoneNormalizer::normalize('GB', '03247087907'));
        $this->assertSame('+447911123456', PhoneNormalizer::normalize('GB', '07911123456'));
        $this->assertSame('+12025550123', PhoneNormalizer::normalize('US', '2025550123'));
        $this->assertSame('+14165550123', PhoneNormalizer::normalize('CA', '4165550123'));
        $this->assertSame('+61412345678', PhoneNormalizer::normalize('AU', '0412345678'));
    }

    public function test_lawyer_jurisdiction_sync_on_create_and_update(): void
    {
        [$admin, $firm] = $this->adminUser();
        $pkFed = Jurisdiction::query()->where('code', 'PK-FED')->firstOrFail();
        $pkSd = Jurisdiction::query()->where('code', 'PK-SD')->firstOrFail();
        $gb = Jurisdiction::query()->where('code', 'GB-EAW')->firstOrFail();

        $this->actingAs($admin)->post(route('lawyers.store'), [
            'name' => 'Counsel One',
            'email' => 'counsel1@example.test',
            'phone_country' => 'PK',
            'phone_local' => '3247087907',
            'jurisdiction_ids' => [$pkFed->id, $pkSd->id],
            'status' => 'active',
        ])->assertRedirect(route('lawyers.index'));

        $lawyer = Lawyer::query()->whereHas('user', fn ($q) => $q->where('email', 'counsel1@example.test'))->firstOrFail();
        $this->assertEqualsCanonicalizing([$pkFed->id, $pkSd->id], $lawyer->jurisdictions()->pluck('jurisdictions.id')->all());
        $this->assertSame('+923247087907', $lawyer->phone);

        $this->actingAs($admin)->put(route('lawyers.update', $lawyer), [
            'name' => 'Counsel One',
            'email' => 'counsel1@example.test',
            'phone_country' => 'GB',
            'phone_local' => '7911123456',
            'jurisdiction_ids' => [$gb->id],
            'status' => 'active',
        ])->assertRedirect(route('lawyers.index'));

        $lawyer->refresh();
        $this->assertEqualsCanonicalizing([$gb->id], $lawyer->jurisdictions()->pluck('jurisdictions.id')->all());
        $this->assertSame('+447911123456', $lawyer->phone);
        $this->assertSame('GB', $lawyer->country);
    }

    public function test_relevant_cases_are_filtered_to_lawyer_jurisdictions(): void
    {
        [$admin] = $this->adminUser();
        $pkSd = Jurisdiction::query()->where('code', 'PK-SD')->firstOrFail();
        $gb = Jurisdiction::query()->where('code', 'GB-EAW')->firstOrFail();

        CaseLawRecord::query()->create([
            'jurisdiction_id' => $pkSd->id,
            'source' => 'test',
            'external_id' => 'pk-sd-real-1',
            'title' => 'Sindh Real Matter',
            'judgment_url' => 'https://example.test/pk/1',
            'is_demo' => false,
            'decision_date' => now()->toDateString(),
            'fetched_at' => now(),
        ]);
        CaseLawRecord::query()->create([
            'jurisdiction_id' => $gb->id,
            'source' => 'test',
            'external_id' => 'gb-real-1',
            'title' => 'UK Real Matter',
            'judgment_url' => 'https://example.test/gb/1',
            'is_demo' => false,
            'decision_date' => now()->toDateString(),
            'fetched_at' => now(),
        ]);

        $lawyer = null;
        app(FirmContext::class)->run($admin->firm_id, function () use ($admin, $pkSd, &$lawyer) {
            $lawyer = Lawyer::query()->create([
                'user_id' => User::factory()->create(['firm_id' => $admin->firm_id])->id,
                'title' => 'Associate',
                'country' => 'PK',
                'phone' => '+923001234567',
                'status' => 'active',
            ]);
            $lawyer->jurisdictions()->sync([$pkSd->id]);
        });

        $this->actingAs($admin)
            ->get(route('lawyers.relevant-cases', $lawyer))
            ->assertOk()
            ->assertSee('Sindh Real Matter')
            ->assertDontSee('UK Real Matter');

        $lawyer->jurisdictions()->sync([$gb->id]);

        $this->actingAs($admin)
            ->get(route('lawyers.relevant-cases', $lawyer))
            ->assertOk()
            ->assertSee('UK Real Matter')
            ->assertDontSee('Sindh Real Matter');
    }

    public function test_case_law_upsert_does_not_duplicate_on_repeated_fetch(): void
    {
        $jurisdiction = Jurisdiction::query()->where('code', 'PK-SD')->firstOrFail();

        $fake = new class implements CaseSourceAdapter
        {
            public function search(array $params): iterable
            {
                yield [
                    'external_id' => 'fake-1',
                    'source' => 'fake-adapter',
                    'title' => 'Fake v Fake',
                    'citation' => '2024 Fake 1',
                    'decision_date' => '2024-01-01',
                    'court_name' => 'Sindh High Court',
                    'judgment_url' => 'https://example.test/fake/1',
                    'full_text' => 'Body',
                ];
            }

            public function fetch(string $externalId): array
            {
                return [];
            }
        };

        $this->app->instance(\App\Domain\CaseLaw\Adapters\PakistanCaseLawAdapter::class, $fake);

        $job = new FetchCasesForJurisdictionJob('PK-SD', 10);
        $job->handle(app(\App\Domain\CaseLaw\CaseSourceRegistry::class), app(CaseLawUpserter::class));
        $job->handle(app(\App\Domain\CaseLaw\CaseSourceRegistry::class), app(CaseLawUpserter::class));

        $this->assertSame(1, CaseLawRecord::query()->where('source', 'fake-adapter')->where('external_id', 'fake-1')->count());
    }

    public function test_us_fed_and_inactive_states_are_seeded(): void
    {
        $this->assertTrue(Jurisdiction::query()->where('code', 'US-FED')->where('is_active', true)->exists());
        $this->assertTrue(Jurisdiction::query()->where('code', 'US-CA')->where('is_active', false)->exists());
        $this->assertSame(51, Jurisdiction::query()->where('country_code', 'US')->where('code', '!=', 'US-FED')->count());
        $this->assertTrue(\App\Models\Court::query()->where('external_court_id', 'scotus')->exists());
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
