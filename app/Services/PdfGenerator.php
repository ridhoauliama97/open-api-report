<?php

namespace App\Services;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class PdfGenerator
{
    /**
     * @param array<string, mixed> $data
     *
     */

    private function columnCount(array $data): int
    {
        if (empty($data['rows'])) {
            return 0;
        }

        $row = $data['rows'][0];
        $count = 3; // No, Jenis, Awal
        $count += count($row['masuk'] ?? []);
        $count += count($row['keluar'] ?? []);
        $count += 1; // Akhir
        return $count;
    }
    public function render(string $view, array $data = []): string
    {
        $html = view($view, $data)->render();
        $orientation = $this->columnCount($data) > 8 ? 'landscape' : 'portrait';
        $mpdf = new Mpdf([
            'tempDir' => storage_path('app/mpdf-temp'),
            'format' => 'A4',
            $orientation,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}
