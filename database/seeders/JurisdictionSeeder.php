<?php

namespace Database\Seeders;

use App\Models\CaseLawRecord;
use App\Models\Court;
use App\Models\Jurisdiction;
use Illuminate\Database\Seeder;

class JurisdictionSeeder extends Seeder
{
    public function run(): void
    {
        $defs = [
            ['code' => 'PK-FED', 'country_code' => 'PK', 'name' => 'Pakistan Federal', 'is_active' => true],
            ['code' => 'PK-SD', 'country_code' => 'PK', 'name' => 'Sindh (Pakistan)', 'is_active' => true],
            ['code' => 'PK-PB', 'country_code' => 'PK', 'name' => 'Punjab (Pakistan)', 'is_active' => false],
            ['code' => 'PK-IS', 'country_code' => 'PK', 'name' => 'Islamabad (Pakistan)', 'is_active' => false],
            ['code' => 'GB-EAW', 'country_code' => 'GB', 'name' => 'United Kingdom (England & Wales)', 'is_active' => true],
            ['code' => 'CA-FED', 'country_code' => 'CA', 'name' => 'Canada (Federal)', 'is_active' => true],
            ['code' => 'CA-ON', 'country_code' => 'CA', 'name' => 'Ontario (Canada)', 'is_active' => true],
        ];

        $ids = [];
        foreach ($defs as $def) {
            $j = Jurisdiction::query()->updateOrCreate(['code' => $def['code']], $def);
            $ids[$def['code']] = $j->id;
        }

        $courts = [
            ['jurisdiction' => 'PK-FED', 'name' => 'Supreme Court of Pakistan', 'level' => 'supreme'],
            ['jurisdiction' => 'PK-FED', 'name' => 'Federal Shariat Court', 'level' => 'high'],
            ['jurisdiction' => 'PK-SD', 'name' => 'Sindh High Court', 'level' => 'high'],
            ['jurisdiction' => 'PK-PB', 'name' => 'Lahore High Court', 'level' => 'high'],
            ['jurisdiction' => 'PK-IS', 'name' => 'Islamabad High Court', 'level' => 'high'],
            ['jurisdiction' => 'GB-EAW', 'name' => 'Supreme Court of the United Kingdom', 'level' => 'supreme'],
            ['jurisdiction' => 'GB-EAW', 'name' => 'Court of Appeal', 'level' => 'appeal'],
            ['jurisdiction' => 'GB-EAW', 'name' => 'High Court', 'level' => 'high'],
            ['jurisdiction' => 'GB-EAW', 'name' => 'Upper Tribunal', 'level' => 'tribunal'],
            ['jurisdiction' => 'CA-FED', 'name' => 'Supreme Court of Canada', 'level' => 'supreme'],
            ['jurisdiction' => 'CA-FED', 'name' => 'Federal Court of Appeal', 'level' => 'appeal'],
            ['jurisdiction' => 'CA-FED', 'name' => 'Federal Court', 'level' => 'high'],
            ['jurisdiction' => 'CA-ON', 'name' => 'Court of Appeal for Ontario', 'level' => 'appeal'],
            ['jurisdiction' => 'CA-ON', 'name' => 'Ontario Superior Court of Justice', 'level' => 'high'],
        ];

        $courtIds = [];
        foreach ($courts as $court) {
            $model = Court::query()->updateOrCreate(
                [
                    'jurisdiction_id' => $ids[$court['jurisdiction']],
                    'name' => $court['name'],
                ],
                ['level' => $court['level']]
            );
            $courtIds[$court['name']] = $model->id;
        }

        $samples = [
            [
                'jurisdiction' => 'PK-SD',
                'court' => 'Sindh High Court',
                'external_id' => 'shc-demo-2019-cp-d-1234',
                'title' => 'Ahmed v. Province of Sindh',
                'case_number' => 'C.P. No. D-1234 of 2019',
                'citation' => 'PLD 2020 Sindh 112',
                'neutral_citation' => '2020 SHC 112',
                'case_type' => 'writ',
                'party_one_name' => 'Ahmed',
                'party_one_role' => 'Petitioner',
                'party_two_name' => 'Province of Sindh',
                'party_two_role' => 'Respondent',
                'bench_json' => ['Munib Akhtar, J.', 'Muhammad Shafi Siddiqui, J.'],
                'author_judge' => 'Munib Akhtar, J.',
                'filing_date' => '2019-03-14',
                'decision_date' => '2020-01-22',
                'judgment_url' => 'https://caselaw.shc.gov.pk/caselaw/view-case/demo-2019-cp-d-1234',
                'pdf_url' => null,
                'is_reported' => true,
                'full_text' => "1. This constitution petition challenges a land acquisition notification.\n2. The petitioner produced mutation extracts and the acquisition notice.\n3. The Province relied on a public-purpose certificate.\n4. We find the notice procedurally defective under Article 23.\n5. The petition is allowed; the notification is set aside.",
                'ai_summary' => 'The Sindh High Court granted a constitution petition challenging a provincial land acquisition notification, finding procedural defects affecting property rights under Article 23.',
                'winner' => 'party_one',
                'winner_label' => 'Petitioner',
                'disposition' => 'allowed',
                'final_order' => 'Petition allowed; impugned acquisition notification set aside.',
                'laws_applied_json' => [
                    ['statute' => 'Constitution of Pakistan', 'section' => 'Article 23', 'description' => 'Property rights'],
                    ['statute' => 'Land Acquisition Act', 'section' => 's.4', 'description' => 'Notification procedure'],
                ],
                'legal_issues_json' => [
                    'Whether the acquisition notification complied with mandatory procedure',
                    'Scope of Article 23 protections against defective acquisition',
                ],
                'party_one_evidence_json' => [
                    ['point' => 'Mutation extracts showing title', 'paragraph_ref' => '¶2'],
                    ['point' => 'Copy of acquisition notice served on petitioner', 'paragraph_ref' => '¶2'],
                ],
                'party_two_evidence_json' => [
                    ['point' => 'Public-purpose certificate issued by the Board of Revenue', 'paragraph_ref' => '¶3'],
                ],
                'court_reasoning_json' => [
                    ['point' => 'Notice failed mandatory particulars required for a valid acquisition', 'paragraph_ref' => '¶4'],
                    ['point' => 'Procedural unfairness engages Article 23', 'paragraph_ref' => '¶4'],
                ],
                'cited_cases_json' => [
                    ['title' => 'Government of Sindh v. Landowners', 'citation' => 'PLD 2015 SC 200', 'context' => 'Cited on acquisition procedure'],
                ],
                'keywords_json' => ['land acquisition', 'Article 23', 'Sindh'],
                'ai_status' => 'done',
                'ai_confidence' => 0.82,
                'ai_model' => 'demo-seed',
                'ai_extracted_at' => now(),
            ],
            [
                'jurisdiction' => 'PK-SD',
                'court' => 'Sindh High Court',
                'external_id' => 'shc-demo-2021-cr-a-455',
                'title' => 'State v. Bilal Khan',
                'case_number' => 'Cr. Appeal No. 455 of 2021',
                'citation' => '2022 YLR 901',
                'neutral_citation' => '2022 SHC Crim 455',
                'case_type' => 'criminal',
                'party_one_name' => 'The State',
                'party_one_role' => 'Prosecution / State',
                'party_two_name' => 'Bilal Khan',
                'party_two_role' => 'Accused',
                'bench_json' => ['Naimatullah Phulpoto, J.'],
                'author_judge' => 'Naimatullah Phulpoto, J.',
                'filing_date' => '2021-06-02',
                'decision_date' => '2022-04-18',
                'judgment_url' => 'https://caselaw.shc.gov.pk/caselaw/view-case/demo-2021-cr-a-455',
                'is_reported' => true,
                'full_text' => "1. Appeal against conviction under PPC 302.\n2. Prosecution led ocular evidence of PW-1 and the FIR.\n3. Defence highlighted the delayed FIR and contradictions.\n4. Ocular account is unreliable; benefit of doubt applies.\n5. Appeal allowed; conviction set aside; appellant acquitted.",
                'ai_summary' => 'On appeal against a murder conviction, the Sindh High Court found the ocular evidence unreliable and acquitted the accused, reversing the trial court conviction.',
                'winner' => 'party_two',
                'winner_label' => 'Accused',
                'disposition' => 'reversed',
                'final_order' => 'Appeal allowed; conviction set aside; accused acquitted.',
                'laws_applied_json' => [
                    ['statute' => 'Pakistan Penal Code', 'section' => '302', 'description' => 'Qatl-i-amd'],
                ],
                'legal_issues_json' => [
                    'Reliability of ocular evidence',
                    'Effect of delayed FIR on prosecution case',
                ],
                'party_one_evidence_json' => [
                    ['point' => 'Ocular testimony of PW-1', 'paragraph_ref' => '¶2'],
                    ['point' => 'First Information Report', 'paragraph_ref' => '¶2'],
                ],
                'party_two_evidence_json' => [
                    ['point' => 'Record showing FIR registered after unexplained delay', 'paragraph_ref' => '¶3'],
                    ['point' => 'Contradictions in witness cross-examination', 'paragraph_ref' => '¶3'],
                ],
                'court_reasoning_json' => [
                    ['point' => 'Delayed FIR undermines the prosecution narrative', 'paragraph_ref' => '¶4'],
                    ['point' => 'Benefit of doubt must go to the accused', 'paragraph_ref' => '¶4'],
                ],
                'cited_cases_json' => [
                    ['title' => 'Tariq Pervez v. The State', 'citation' => '1995 SCMR 1345', 'context' => 'Benefit of doubt'],
                ],
                'keywords_json' => ['PPC 302', 'acquittal', 'ocular evidence'],
                'ai_status' => 'done',
                'ai_confidence' => 0.79,
                'ai_model' => 'demo-seed',
                'ai_extracted_at' => now(),
            ],
            [
                'jurisdiction' => 'PK-FED',
                'court' => 'Supreme Court of Pakistan',
                'external_id' => 'scp-demo-2019-cpla-100',
                'title' => 'Federation of Pakistan v. Civil Servant',
                'case_number' => 'CPLA No. 100 of 2019',
                'citation' => 'PLD 2019 SC 500',
                'neutral_citation' => null,
                'case_type' => 'civil',
                'party_one_name' => 'Federation of Pakistan',
                'party_one_role' => 'Plaintiff',
                'party_two_name' => 'Civil Servant',
                'party_two_role' => 'Defendant',
                'bench_json' => [],
                'author_judge' => null,
                'filing_date' => '2019-01-10',
                'decision_date' => '2019-06-01',
                'judgment_url' => null,
                'full_text' => 'Leave petition on service law seniority dispute. Full operative analysis not available in demo text.',
                'ai_summary' => null,
                'winner' => 'not_stated',
                'winner_label' => 'Not stated in judgment',
                'disposition' => 'not_stated',
                'final_order' => null,
                'laws_applied_json' => [],
                'legal_issues_json' => [],
                'party_one_evidence_json' => [],
                'party_two_evidence_json' => [],
                'court_reasoning_json' => [],
                'cited_cases_json' => [],
                'keywords_json' => ['service law'],
                'ai_status' => 'pending',
                'ai_confidence' => null,
                'ai_model' => null,
                'ai_extracted_at' => null,
            ],
            [
                'jurisdiction' => 'GB-EAW',
                'court' => 'Supreme Court of the United Kingdom',
                'external_id' => 'uksc-demo-2019-41',
                'title' => 'R (Miller) v The Prime Minister',
                'case_number' => 'UKSC 2019/0192',
                'citation' => '[2019] UKSC 41',
                'neutral_citation' => '[2019] UKSC 41',
                'case_type' => 'writ',
                'party_one_name' => 'Gina Miller',
                'party_one_role' => 'Petitioner',
                'party_two_name' => 'The Prime Minister',
                'party_two_role' => 'Respondent',
                'bench_json' => ['Lady Hale', 'Lord Reed'],
                'author_judge' => 'Lady Hale',
                'filing_date' => '2019-09-17',
                'decision_date' => '2019-09-24',
                'judgment_url' => 'https://www.supremecourt.uk/cases/uksc-2019-0192.html',
                'pdf_url' => 'https://www.supremecourt.uk/cases/docs/uksc-2019-0192-judgment.pdf',
                'is_reported' => true,
                'full_text' => 'Prorogation advice held unlawful as it frustrated Parliament.',
                'ai_summary' => 'The Supreme Court held that advice to prorogue Parliament was unlawful because it frustrated parliamentary sovereignty without reasonable justification.',
                'winner' => 'party_one',
                'winner_label' => 'Petitioner',
                'disposition' => 'allowed',
                'final_order' => 'Appeal allowed; prorogation declared unlawful and of no effect.',
                'laws_applied_json' => [
                    ['statute' => 'Constitutional common law', 'section' => 'n/a', 'description' => 'Parliamentary sovereignty / accountability'],
                ],
                'legal_issues_json' => [
                    'Justiciability of prorogation advice',
                    'Whether prorogation frustrated Parliament',
                ],
                'party_one_evidence_json' => [
                    ['point' => 'Length and timing of the prorogation period', 'paragraph_ref' => null],
                ],
                'party_two_evidence_json' => [
                    ['point' => 'Assertion of prerogative power to prorogue', 'paragraph_ref' => null],
                ],
                'court_reasoning_json' => [
                    ['point' => 'Extreme effects on Parliament rendered the advice unlawful', 'paragraph_ref' => null],
                ],
                'cited_cases_json' => [
                    ['title' => 'R (Miller) v Secretary of State for Exiting the EU', 'citation' => '[2017] UKSC 5', 'context' => 'Parliamentary sovereignty'],
                ],
                'keywords_json' => ['prorogation', 'UKSC'],
                'ai_status' => 'done',
                'ai_confidence' => 0.9,
                'ai_model' => 'demo-seed',
                'ai_extracted_at' => now(),
            ],
            [
                'jurisdiction' => 'GB-EAW',
                'court' => 'Supreme Court of the United Kingdom',
                'external_id' => 'uksc-demo-2021-5',
                'title' => 'Uber BV v Aslam',
                'case_number' => 'UKSC 2019/0029',
                'citation' => '[2021] UKSC 5',
                'neutral_citation' => '[2021] UKSC 5',
                'case_type' => 'appeal',
                'party_one_name' => 'Uber BV',
                'party_one_role' => 'Appellant',
                'party_two_name' => 'Yaseen Aslam & others',
                'party_two_role' => 'Respondent',
                'bench_json' => ['Lord Leggatt'],
                'author_judge' => 'Lord Leggatt',
                'filing_date' => '2020-07-21',
                'decision_date' => '2021-02-19',
                'judgment_url' => 'https://www.supremecourt.uk/cases/uksc-2019-0029.html',
                'pdf_url' => 'https://www.supremecourt.uk/cases/docs/uksc-2019-0029-judgment.pdf',
                'is_reported' => true,
                'full_text' => 'Uber drivers held to be workers.',
                'ai_summary' => 'The Supreme Court upheld that Uber drivers were workers for working-time and minimum-wage purposes, dismissing Uber’s appeal.',
                'winner' => 'party_two',
                'winner_label' => 'Respondent',
                'disposition' => 'upheld',
                'final_order' => 'Appeal dismissed; employment tribunal finding upheld.',
                'laws_applied_json' => [
                    ['statute' => 'National Minimum Wage Act 1998', 'section' => 'n/a', 'description' => 'Worker status'],
                    ['statute' => 'Working Time Regulations 1998', 'section' => 'n/a', 'description' => 'Worker protections'],
                ],
                'legal_issues_json' => ['Whether Uber drivers are workers'],
                'party_one_evidence_json' => [
                    ['point' => 'Contractual documents labelling drivers as independent contractors', 'paragraph_ref' => null],
                ],
                'party_two_evidence_json' => [
                    ['point' => 'Evidence of platform control over fares and acceptance', 'paragraph_ref' => null],
                ],
                'court_reasoning_json' => [
                    ['point' => 'Reality of subordination outweighed contractual labels', 'paragraph_ref' => null],
                ],
                'cited_cases_json' => [],
                'keywords_json' => ['employment', 'worker status'],
                'ai_status' => 'done',
                'ai_confidence' => 0.88,
                'ai_model' => 'demo-seed',
                'ai_extracted_at' => now(),
            ],
        ];

        foreach ($samples as $sample) {
            $payload = $sample;
            unset($payload['jurisdiction'], $payload['court']);
            CaseLawRecord::query()->updateOrCreate(
                [
                    'source' => 'demo',
                    'external_id' => $sample['external_id'],
                ],
                array_merge($payload, [
                    'jurisdiction_id' => $ids[$sample['jurisdiction']],
                    'court_id' => $courtIds[$sample['court']] ?? null,
                    'source' => 'demo',
                    'is_demo' => true,
                    'fetched_at' => now(),
                    'source_url' => $sample['judgment_url'] ?? null,
                ])
            );
        }
    }
}
