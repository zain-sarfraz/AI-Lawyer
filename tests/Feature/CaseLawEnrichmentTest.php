<?php

namespace Tests\Feature;

use App\Domain\CaseLaw\CaseExtractionValidator;
use App\Domain\Firm\FirmContext;
use App\Domain\Firm\PermissionCatalog;
use App\Models\CaseLawRecord;
use App\Models\Firm;
use App\Models\Jurisdiction;
use App\Models\Lawyer;
use App\Models\User;
use App\Support\Legal\CaseLabels;
use Database\Seeders\JurisdictionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CaseLawEnrichmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        PermissionCatalog::sync();
        $this->seed(JurisdictionSeeder::class);
    }

    public function test_case_labels_by_type(): void
    {
        $this->assertSame(
            ['party_one' => 'Prosecution / State', 'party_two' => 'Accused'],
            CaseLabels::for('criminal')
        );
        $this->assertSame(
            ['party_one' => 'Plaintiff', 'party_two' => 'Defendant'],
            CaseLabels::for('civil')
        );
        $this->assertSame(
            ['party_one' => 'Petitioner', 'party_two' => 'Respondent'],
            CaseLabels::for('writ')
        );
        $this->assertSame(
            ['party_one' => 'Appellant', 'party_two' => 'Respondent'],
            CaseLabels::for('appeal')
        );
        $this->assertSame(
            ['party_one' => 'Applicant', 'party_two' => 'Opposite party'],
            CaseLabels::for('family', 'Applicant', 'Opposite party')
        );
    }

    public function test_extraction_validator_accepts_not_stated_and_rejects_missing_fields(): void
    {
        $validator = new CaseExtractionValidator;

        $valid = $validator->validate($this->validPayload([
            'winner' => 'not_stated',
            'disposition' => 'not_stated',
            'party_one_evidence' => [],
            'party_two_evidence' => [],
        ]));
        $this->assertSame('not_stated', $valid['winner']);
        $this->assertSame('not_stated', $valid['disposition']);

        $this->expectException(InvalidArgumentException::class);
        $validator->validate(['case_type' => 'civil']);
    }

    public function test_details_page_for_criminal_civil_and_empty_evidence(): void
    {
        $user = $this->lawyerWithAllJurisdictions();

        $criminal = CaseLawRecord::query()->where('external_id', 'shc-demo-2021-cr-a-455')->firstOrFail();
        $civil = CaseLawRecord::query()->where('external_id', 'scp-demo-2019-cpla-100')->firstOrFail();
        $empty = CaseLawRecord::query()->where('external_id', 'scp-demo-2019-cpla-100')->firstOrFail();

        $this->actingAs($user)
            ->get(route('case-research.show', $criminal))
            ->assertOk()
            ->assertSee('Prosecution / State')
            ->assertSee('Accused')
            ->assertSee('Winner: Accused')
            ->assertSee('Reversed')
            ->assertSee('Demo data')
            ->assertSee('Open full judgment');

        $this->actingAs($user)
            ->get(route('case-research.show', $civil))
            ->assertOk()
            ->assertSee('Plaintiff')
            ->assertSee('Defendant')
            ->assertSee('Not stated in judgment')
            ->assertSee('Not available');

        $this->actingAs($user)
            ->get(route('case-research.show', $empty))
            ->assertOk()
            ->assertSee('Source link unavailable');
    }

    public function test_direct_judgment_url_and_null_fallback(): void
    {
        $user = $this->lawyerWithAllJurisdictions();
        $withUrl = CaseLawRecord::query()->where('external_id', 'uksc-demo-2019-41')->firstOrFail();
        $withoutUrl = CaseLawRecord::query()->where('external_id', 'scp-demo-2019-cpla-100')->firstOrFail();

        $this->actingAs($user)
            ->get(route('case-research.show', $withUrl))
            ->assertOk()
            ->assertSee('https://www.supremecourt.uk/cases/uksc-2019-0192.html')
            ->assertDontSee('Source link unavailable');

        $this->actingAs($user)
            ->get(route('case-research.show', $withoutUrl))
            ->assertOk()
            ->assertSee('Source link unavailable');
    }

    /** @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'case_type' => 'civil',
            'parties' => [['name' => 'A', 'role' => 'Plaintiff'], ['name' => 'B', 'role' => 'Defendant']],
            'bench' => ['Judge X'],
            'author_judge' => 'Judge X',
            'summary' => 'A short summary of the case.',
            'legal_issues' => ['Issue one'],
            'laws_applied' => [['statute' => 'Act', 'section' => '1', 'description' => 'desc']],
            'party_one_evidence' => [['point' => 'Doc A', 'paragraph_ref' => '¶1']],
            'party_two_evidence' => [['point' => 'Doc B', 'paragraph_ref' => null]],
            'court_reasoning' => [['point' => 'Because of X', 'paragraph_ref' => '¶2']],
            'winner' => 'party_one',
            'disposition' => 'allowed',
            'final_order' => 'Petition allowed.',
            'cited_cases' => [['title' => 'Prior', 'citation' => '1 SCR 1', 'context' => 'Cited']],
            'keywords' => ['land'],
            'confidence' => 0.7,
        ], $overrides);
    }

    private function lawyerWithAllJurisdictions(): User
    {
        $context = app(FirmContext::class);
        $context->forget();
        $firm = Firm::factory()->create();

        return $context->run($firm->id, function () use ($firm) {
            $user = User::factory()->create(['firm_id' => $firm->id]);
            $user->assignRole('Lawyer');
            $lawyer = Lawyer::query()->create([
                'user_id' => $user->id,
                'title' => 'Associate',
                'country' => 'PK',
                'status' => 'active',
            ]);
            $lawyer->jurisdictions()->sync(Jurisdiction::query()->pluck('id')->all());

            return $user;
        });
    }
}
