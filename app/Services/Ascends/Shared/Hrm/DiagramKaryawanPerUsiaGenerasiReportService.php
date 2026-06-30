<?php

namespace App\Services\Ascends\Shared\Hrm;

use App\Services\XmlDataSourceService;
use Carbon\Carbon;

class DiagramKaryawanPerUsiaGenerasiReportService
{
    private const TITLE = 'Laporan Diagram Karyawan Per Usia Generasi';

    private const GENERATION_RANGES = [
        ['label' => 'Generasi GI', 'min' => 1901, 'max' => 1927],
        ['label' => 'Generasi Pendiam', 'min' => 1928, 'max' => 1945],
        ['label' => 'Generasi Baby Boomer', 'min' => 1946, 'max' => 1964],
        ['label' => 'Generasi X', 'min' => 1965, 'max' => 1980],
        ['label' => 'Generasi Milenial', 'min' => 1981, 'max' => 1996],
        ['label' => 'Generasi Z', 'min' => 1997, 'max' => 2012],
        ['label' => 'Generasi Alpha', 'min' => 2010, 'max' => 2024],
    ];

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

    public function __construct(
        private readonly XmlDataSourceService $xmlDataSourceService,
    ) {}

    public function buildReportData(): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReport('RU', 'hrm', 'diagram_karyawan_per_usia_generasi');

        return $this->shapeReportData($reportData, 'storage/app/xml_sources/RU/hrm/Diagram/AnlReports.HRM.EmployeeList.xml');
    }

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReportFromXmlContents(
            'RU',
            'hrm',
            'diagram_karyawan_per_usia_generasi',
            $xmlContents,
            $sourceLabel
        );

        return $this->shapeReportData($reportData, $sourceLabel, $filters);
    }

    private function shapeReportData(array $reportData, string $sourceLabel, array $filters = []): array
    {
        $rawRows = $reportData['rows'] ?? [];

        $genCounts = [];
        foreach (self::GENERATION_RANGES as $range) {
            $genCounts[$range['label']] = ['name' => $range['label'], 'count' => 0];
        }

        foreach ($rawRows as $row) {
            $empCode = trim((string) ($row['Kode Karyawan'] ?? ''));
            if (str_contains($empCode, 'SPECIAL')) {
                continue;
            }

            $birthYear = (int) ($row['Tahun Lahir'] ?? 0);
            if ($birthYear <= 0) {
                continue;
            }

            $matched = false;
            foreach (self::GENERATION_RANGES as $range) {
                if ($birthYear >= $range['min'] && $birthYear <= $range['max']) {
                    $genCounts[$range['label']]['count']++;
                    $matched = true;
                    break;
                }
            }
        }

        $genCounts = array_filter($genCounts, static fn (array $item): bool => $item['count'] > 0);

        $total = array_sum(array_column($genCounts, 'count'));
        $departments = [];
        $chartData = [];

        foreach ($genCounts as $item) {
            $percent = $total > 0 ? round(($item['count'] / $total) * 100, 1) : 0;
            $departments[] = [
                'name' => $item['name'],
                'count' => $item['count'],
                'percent' => $percent,
            ];
            $chartData[] = ['name' => $item['name'], 'count' => $item['count'], 'percent' => $percent];
        }

        $tableRows = $departments;
        usort($tableRows, static fn (array $a, array $b): int => $b['percent'] <=> $a['percent']);

        $pieChartBase64 = $total > 0 ? $this->generatePieChart($chartData) : '';

        $now = Carbon::now()->locale('id');
        $perDateFilter = $filters['PerDate'] ?? '';
        $perDateValue = $perDateFilter !== ''
            ? Carbon::parse($perDateFilter)->toDateString()
            : $now->toDateString();

        $headers = ['Generasi', 'Jumlah', '%'];
        $rows = array_map(static fn (array $d): array => [
            'Generasi' => $d['name'],
            'Jumlah' => $d['count'],
            '%' => number_format($d['percent'], 1, '.', '').'%',
        ], $tableRows);

        return [
            'title' => $reportData['label'] ?? self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => self::resolvePrintedBy($rawRows),
            'printed_at' => $now->translatedFormat('d F Y H:i'),
            'per_date' => $perDateValue,
            'headers' => $headers,
            'rows' => $rows,
            'departments' => $departments,
            'tableRows' => $tableRows,
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
