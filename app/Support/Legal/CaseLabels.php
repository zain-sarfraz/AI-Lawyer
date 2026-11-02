<?php

namespace App\Support\Legal;

final class CaseLabels
{
    /**
     * @return array{party_one: string, party_two: string}
     */
    public static function for(?string $caseType, ?string $partyOneRole = null, ?string $partyTwoRole = null): array
    {
        $type = strtolower(trim((string) $caseType));

        $defaults = match ($type) {
            'criminal' => [
                'party_one' => 'Prosecution / State',
                'party_two' => 'Accused',
            ],
            'civil' => [
                'party_one' => 'Plaintiff',
                'party_two' => 'Defendant',
            ],
            'writ', 'constitutional' => [
                'party_one' => 'Petitioner',
                'party_two' => 'Respondent',
            ],
            'appeal' => [
                'party_one' => 'Appellant',
                'party_two' => 'Respondent',
            ],
            default => [
                'party_one' => 'Party One',
                'party_two' => 'Party Two',
            ],
        };

        if (in_array($type, ['family', 'tax', 'other', ''], true)) {
            if (filled($partyOneRole)) {
                $defaults['party_one'] = $partyOneRole;
            }
            if (filled($partyTwoRole)) {
                $defaults['party_two'] = $partyTwoRole;
            }
        }

        return $defaults;
    }
}
