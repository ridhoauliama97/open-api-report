<?php

namespace App\Support;

final class ReportFilenameFormatter
{
    public static function fromLegacy(string $filename): string
    {
        $raw = trim($filename);
        $raw = preg_replace('/\.pdf$/i', '', $raw) ?? $raw;

        [$titleRaw, $subtitleRaw] = self::splitTitleAndSubtitle($raw);

        $title = self::normalizePart($titleRaw);
        $subtitle = self::normalizePart($subtitleRaw);

        if ($subtitle === '') {
            return $title . '.pdf';
        }

        return sprintf('%s - %s.pdf', $title, $subtitle);
    }

    private static function splitTitleAndSubtitle(string $value): array
    {
        $withStandardSeparator = preg_replace('/\s*[—–]\s*/u', ' - ', $value) ?? $value;

        if (str_contains($withStandardSeparator, ' - ')) {
            [$title, $subtitle] = explode(' - ', $withStandardSeparator, 2);

            return [$title, $subtitle];
        }

        if (preg_match('/^(.+?)[-_ ]+(\d{4}[-_]\d{2}[-_]\d{2}(?:[-_ ]+(?:sd|s-d|s\/d)[-_ ]+\d{4}[-_]\d{2}[-_]\d{2})?)$/i', $value, $matches) === 1) {
            return [$matches[1], $matches[2]];
        }

        if (preg_match('/^(.+?)[-_ ]+(Periode.+)$/i', $value, $matches) === 1) {
            return [$matches[1], $matches[2]];
        }

        return [$value, ''];
    }

    private static function normalizePart(string $value): string
    {
        $normalized = trim($value);
        $normalized = preg_replace('/\b(?:sd|s-d|s\/d)\b/i', 's/d', $normalized) ?? $normalized;
        $normalized = preg_replace('/[_]+/', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/-+/', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
        $normalized = trim($normalized, " \t\n\r\0\x0B-");

        return $normalized;
    }
}

