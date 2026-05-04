<?php

declare(strict_types=1);

use App\Services\PdfGenerator;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$inputPath = $argv[1] ?? 'D:\XML Ascends\AnlReports.Inventory.AdjustmentByItem.xml';
$outputPath = $argv[2] ?? __DIR__ . '\resources\views\ascends\ascend_ru\analysis_report\AnlReports.Inventory.AdjustmentByItem.pdf';

if (!is_file($inputPath)) {
    fwrite(STDERR, "XML file not found: {$inputPath}" . PHP_EOL);
    exit(1);
}

$xml = simplexml_load_file($inputPath);

if ($xml === false) {
    fwrite(STDERR, "Failed to parse XML file: {$inputPath}" . PHP_EOL);
    exit(1);
}

$rows = [];
$totalQuantityDb = 0.0;
$totalQuantityCr = 0.0;
$totalAdjustedValue = 0.0;

foreach ($xml->CB as $node) {
    $row = [
        'adjustment_type' => trim((string) ($node->Adjustment_x0020_Type ?? '')),
        'memo_number' => trim((string) ($node->Memo_x0020_Number ?? '')),
        'adjustment_date' => trim((string) ($node->Adjustment_x0020_Date ?? '')),
        'approved_date' => trim((string) ($node->Approved_x0020_Date_x002F_Time ?? '')),
        'created_by' => trim((string) ($node->Created_x0020_By ?? '')),
        'approved_by' => trim((string) ($node->Approved_x0020_By ?? '')),
        'status' => trim((string) ($node->Status ?? '')),
        'warehouse_code' => trim((string) ($node->Warehouse_x0020_Code ?? '')),
        'warehouse_name' => trim((string) ($node->Warehouse_x0020_Name ?? '')),
        'item_family_name' => trim((string) ($node->Item_x0020_Family_x0020_Name ?? '')),
        'item_category' => trim((string) ($node->Item_x0020_Category ?? '')),
        'item_code' => trim((string) ($node->Item_x0020_Code ?? '')),
        'item_name' => trim((string) ($node->Item_x0020_Name ?? '')),
        'uom' => trim((string) ($node->UOM ?? '')),
        'quantity' => (float) ($node->Quantity ?? 0),
        'quantity_db' => (float) ($node->Quantity_x0020__x0028_DB_x0029_ ?? 0),
        'quantity_cr' => (float) ($node->Quantity_x0020__x0028_CR_x0029_ ?? 0),
        'adjusted_value' => (float) ($node->Adjusted_x0020_Value ?? 0),
        'adjusted_value_per_unit' => (float) ($node->Adjusted_x0020_Value_x002F_Unit ?? 0),
        'memo_remarks' => trim((string) ($node->Memo_x0020_Remarks ?? '')),
    ];

    $rows[] = $row;
    $totalQuantityDb += $row['quantity_db'];
    $totalQuantityCr += $row['quantity_cr'];
    $totalAdjustedValue += $row['adjusted_value'];
}

usort(
    $rows,
    static function (array $left, array $right): int {
        return [$left['adjustment_date'], $left['memo_number'], $left['item_code']]
            <=> [$right['adjustment_date'], $right['memo_number'], $right['item_code']];
    }
);

$warehouseSummary = [];
foreach ($rows as $row) {
    $key = $row['warehouse_code'] !== '' ? $row['warehouse_code'] : '-';

    if (!isset($warehouseSummary[$key])) {
        $warehouseSummary[$key] = [
            'warehouse_code' => $row['warehouse_code'],
            'warehouse_name' => $row['warehouse_name'],
            'count' => 0,
        ];
    }

    $warehouseSummary[$key]['count']++;
}

$reportData = [
    'title' => 'Adjustment By Item',
    'source_file' => basename($inputPath),
    'generated_at' => now()->format('d-M-y'),
    'row_count' => count($rows),
    'total_quantity_db' => $totalQuantityDb,
    'total_quantity_cr' => $totalQuantityCr,
    'total_adjusted_value' => $totalAdjustedValue,
    'warehouse_summary' => array_values($warehouseSummary),
];

$outputDirectory = dirname($outputPath);
if (!is_dir($outputDirectory)) {
    mkdir($outputDirectory, 0777, true);
}

app(PdfGenerator::class)->renderToFile(
    'ascends.ascend_ru.analysis_report.adjustment-by-item-pdf',
    [
        'rows' => $rows,
        'reportData' => $reportData,
        'pdf_orientation' => 'landscape',
        'pdf_format' => 'A4',
        'pdf_default_font' => 'dejavusans',
        'pdf_simple_tables' => true,
        'pdf_disable_chunking' => true,
        'pdf_column_count' => 12,
    ],
    $outputPath
);

fwrite(STDOUT, "PDF generated: {$outputPath}" . PHP_EOL);
