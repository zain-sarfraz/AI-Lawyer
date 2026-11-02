<?php

namespace App\Domain\CaseLaw;

/**
 * Pull counsel lines from UK Find Case Law Akoma Ntoso / cover text.
 *
 * Example source line:
 * "Peter Caldwell KC and Waheed Chaudhrey (instructed by CPS Extradition Unit) for the Appellant"
 */
final class UkJudgmentCounselParser
{
    /**
     * @return list<array{name: string, side: string, instructed_by: ?string, role: string}>
     */
    public function parse(string $xmlOrText): array
    {
        $text = html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>'], "\n", $xmlOrText)), ENT_QUOTES | ENT_HTML5);
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;

        $out = [];
        $pattern = '/([A-Z][^()\n]{2,180}?)\s*\(\s*instructed by\s+([^)]+?)\s*\)\s+for the\s+([A-Za-z\/\- ]{3,40})/iu';

        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                foreach ($this->splitNames(trim($m[1])) as $name) {
                    $out[] = [
                        'name' => $name,
                        'side' => $this->normalizeSide(trim($m[3])),
                        'instructed_by' => trim($m[2]),
                        'role' => 'counsel',
                    ];
                }
            }
        }

        // Fallback: "X for the Appellant" without instructed-by (less reliable).
        if ($out === [] && preg_match_all(
            '/(?:^|\n)\s*([A-Z][A-Za-z .\'\-]{2,80}?(?:KC|QC|Jr\.?)?)\s+for the\s+([A-Za-z\/\- ]{3,40})/u',
            $text,
            $fallback,
            PREG_SET_ORDER
        )) {
            foreach ($fallback as $m) {
                $name = trim($m[1]);
                if (stripos($name, 'judgment') !== false || stripos($name, 'hearing') !== false) {
                    continue;
                }
                $out[] = [
                    'name' => $name,
                    'side' => $this->normalizeSide(trim($m[2])),
                    'instructed_by' => null,
                    'role' => 'counsel',
                ];
            }
        }

        return $this->unique($out);
    }

    /** @return list<string> */
    private function splitNames(string $blob): array
    {
        $blob = preg_replace('/\s+and\s+/i', '|', $blob) ?? $blob;
        $blob = str_replace(',', '|', $blob);
        $names = [];
        foreach (explode('|', $blob) as $part) {
            $part = trim($part, " \t\n\r\0\x0B;");
            $part = preg_replace('/\s*\(of\s+[^)]+\)\s*/i', ' ', $part) ?? $part;
            $part = trim(preg_replace('/\s+/', ' ', $part) ?? $part);
            if ($part !== '' && mb_strlen($part) > 2) {
                $names[] = $part;
            }
        }

        return $names;
    }

    private function normalizeSide(string $side): string
    {
        $side = strtolower(trim($side));
        $side = preg_replace('/\s+/', ' ', $side) ?? $side;

        return match (true) {
            str_contains($side, 'appellant') => 'Appellant',
            str_contains($side, 'respondent') => 'Respondent',
            str_contains($side, 'claimant') || str_contains($side, 'applicant') => 'Claimant',
            str_contains($side, 'defendant') => 'Defendant',
            str_contains($side, 'petitioner') => 'Petitioner',
            default => ucwords($side),
        };
    }

    /**
     * @param  list<array{name: string, side: string, instructed_by: ?string, role: string}>  $rows
     * @return list<array{name: string, side: string, instructed_by: ?string, role: string}>
     */
    private function unique(array $rows): array
    {
        $seen = [];
        $out = [];
        foreach ($rows as $row) {
            $key = mb_strtolower($row['name'].'|'.$row['side']);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $row;
        }

        return $out;
    }
}
