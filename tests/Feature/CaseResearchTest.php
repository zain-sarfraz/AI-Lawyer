<?php

namespace Tests\Feature;

use App\Domain\Firm\FirmContext;
use App\Domain\Firm\PermissionCatalog;
use App\Models\Firm;
use App\Models\Lawyer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Cases module: provider contract mapping, quota handling, honesty of outcome
 * reporting and permission gating. Every provider response here is a fake, so
 * the suite never spends the account's request quota.
 */
class CaseResearchTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://www.courtlistener.com/api/rest/v4';

    protected function setUp(): void
    {
        parent::setUp();

        PermissionCatalog::sync();
        config(['services.courtlistener.api_token' => 'secret-token-for-tests']);
    }

    private function lawyerUser(?string $country = null): User
    {
        $context = app(FirmContext::class);
        $context->forget();

        $firm = Firm::factory()->create();

        $user = $context->run($firm->id, function () use ($firm, $country) {
            $user = User::factory()->create(['firm_id' => $firm->id, 'email' => 'counsel-'.uniqid().'@example.test']);
            $user->assignRole('Lawyer');

            if ($country !== null) {
                Lawyer::query()->create([
                    'user_id' => $user->id,
                    'title' => 'Associate',
                    'country' => $country,
                    'status' => 'active',
                ]);
            }

            return $user;
        });

        $context->forget();

        return $user;
    }

    /**
     * One documented type=o search result: `court` is the court's full name and
     * `court_id` the abbreviation, and snippets are nested inside opinions.
     *
     * Verified against the live API: the response body carries only
     * count/next/previous/results (no page or page_size), `court_jurisdiction` is a
     * multi-character code and is often absent, and every page holds 20 records.
     */
    private function searchPayload(int $count = 4123): array
    {
        return [
            'count' => $count,
            'next' => self::BASE.'/search/?page=2',
            'previous' => null,
            'results' => [
                [
                    'cluster_id' => 18532081,
                    'caseName' => 'Doe v. Example Corp.',
                    'citation' => ['41 F.4th 101'],
                    'court' => 'United States Court of Appeals for the Ninth Circuit',
                    'court_id' => 'ca9',
                    'court_jurisdiction' => 'F',
                    'court_citation_string' => '9th Cir.',
                    'dateFiled' => '2025-11-04',
                    'docketNumber' => '24-1234',
                    'suitNature' => 'Labor',
                    'status' => 'Published',
                    'absolute_url' => '/opinion/18532081/doe-v-example-corp/',
                    'opinions' => [
                        ['snippet' => 'The <mark>district court</mark> dismissed the complaint.'],
                    ],
                ],
                [
                    'cluster_id' => 18532082,
                    'caseName' => 'United States v. Rivera',
                    'citation' => [],
                    'neutralCite' => '2025-11872',
                    'court' => 'Supreme Court of the United States',
                    'court_id' => 'scotus',
                    'dateFiled' => '2025-09-30',
                    'docketNumber' => '24A118',
                    'status' => 'Published',
                    'absolute_url' => '/opinion/18532082/united-states-v-rivera/',
                ],
            ],
        ];
    }

    public function test_guests_cannot_open_the_cases_module(): void
    {
        $this->get(route('case-research.index'))->assertRedirect(route('login'));
        $this->get(route('case-research.api.search'))->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_cases_permission_is_forbidden(): void
    {
        $firm = Firm::factory()->create();
        $user = User::factory()->create(['firm_id' => $firm->id, 'email' => 'no-access@example.test']);

        $this->actingAs($user)->get(route('case-research.index'))->assertForbidden();
    }

    public function test_the_landing_page_lists_the_supported_categories_and_provider(): void
    {
        Http::fake([self::BASE.'/*' => Http::response(['count' => 12], 200)]);

        $this->actingAs($this->lawyerUser())
            ->get(route('case-research.index'))
            ->assertOk()
            ->assertSee('Civil')
            ->assertSee('Intellectual Property')
            ->assertSee('CourtListener')
            ->assertSee('United States');
    }

    public function test_search_maps_the_documented_fields_and_pagination(): void
    {
        Http::fake([self::BASE.'/search/*' => Http::response($this->searchPayload(), 200)]);

        $response = $this->actingAs($this->lawyerUser())
            ->getJson(route('case-research.api.search', ['category' => 'civil', 'date_range' => '2y']));

        $response->assertOk()
            ->assertJsonPath('reason', 'ok')
            ->assertJsonPath('total', 4123)
            ->assertJsonPath('provider', 'CourtListener')
            ->assertJsonPath('country', 'US')
            ->assertJsonPath('has_next', true);

        $records = $response->json('results');

        $this->assertCount(2, $records);
        $this->assertSame('ca9', $records[0]['court_id']);
        $this->assertSame('United States Court of Appeals for the Ninth Circuit', $records[0]['court_name']);
        $this->assertSame('41 F.4th 101', $records[0]['citation']);
        $this->assertSame('The district court dismissed the complaint.', $records[0]['snippet']);
        $this->assertSame('https://www.courtlistener.com/opinion/18532081/doe-v-example-corp/', $records[0]['url']);
        $this->assertSame('Civil', $records[0]['category']);
        $this->assertSame('2025-09-30', $records[1]['date_filed']);
        $this->assertSame('2025-11872', $records[1]['citation']);
        // Jurisdiction is taken from the published code, never invented per court.
        $this->assertSame('F', $records[0]['jurisdiction_code']);
        $this->assertSame('Federal appellate', $records[0]['jurisdiction']);
        $this->assertNull($records[1]['jurisdiction_code']);
        // 4123 results over 20 per page would be 207 pages, capped by the provider.
        $this->assertSame(100, $response->json('total_pages'));
    }

    public function test_the_provider_page_size_is_used_even_when_a_larger_one_is_requested(): void
    {
        Http::fake([self::BASE.'/search/*' => Http::response($this->searchPayload(100), 200)]);

        $response = $this->actingAs($this->lawyerUser())
            ->getJson(route('case-research.api.search', ['category' => 'civil', 'per_page' => 50]));

        // CourtListener ignores page_size and always returns 20 records per page,
        // so the module must not promise a different page size to the user.
        $response->assertOk()
            ->assertJsonPath('per_page', 20)
            ->assertJsonPath('total_pages', 5);

        Http::assertSent(fn ($request) => ! str_contains($request->url(), 'page_size'));
    }

    public function test_win_loss_is_never_invented_when_the_source_publishes_no_disposition(): void
    {
        Http::fake([self::BASE.'/search/*' => Http::response($this->searchPayload(), 200)]);

        $response = $this->actingAs($this->lawyerUser())
            ->getJson(route('case-research.api.search', ['category' => 'criminal']));

        $response->assertJsonPath('stats.classifiable', 0)
            ->assertJsonPath('stats.win_pct', null)
            ->assertJsonPath('stats.loss_pct', null)
            ->assertJsonPath('stats.outcomes.unknown', 2);

        foreach ($response->json('results') as $record) {
            $this->assertSame('unknown', $record['outcome_class']);
            $this->assertSame('', $record['judgment_text']);
        }

        // Snippet text mentions "dismissed" — that must not become a verdict.
        $body = $response->getContent();
        $this->assertStringNotContainsString('"outcome_class":"won"', $body);
        $this->assertStringNotContainsString('Plaintiff Won', $body);
    }

    public function test_an_explicit_party_relative_outcome_is_the_only_way_to_classify_a_win(): void
    {
        $payload = $this->searchPayload(1);
        $payload['results'][0]['disposition'] = 'Affirmed';
        $payload['results'][0]['party_perspective'] = 'appellant';
        unset($payload['results'][1]);

        Http::fake([self::BASE.'/search/*' => Http::response($payload, 200)]);

        $response = $this->actingAs($this->lawyerUser())
            ->getJson(route('case-research.api.search', ['category' => 'civil']));

        // A disposition alone is not enough: the firm's side must be known.
        $response->assertJsonPath('results.0.outcome_class', 'unknown')
            ->assertJsonPath('results.0.judgment_text', 'Affirmed')
            ->assertJsonPath('stats.classifiable', 0);
    }

    public function test_a_rate_limited_provider_response_reports_the_wait_and_is_not_retried(): void
    {
        Http::fake([
            self::BASE.'/search/*' => Http::response(
                ['detail' => 'Request was throttled. Rate limit exceeded: 5/min.'],
                429,
                ['Retry-After' => '37'],
            ),
        ]);

        $this->actingAs($this->lawyerUser())
            ->getJson(route('case-research.api.search', ['category' => 'tax']))
            ->assertOk()
            ->assertJsonPath('reason', 'rate_limited')
            ->assertJsonPath('retry_after', 37)
            ->assertJsonPath('results', []);

        Http::assertSentCount(1);
    }

    public function test_identical_searches_are_served_from_cache_instead_of_the_quota(): void
    {
        Http::fake([self::BASE.'/search/*' => Http::response($this->searchPayload(), 200)]);

        $user = $this->lawyerUser();
        $url = route('case-research.api.search', ['category' => 'employment', 'q' => 'overtime']);

        $this->actingAs($user)->getJson($url)->assertOk();
        $this->actingAs($user)->getJson($url)->assertOk();

        Http::assertSentCount(1);
    }

    public function test_an_unsupported_country_returns_nothing_without_spending_a_request(): void
    {
        Http::fake([self::BASE.'/*' => Http::response($this->searchPayload(), 200)]);

        $this->actingAs($this->lawyerUser())
            ->getJson(route('case-research.api.search', ['country' => 'GB']))
            ->assertOk()
            ->assertJsonPath('reason', 'unsupported_country')
            ->assertJsonPath('results', []);

        Http::assertSentCount(0);
    }

    public function test_the_signup_country_defaults_the_filter_only_when_a_provider_covers_it(): void
    {
        Http::fake([self::BASE.'/search/*' => Http::response($this->searchPayload(), 200)]);

        $this->actingAs($this->lawyerUser('US'))
            ->getJson(route('case-research.api.search'))
            ->assertOk()
            ->assertJsonPath('filters.country', 'US');

        // A country with no data source must not become the default filter.
        $this->actingAs($this->lawyerUser('PK'))
            ->getJson(route('case-research.api.search'))
            ->assertOk()
            ->assertJsonPath('filters.country', '');
    }

    public function test_the_token_stays_server_side_and_is_never_returned_to_the_browser(): void
    {
        Http::fake([
            self::BASE.'/search/*' => Http::response($this->searchPayload(), 200),
            self::BASE.'/courts/*' => Http::response([
                'count' => 1,
                'next' => null,
                'results' => [[
                    'id' => 'ca9',
                    'full_name' => 'United States Court of Appeals for the Ninth Circuit',
                    'jurisdiction' => 'F',
                    'in_use' => true,
                ]],
            ], 200),
        ]);

        $user = $this->lawyerUser();

        foreach ([route('case-research.api.search'), route('case-research.api.courts')] as $url) {
            $response = $this->actingAs($user)->getJson($url);
            $this->assertStringNotContainsString('secret-token-for-tests', $response->getContent());
        }

        $this->actingAs($user)
            ->get(route('case-research.search'))
            ->assertOk()
            ->assertDontSee('secret-token-for-tests');
    }

    public function test_the_court_directory_is_offered_for_the_searchable_filter(): void
    {
        Http::fake([
            self::BASE.'/courts/*' => Http::response([
                'count' => 1,
                'next' => null,
                'results' => [[
                    'id' => 'scotus',
                    'full_name' => 'Supreme Court of the United States',
                    'jurisdiction' => 'F',
                    'in_use' => true,
                ]],
            ], 200),
        ]);

        $response = $this->actingAs($this->lawyerUser())
            ->getJson(route('case-research.api.courts'))
            ->assertOk()
            ->assertJsonPath('courts.0.id', 'scotus')
            ->assertJsonPath('courts.0.name', 'Supreme Court of the United States')
            ->assertJsonPath('courts.0.jurisdiction', 'Federal appellate');

        // The directory publishes no city or state, so no location may be claimed.
        $this->assertNull($response->json('courts.0.location'));
    }

    public function test_multi_character_jurisdiction_codes_are_labelled_or_passed_through_verbatim(): void
    {
        Http::fake([
            self::BASE.'/courts/*' => Http::response([
                'count' => 3,
                'next' => null,
                'results' => [
                    ['id' => 'fbap1', 'full_name' => 'Bankruptcy Appellate Panel of the First Circuit', 'jurisdiction' => 'FBP', 'in_use' => true],
                    ['id' => 'usag', 'full_name' => 'United States Attorneys General', 'jurisdiction' => 'FS', 'in_use' => true],
                    ['id' => 'ohio', 'full_name' => 'Supreme Court of Ohio', 'jurisdiction' => 'U', 'in_use' => true],
                ],
            ], 200),
        ]);

        $courts = $this->actingAs($this->lawyerUser())
            ->getJson(route('case-research.api.courts'))
            ->assertOk()
            ->json('courts');

        $this->assertSame('Federal bankruptcy appellate', $courts[0]['jurisdiction']);
        $this->assertSame('FBP', $courts[0]['jurisdiction_code']);
        $this->assertSame('Federal specialty tribunal', $courts[1]['jurisdiction']);
        // A code that has not been confirmed against the source is shown as published.
        $this->assertSame('U', $courts[2]['jurisdiction']);
    }

    public function test_coverage_probes_only_report_counts_the_provider_actually_returned(): void
    {
        Http::fake([self::BASE.'/search/*' => Http::response(['count' => 731], 200)]);

        $this->actingAs($this->lawyerUser())
            ->getJson(route('case-research.api.coverage', ['category' => 'bankruptcy']))
            ->assertOk()
            ->assertJsonPath('verified', true)
            ->assertJsonPath('count', 731);

        // Unknown categories are rejected before any request leaves the server.
        $this->actingAs($this->lawyerUser())
            ->getJson(route('case-research.api.coverage', ['category' => 'not_a_category']))
            ->assertStatus(422);
    }

    public function test_the_first_results_page_is_rendered_server_side(): void
    {
        Http::fake([self::BASE.'/search/*' => Http::response($this->searchPayload(), 200)]);

        $this->actingAs($this->lawyerUser())
            ->get(route('case-research.search', ['category' => 'civil']))
            ->assertOk()
            ->assertSee('Doe v. Example Corp.')
            ->assertSee('Records matching filters')
            ->assertDontSee('secret-token-for-tests');
    }

    public function test_case_detail_returns_the_source_record(): void
    {
        Http::fake([
            self::BASE.'/clusters/18532081/*' => Http::response([
                'id' => 18532081,
                'case_name' => 'Doe v. Example Corp.',
                'case_name_full' => 'Jane Doe v. Example Corporation',
                'date_filed' => '2025-11-04',
                'precedential_status' => 'Published',
                'nature_of_suit' => 'Labor',
                'court' => 'http://www.courtlistener.com/api/rest/v4/courts/ca9/',
                'absolute_url' => '/opinion/18532081/doe-v-example-corp/',
                'syllabus' => '',
            ], 200),
        ]);

        $this->actingAs($this->lawyerUser())
            ->getJson(route('case-research.api.case', 18532081))
            ->assertOk()
            ->assertJsonPath('case.case_name', 'Jane Doe v. Example Corporation')
            ->assertJsonPath('case.court_id', 'ca9')
            ->assertJsonPath('case.outcome_class', 'unknown');
    }

    public function test_a_record_the_provider_no_longer_publishes_is_reported_as_missing(): void
    {
        Http::fake([self::BASE.'/clusters/999/*' => Http::response(['detail' => 'Not found.'], 404)]);

        $this->actingAs($this->lawyerUser())
            ->getJson(route('case-research.api.case', 999))
            ->assertNotFound()
            ->assertJsonPath('reason', 'not_found');
    }

    public function test_an_outgoing_case_reference_is_rejected_before_any_request(): void
    {
        Http::fake();

        $this->actingAs($this->lawyerUser())
            ->getJson(route('case-research.api.case', ['caseId' => '0000000000000']))
            ->assertStatus(422);

        Http::assertSentCount(0);
    }

    public function test_an_unreachable_provider_is_reported_instead_of_fabricated(): void
    {
        Http::fake([self::BASE.'/search/*' => Http::response(null, 502)]);

        $this->actingAs($this->lawyerUser())
            ->getJson(route('case-research.api.search', ['category' => 'civil']))
            ->assertOk()
            ->assertJsonPath('reason', 'error')
            ->assertJsonPath('results', [])
            ->assertJsonPath('stats.total_found', 0);
    }

    public function test_missing_credentials_are_reported_rather_than_simulated(): void
    {
        config(['services.courtlistener.api_token' => null]);
        Http::fake();

        $this->actingAs($this->lawyerUser())
            ->getJson(route('case-research.api.search'))
            ->assertOk()
            ->assertJsonPath('reason', 'unconfigured')
            ->assertJsonPath('success', false);

        Http::assertSentCount(0);
    }
}
