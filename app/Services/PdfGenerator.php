<?php

namespace App\Services;

use Mpdf\Mpdf;
use Mpdf\HTMLParserMode;
use Mpdf\Output\Destination;

class PdfGenerator
{
    /**
     * @param array<string, mixed> $data
     */
    private function resolveOrientation(array $data): string
    {
        $requested = strtolower((string) ($data['pdf_orientation'] ?? ''));
        if (in_array($requested, ['landscape', 'portrait'], true)) {
            return $requested;
        }

        return $this->columnCount($data) > 10 ? 'landscape' : 'portrait';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveFormat(array $data): string
    {
        $requested = strtoupper(trim((string) ($data['pdf_format'] ?? 'A4')));
        $allowed = ['A4', 'A3', 'A2', 'A1', 'A0', 'LETTER', 'LEGAL'];

        return in_array($requested, $allowed, true) ? $requested : 'A4';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function columnCount(array $data): int
    {
        if (isset($data['pdf_column_count']) && is_numeric($data['pdf_column_count'])) {
            return max(0, (int) $data['pdf_column_count']);
        }

        $rows = $data['rows'] ?? ($data['reportData']['rows'] ?? []);

        if (!is_array($rows) || empty($rows)) {
            return 0;
        }

        $firstRow = $rows[0] ?? null;

        if (!is_array($firstRow)) {
            $firstRow = (array) $firstRow;
        }

        if (empty($firstRow)) {
            return 0;
        }

        $excluded = ['created_at', 'updated_at'];
        $visibleColumns = array_filter(
            array_keys($firstRow),
            static fn(string $key): bool => !in_array($key, $excluded, true)
        );

        return count($visibleColumns);
    }

    /**
     * Execute render logic.
     */
    public function render(string $view, array $data = []): string
    {
        $html = view($view, $data)->render();
        $html = $this->stripExternalFontLinks($html);
        $html = $this->sanitizeUtf8($html);
        $orientation = $this->resolveOrientation($data);
        $format = $this->resolveFormat($data);

        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf-temp'),
            'format' => $format,
            'orientation' => $orientation,
            'simpleTables' => true,
            'packTableData' => true,
            'default_font' => 'dejavusans',
            'autoScriptToLang' => false,
            'autoLangToFont' => false,
        ]);

        if (isset($data['pdf_shrink_tables_to_fit']) && is_numeric($data['pdf_shrink_tables_to_fit'])) {
            $mpdf->shrink_tables_to_fit = (float) $data['pdf_shrink_tables_to_fit'];
        }

        if (!empty($data['pdf_disable_auto_page_break'])) {
            $mpdf->SetAutoPageBreak(false);
        }

        // Keep limits high, but still stream HTML in chunks to avoid
        // "The HTML code size is larger than pcre.backtrack_limit".
        @ini_set('pcre.backtrack_limit', '10000000');
        @ini_set('pcre.recursion_limit', '1000000');

        if (!empty($data['pdf_disable_chunking'])) {
            $mpdf->WriteHTML($html);
        } else {
            $this->writeHtmlInChunks($mpdf, $html);
        }

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    private function writeHtmlInChunks(Mpdf $mpdf, string $html): void
    {
        if (trim($html) === '') {
            return;
        }

        if (preg_match('/<style\b[^>]*>(.*?)<\/style>/is', $html, $styleMatch) === 1) {
            $css = trim((string) ($styleMatch[1] ?? ''));
            if ($css !== '') {
                $mpdf->WriteHTML($css, HTMLParserMode::HEADER_CSS);
            }

            $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html, 1) ?? $html;
        }

        // Keep named footer/header declarations intact and processed before body chunks.
        $footerDeclarations = [];
        if (preg_match_all('/<htmlpagefooter\b[^>]*>.*?<\/htmlpagefooter>/is', $html, $footerMatches) >= 1) {
            $footerDeclarations = array_merge($footerDeclarations, $footerMatches[0]);
            $html = preg_replace('/<htmlpagefooter\b[^>]*>.*?<\/htmlpagefooter>/is', '', $html) ?? $html;
        }
        if (preg_match_all('/<sethtmlpagefooter\b[^>]*\/?>/is', $html, $setFooterMatches) >= 1) {
            $footerDeclarations = array_merge($footerDeclarations, $setFooterMatches[0]);
            $html = preg_replace('/<sethtmlpagefooter\b[^>]*\/?>/is', '', $html) ?? $html;
        }
        foreach ($footerDeclarations as $declaration) {
            if (trim($declaration) !== '') {
                $mpdf->WriteHTML($declaration, HTMLParserMode::HTML_BODY);
            }
        }

        foreach ($this->splitHtmlChunks($html) as $chunk) {
            if (trim($chunk) === '') {
                continue;
            }
            $mpdf->WriteHTML($chunk, HTMLParserMode::HTML_BODY);
        }
    }

    /**
     * @return array<int, string>
     */
    private function splitHtmlChunks(string $html, int $maxLength = 500000): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $html) ?: [$html];
        $chunks = [];
        $buffer = '';

        foreach ($lines as $line) {
            $candidate = $buffer === '' ? $line : $buffer . PHP_EOL . $line;

            if (strlen($candidate) <= $maxLength) {
                $buffer = $candidate;
                continue;
            }

            if ($buffer !== '') {
                $chunks[] = $buffer;
                $buffer = '';
            }

            if (strlen($line) <= $maxLength) {
                $buffer = $line;
                continue;
            }

            $parts = $this->utf8ChunkSplit($line, $maxLength);
            $lastIndex = count($parts) - 1;
            foreach ($parts as $index => $part) {
                if ($index === $lastIndex) {
                    $buffer = $part;
                } else {
                    $chunks[] = $part;
                }
            }
        }

        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        return $chunks;
    }

    private function stripExternalFontLinks(string $html): string
    {
        // External Google Fonts fetch can slow down mPDF significantly on each render.
        $html = preg_replace('/<link\b[^>]*fonts\.googleapis\.com[^>]*>/i', '', $html) ?? $html;
        $html = preg_replace('/<link\b[^>]*fonts\.gstatic\.com[^>]*>/i', '', $html) ?? $html;
        $html = preg_replace('/<link\b[^>]*rel=["\']preconnect["\'][^>]*>/i', '', $html) ?? $html;

        return $html;
    }

    /**
     * Remove BOM and drop malformed byte sequences to prevent mPDF UTF-8 errors.
     */
    private function sanitizeUtf8(string $html): string
    {
        if (str_starts_with($html, "\xEF\xBB\xBF")) {
            $html = substr($html, 3);
        }

        $sanitized = @iconv('UTF-8', 'UTF-8//IGNORE', $html);

        return is_string($sanitized) ? $sanitized : $html;
    }

    /**
     * @return array<int, string>
     */
    private function utf8ChunkSplit(string $line, int $maxLength): array
    {
        if ($line === '') {
            return [''];
        }

        if (!function_exists('mb_strcut')) {
            return str_split($line, $maxLength);
        }

        $parts = [];
        $offset = 0;
        $lineLength = strlen($line);

        while ($offset < $lineLength) {
            $part = mb_strcut($line, $offset, $maxLength, 'UTF-8');
            if ($part === '') {
                break;
            }
            $parts[] = $part;
            $offset += strlen($part);
        }

        return $parts === [] ? [''] : $parts;
    }
}
