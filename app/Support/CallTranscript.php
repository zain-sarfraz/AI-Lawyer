<?php

declare(strict_types=1);

namespace App\Support;

final class CallTranscript
{
    /**
     * Normalize FineTuner/Synthflow or gateway transcripts into Caller:/Receptionist: lines.
     */
    public static function normalize(?string $raw): string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }

        $lines = preg_split("/\r\n|\n|\r/", $raw) ?: [];
        $out = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^(bot|ai|assistant|receptionist)\s*[:\-]\s*(.+)$/i', $line, $m)) {
                $out[] = 'Receptionist: '.trim($m[2]);

                continue;
            }

            if (preg_match('/^(human|caller|user|client)\s*[:\-]\s*(.+)$/i', $line, $m)) {
                $out[] = 'Caller: '.trim($m[2]);

                continue;
            }

            $out[] = $line;
        }

        return implode("\n", $out);
    }

    /**
     * @return list<array{role: string, text: string}>
     */
    public static function toLines(?string $raw): array
    {
        $normalized = self::normalize($raw);
        if ($normalized === '') {
            return [];
        }

        $lines = [];
        foreach (preg_split("/\r\n|\n|\r/", $normalized) ?: [] as $rawLine) {
            $rawLine = trim($rawLine);
            if ($rawLine === '') {
                continue;
            }

            if (preg_match('/^(AI|Assistant|Receptionist)\s*[:\-]\s*(.+)$/i', $rawLine, $m)) {
                $lines[] = ['role' => 'ai', 'text' => $m[2]];
            } elseif (preg_match('/^(Caller|User|Human|Client)\s*[:\-]\s*(.+)$/i', $rawLine, $m)) {
                $lines[] = ['role' => 'caller', 'text' => $m[2]];
            } else {
                $lines[] = ['role' => 'caller', 'text' => $rawLine];
            }
        }

        return $lines;
    }
}
