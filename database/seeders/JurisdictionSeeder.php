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
            ['code' => 'AU-FED', 'country_code' => 'AU', 'name' => 'Australia (Federal Court)', 'is_active' => true],
            ['code' => 'US-FED', 'country_code' => 'US', 'name' => 'United States (Federal)', 'is_active' => true],
        ];

        foreach ($this->usStates() as $code => $name) {
            $defs[] = [
                'code' => 'US-'.$code,
                'country_code' => 'US',
                'name' => $name.' (United States)',
                // Activate later via courts:sync-courtlistener / admin; only US-FED starts active.
                'is_active' => false,
            ];
        }

        $ids = [];
        foreach ($defs as $def) {
            $j = Jurisdiction::query()->updateOrCreate(['code' => $def['code']], $def);
            $ids[$def['code']] = $j->id;
        }

        $courts = [
            ['jurisdiction' => 'PK-FED', 'name' => 'Supreme Court of Pakistan', 'level' => 'supreme', 'external_court_id' => null],
            ['jurisdiction' => 'PK-FED', 'name' => 'Federal Shariat Court', 'level' => 'high', 'external_court_id' => null],
            ['jurisdiction' => 'PK-SD', 'name' => 'Sindh High Court', 'level' => 'high', 'external_court_id' => null],
            ['jurisdiction' => 'PK-PB', 'name' => 'Lahore High Court', 'level' => 'high', 'external_court_id' => null],
            ['jurisdiction' => 'PK-IS', 'name' => 'Islamabad High Court', 'level' => 'high', 'external_court_id' => null],
            ['jurisdiction' => 'GB-EAW', 'name' => 'Supreme Court of the United Kingdom', 'level' => 'supreme', 'external_court_id' => null],
            ['jurisdiction' => 'GB-EAW', 'name' => 'Court of Appeal', 'level' => 'appeal', 'external_court_id' => null],
            ['jurisdiction' => 'GB-EAW', 'name' => 'High Court', 'level' => 'high', 'external_court_id' => null],
            ['jurisdiction' => 'GB-EAW', 'name' => 'Upper Tribunal', 'level' => 'tribunal', 'external_court_id' => null],
            ['jurisdiction' => 'CA-FED', 'name' => 'Supreme Court of Canada', 'level' => 'supreme', 'external_court_id' => null],
            ['jurisdiction' => 'CA-FED', 'name' => 'Federal Court of Appeal', 'level' => 'appeal', 'external_court_id' => null],
            ['jurisdiction' => 'CA-FED', 'name' => 'Federal Court', 'level' => 'high', 'external_court_id' => null],
            ['jurisdiction' => 'CA-ON', 'name' => 'Court of Appeal for Ontario', 'level' => 'appeal', 'external_court_id' => null],
            ['jurisdiction' => 'CA-ON', 'name' => 'Ontario Superior Court of Justice', 'level' => 'high', 'external_court_id' => null],
            ['jurisdiction' => 'AU-FED', 'name' => 'Federal Court of Australia', 'level' => 'high', 'external_court_id' => null],
            ['jurisdiction' => 'US-FED', 'name' => 'Supreme Court of the United States', 'level' => 'supreme', 'external_court_id' => 'scotus'],
            ['jurisdiction' => 'US-FED', 'name' => 'U.S. Court of Appeals for the D.C. Circuit', 'level' => 'appeal', 'external_court_id' => 'cadc'],
            ['jurisdiction' => 'US-FED', 'name' => 'U.S. Court of Appeals for the Federal Circuit', 'level' => 'appeal', 'external_court_id' => 'cafc'],
        ];

        for ($i = 1; $i <= 11; $i++) {
            $courts[] = [
                'jurisdiction' => 'US-FED',
                'name' => 'U.S. Court of Appeals for the '.$this->ordinal($i).' Circuit',
                'level' => 'appeal',
                'external_court_id' => 'ca'.$i,
            ];
        }

        foreach ($courts as $court) {
            Court::query()->updateOrCreate(
                [
                    'jurisdiction_id' => $ids[$court['jurisdiction']],
                    'name' => $court['name'],
                ],
                [
                    'level' => $court['level'],
                    'external_court_id' => $court['external_court_id'] ?? null,
                ]
            );
        }

        CaseLawRecord::query()
            ->where(function ($query) {
                $query->where('is_demo', true)->orWhere('source', 'demo');
            })
            ->delete();
    }

    /** @return array<string, string> */
    private function usStates(): array
    {
        return [
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
            'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
            'DC' => 'District of Columbia', 'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii',
            'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
            'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine',
            'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota',
            'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska',
            'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico',
            'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
            'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island',
            'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas',
            'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington',
            'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
        ];
    }

    private function ordinal(int $n): string
    {
        return match ($n) {
            1 => 'First', 2 => 'Second', 3 => 'Third', 4 => 'Fourth', 5 => 'Fifth',
            6 => 'Sixth', 7 => 'Seventh', 8 => 'Eighth', 9 => 'Ninth', 10 => 'Tenth',
            11 => 'Eleventh',
            default => (string) $n,
        };
    }
}