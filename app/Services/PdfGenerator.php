<?php

namespace App\Services;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class PdfGenerator
{
    /**
     * @param array<string, mixed> $data
     */
    private function columnCount(array $data): int
    {
        if (isset($data['pdf_column_count']) && is_numeric($data['pdf_column_count'])) {
            return max(0, (int) $data['pdf_column_count']);
        }

        $rows = $data['rows'] ?? [];

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
        $orientation = $this->columnCount($data) >= 10 ? 'landscape' : 'portrait';

        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf-temp'),
            'format' => 'A4',
            'orientation' => $orientation,
        ]);

        // Increase PCRE limits for very large HTML reports before handing
        // everything to mPDF in a single pass (keeps named footer parsing intact).
        @ini_set('pcre.backtrack_limit', '10000000');
        @ini_set('pcre.recursion_limit', '1000000');

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}
