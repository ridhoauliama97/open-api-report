<?php

namespace App\Services\Ascends\Shared\Hrm;

use App\Services\XmlDataSourceService;
use Carbon\Carbon;

class DiagramKaryawanPerMasaKerjaReportService
{
    private const TITLE = 'Laporan Diagram Karyawan Per Masa Kerja';

    public const CHART_COLORS = [
        [52, 73, 94],
        [231, 76, 60],
        [241, 196, 15],
        [46, 204, 113],
        [155, 89, 182],
        [230, 126, 34],
        [149, 165, 166],
        [52, 152, 219],
        [214, 97, 143],
        [244, 208, 63],
        [88, 214, 141],
        [93, 173, 226],
    ];

    private const PERIOD_LABELS = [
        1 => '0 - 6 Bulan',
        2 => '6 - 12 Bulan',
        3 => '1 - 2 Tahun',
        4 => '2 - 3 Tahun',
        5 => '3 Tahun Lebih',
    ];

    public function __construct(
        private readonly XmlDataSourceService $xmlDataSourceService,
    ) {}

    public function buildReportData(): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReport('RU', 'hrm', 'diagram_karyawan_per_masa_kerja');

        return $this->shapeReportData($reportData, 'storage/app/xml_sources/RU/hrm/Diagram/AnlReports.HRM.EmployeeList.xml');
    }

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReportFromXmlContents(
            'RU',
            'hrm',
            'diagram_karyawan_per_masa_kerja',
            $xmlContents,
            $sourceLabel
        );

        return $this->shapeReportData($reportData, $sourceLabel, $filters);
    }

    private function shapeReportData(array $reportData, string $sourceLabel, array $filters = []): array
    {
        $rawRows = $reportData['rows'] ?? [];

        $refDate = Carbon::now()->startOfDay();
        $periods = [
            1 => ['label' => self::PERIOD_LABELS[1], 'count' => 0],
            2 => ['label' => self::PERIOD_LABELS[2], 'count' => 0],
            3 => ['label' => self::PERIOD_LABELS[3], 'count' => 0],
            4 => ['label' => self::PERIOD_LABELS[4], 'count' => 0],
            5 => ['label' => self::PERIOD_LABELS[5], 'count' => 0],
        ];

        foreach ($rawRows as $row) {
            $empCode = trim((string) ($row['Kode Karyawan'] ?? ''));
            if (str_contains($empCode, 'SPECIAL')) {
                continue;
            }

            $joinStr = trim((string) ($row['Tanggal Masuk'] ?? ''));
            if ($joinStr === '') {
                continue;
            }

            $joinDate = Carbon::parse($joinStr);
            $diffDays = (int) $joinDate->diffInDays($refDate);

            if ($diffDays < 180) {
                $urutan = 1;
            } elseif ($diffDays <= 360) {
                $urutan = 2;
            } elseif ($diffDays <= 720) {
                $urutan = 3;
            } elseif ($diffDays <= 1080) {
                $urutan = 4;
            } else {
                $urutan = 5;
            }

            $periods[$urutan]['count']++;
        }

        ksort($periods);

        $total = array_sum(array_column($periods, 'count'));
        $departments = [];
        $chartData = [];

        foreach ($periods as $p) {
            $percent = $total > 0 ? round(($p['count'] / $total) * 100, 1) : 0;
            $departments[] = [
                'name' => $p['label'],
                'count' => $p['count'],
                'percent' => $percent,
            ];
            $chartData[] = ['name' => $p['label'], 'count' => $p['count'], 'percent' => $percent];
        }

        $pieChartBase64 = $total > 0 ? $this->generatePieChart($chartData) : '';

        $now = Carbon::now()->locale('id');
        $perDateFilter = $filters['PerDate'] ?? '';
        $perDateValue = $perDateFilter !== ''
            ? Carbon::parse($perDateFilter)->toDateString()
            : $now->toDateString();

        $headers = ['Masa Kerja', 'Jumlah', '%'];
        $rows = array_map(static fn (array $d): array => [
            'Masa Kerja' => $d['name'],
            'Jumlah' => $d['count'],
            '%' => number_format($d['percent'], 1, '.', '').'%',
        ], $departments);

        return [
            'title' => $reportData['label'] ?? self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => self::resolvePrintedBy($rawRows),
            'printed_at' => $now->translatedFormat('d F Y H:i'),
            'per_date' => $perDateValue,
            'headers' => $headers,
            'rows' => $rows,
            'departments' => $departments,
            'total' => $total,
            'pie_chart_base64' => $pieChartBase64,
        ];
    }

    private function generatePieChart(array $data): string
    {
        $width = 400;
        $height = 400;
        $cx = 200;
        $cy = 200;
        $radius = 150;

        $image = imagecreatetruecolor($width, $height);
        imagesavealpha($image, true);
        $bgColor = imagecolorallocatealpha($image, 255, 255, 255, 127);
        imagefill($image, 0, 0, $bgColor);

        $allocatedColors = [];
        foreach (self::CHART_COLORS as $rgb) {
            $allocatedColors[] = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
        }

        $total = (int) array_sum(array_column($data, 'count'));
        $startAngle = 0;
        $boundaryAngles = [0];

        foreach ($data as $i => $item) {
            $sweep = ($item['count'] / $total) * 360;
            $endAngle = $startAngle + $sweep;
            if ($endAngle > 359.99) {
                $endAngle = 360;
            }

            $colorIndex = $i % count($allocatedColors);
            imagefilledarc($image, $cx, $cy, $radius * 2, $radius * 2, (int) $startAngle, (int) $endAngle, $allocatedColors[$colorIndex], IMG_ARC_PIE);

            $startAngle = $endAngle;
            $boundaryAngles[] = $startAngle;
        }

        foreach ($boundaryAngles as $angle) {
            if ($angle > 0 && $angle < 360) {
                $rad = deg2rad($angle);
                $x = $cx + (int) ($radius * cos($rad));
                $y = $cy + (int) ($radius * sin($rad));
                imageline($image, $cx, $cy, $x, $y, imagecolorallocate($image, 255, 255, 255));
            }
        }

        imagearc($image, $cx, $cy, $radius * 2, $radius * 2, 0, 360, imagecolorallocate($image, 255, 255, 255));

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($imageData);
    }

    private static function resolvePrintedBy(array $rows): string
    {
        foreach (['Nama User', 'User Name', 'Printed By', 'Created By'] as $field) {
            foreach ($rows as $row) {
                $value = trim((string) ($row[$field] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }
}
