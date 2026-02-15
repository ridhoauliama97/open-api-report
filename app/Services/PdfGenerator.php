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
            static fn (string $key): bool => !in_array($key, $excluded, true)
        );

        return count($visibleColumns);
    }

    public function render(string $view, array $data = []): string
    {
        $html = view($view, $data)->render();
        $orientation = $this->columnCount($data) > 8 ? 'landscape' : 'portrait';

        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf-temp'),
            'format' => 'A4',
            'orientation' => $orientation,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}
