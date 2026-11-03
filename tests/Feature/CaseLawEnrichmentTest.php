<?php

namespace Tests\Feature;

use App\Domain\CaseLaw\CaseExtractionValidator;
use App\Domain\Firm\FirmContext;
use App\Domain\Firm\PermissionCatalog;
use App\Models\CaseLawRecord;
use App\Models\Court;
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
        $criminal = $this->makeRecord([
            'external_id' => 'test-criminal-1',
            'jurisdiction_code' => 'PK-SD',
            'court_name' => 'Sindh High Court',
            'title' => 'State v Accused Person',
            'case_type' => 'criminal',
            'party_one_name' => 'The State',
            'party_one_role' => 'Prosecution / State',
            'party_two_name' => 'Accused Person',
            'party_two_role' => 'Accused',
            'winner' => 'party_two',
            'winner_label' => 'Accused',
            'disposition' => 'reversed',
            'judgment_url' => 'https://example.test/judgment/criminal-1',
            'ai_summary' => 'Conviction was reversed on appeal.',
        ]);
        $civil = $this->makeRecord([
            'external_id' => 'test-civil-1',
            'jurisdiction_code' => 'PK-FED',
            'court_name' => 'Supreme Court of Pakistan',
            'title' => 'Plaintiff Co v Defendant Co',
            'case_type' => 'civil',
            'party_one_name' => 'Plaintiff Co',
            'party_one_role' => 'Plaintiff',
            'party_two_name' => 'Defendant Co',
            'party_two_role' => 'Defendant',
            'winner' => 'not_stated',
            'disposition' => 'not_stated',
            'judgment_url' => null,
            'ai_summary' => null,
            'final_order' => null,
        ]);

        $this->actingAs($user)
            ->get(route('case-research.show', $criminal))
            ->assertOk()
            ->assertSee('Prosecution / State')
            ->assertSee('Accused')
            ->assertSee('Accused')
            ->assertSee('Reversed')
            ->assertSee('Open full judgment');

        $this->actingAs($user)
            ->get(route('case-research.show', $civil))
            ->assertOk()
            ->assertSee('Plaintiff')
            ->assertSee('Defendant')
            ->assertSee('Not stated in judgment');

        $this->actingAs($user)
            ->get(route('case-research.show', $civil))
            ->assertOk()
            ->assertDontSee('Open full judgment')
            ->assertSee('Read the judgment');
    }

    public function test_direct_judgment_url_and_null_fallback(): void
    {
        $user = $this->lawyerWithAllJurisdictions();
        $withUrl = $this->makeRecord([
            'external_id' => 'test-uk-1',
            'jurisdiction_code' => 'GB-EAW',
            'court_name' => 'Supreme Court of the United Kingdom',
            'title' => 'Example UK Appeal',
            'case_type' => 'appeal',
            'judgment_url' => 'https://www.supremecourt.uk/cases/uksc-2019-0192.html',
        ]);
        $withoutUrl = $this->makeRecord([
            'external_id' => 'test-uk-2',
            'jurisdiction_code' => 'GB-EAW',
            'court_name' => 'Supreme Court of the United Kingdom',
            'title' => 'Example UK Without Link',
            'case_type' => 'appeal',
            'judgment_url' => null,
        ]);

        $this->actingAs($user)
            ->get(route('case-research.show', $withUrl))
            ->assertOk()
            ->assertSee('https://www.supremecourt.uk/cases/uksc-2019-0192.html')
            ->assertSee('Open full judgment');

        $this->actingAs($user)
            ->get(route('case-research.show', $withoutUrl))
            ->assertOk()
            ->assertDontSee('Open full judgment')
            ->assertSee('Read the judgment');
    }

    /** @param  array<string, mixed>  $attrs */
    private function makeRecord(array $attrs): CaseLawRecord
    {
        $jurisdiction = Jurisdiction::query()->where('code', $attrs['jurisdiction_code'])->firstOrFail();
        $courtId = null;
        if (! empty($attrs['court_name'])) {
            $courtId = Court::query()
                ->where('jurisdiction_id', $jurisdiction->id)
                ->where('name', $attrs['court_name'])
                ->value('id');
        }

        return CaseLawRecord::query()->create([
            'jurisdiction_id' => $jurisdiction->id,
            'court_id' => $courtId,
            'source' => 'test',
            'external_id' => $attrs['external_id'],
            'title' => $attrs['title'],
            'case_type' => $attrs['case_type'] ?? 'civil',
            'party_one_name' => $attrs['party_one_name'] ?? null,
            'party_one_role' => $attrs['party_one_role'] ?? null,
            'party_two_name' => $attrs['party_two_name'] ?? null,
            'party_two_role' => $attrs['party_two_role'] ?? null,
            'winner' => $attrs['winner'] ?? null,
            'winner_label' => $attrs['winner_label'] ?? null,
            'disposition' => $attrs['disposition'] ?? null,
            'final_order' => $attrs['final_order'] ?? null,
            'ai_summary' => $attrs['ai_summary'] ?? null,
            'judgment_url' => $attrs['judgment_url'] ?? null,
            'source_url' => $attrs['judgment_url'] ?? null,
            'is_demo' => false,
            'decision_date' => now()->toDateString(),
            'fetched_at' => now(),
        ]);
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
