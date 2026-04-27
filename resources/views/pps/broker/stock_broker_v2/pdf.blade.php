<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 14mm 10mm 14mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.15;
            color: #000;
        }

        .report-title {
            text-align: center;
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            font-size: 10px;
            border: 1px solid #000;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        table.data-table th,
        table.data-table td {
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        table.data-table th:first-child,
        table.data-table td:first-child {
            border-left: 0;
        }

        table.data-table th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            border-bottom: 1px solid #000;
            background: #fff;
        }

        table.data-table tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .total-row td {
            font-weight: bold;
            font-size: 11px;
            border-top: #000 solid 1px;
        }

        .jenis-column {
            width: 200px;
        }

        @include('reports.partials.pdf-footer-table-style');
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $reportDateText = \Carbon\Carbon::parse($endDate ?? now()->toDateString())
            ->locale('id')
            ->translatedFormat('d-M-y');
        $isAllWarehouse = strtoupper(trim((string) ($warehouse ?? ''))) === 'ALL';
        $toFloat = static function ($value): ?float {
            if (is_numeric($value)) {
                return (float) $value;
            }
            if (!is_string($value)) {
                return null;
            }
            $normalized = trim(str_replace([' ', ','], ['', '.'], $value));

            return is_numeric($normalized) ? (float) $normalized : null;
        };
        $warehouseLabels = [];
        $pivotRows = [];
        $pivotTotals = [];

        if ($isAllWarehouse) {
            foreach ($rowsData as $row) {
                $warehouseName = trim((string) ($row['NamaWarehouse'] ?? ''));

                if ($warehouseName === '') {
                    continue;
                }

                $jenis = trim((string) ($row['Jenis'] ?? ''));
                if ($jenis === '') {
                    $jenis = '-';
                }

                $warehouseLabels[$warehouseName] = $warehouseName;

                if (!isset($pivotRows[$jenis])) {
                    $pivotRows[$jenis] = [];
                }

                if (!isset($pivotRows[$jenis][$warehouseName])) {
                    $pivotRows[$jenis][$warehouseName] = [
                        'JmlhSak' => 0.0,
                        'Berat' => 0.0,
                    ];
                }

                $pivotRows[$jenis][$warehouseName]['JmlhSak'] += $toFloat($row['JmlhSak'] ?? null) ?? 0.0;
                $pivotRows[$jenis][$warehouseName]['Berat'] += $toFloat($row['Berat'] ?? null) ?? 0.0;
            }

            ksort($warehouseLabels);
            ksort($pivotRows);

            foreach ($warehouseLabels as $warehouseName) {
                $pivotTotals[$warehouseName] = [
                    'JmlhSak' => 0.0,
                    'Berat' => 0.0,
                ];
            }

            foreach ($pivotRows as $jenis => $warehouseData) {
                foreach ($warehouseLabels as $warehouseName) {
                    $pivotRows[$jenis][$warehouseName] = $warehouseData[$warehouseName] ?? [
                        'JmlhSak' => 0.0,
                        'Berat' => 0.0,
                    ];

                    $pivotTotals[$warehouseName]['JmlhSak'] += $pivotRows[$jenis][$warehouseName]['JmlhSak'];
                    $pivotTotals[$warehouseName]['Berat'] += $pivotRows[$jenis][$warehouseName]['Berat'];
                }
            }
        } else {
            $selectedWarehouse = trim((string) ($warehouse ?? ''));
            $warehouseLabels = [$selectedWarehouse !== '' ? $selectedWarehouse : 'Gudang'];

            foreach ($rowsData as $row) {
                $jenis = trim((string) ($row['Jenis'] ?? ''));

                if ($jenis === '') {
                    $jenis = '-';
                }

                $warehouseName = $warehouseLabels[0];

                if (!isset($pivotRows[$jenis])) {
                    $pivotRows[$jenis] = [
                        $warehouseName => [
                            'JmlhSak' => 0.0,
                            'Berat' => 0.0,
                        ],
                    ];
                }

                $pivotRows[$jenis][$warehouseName]['JmlhSak'] += $toFloat($row['JmlhSak'] ?? null) ?? 0.0;
                $pivotRows[$jenis][$warehouseName]['Berat'] += $toFloat($row['Berat'] ?? null) ?? 0.0;
            }

            ksort($pivotRows);

            $pivotTotals[$warehouseLabels[0]] = [
                'JmlhSak' => 0.0,
                'Berat' => 0.0,
            ];

            foreach ($pivotRows as $jenis => $warehouseData) {
                $pivotTotals[$warehouseLabels[0]]['JmlhSak'] += $warehouseData[$warehouseLabels[0]]['JmlhSak'];
                $pivotTotals[$warehouseLabels[0]]['Berat'] += $warehouseData[$warehouseLabels[0]]['Berat'];
            }
        }
    @endphp

    <h1 class="report-title">Laporan Stock Broker</h1>
    <p class="report-subtitle">Per Tanggal : {{ $reportDateText }}</p>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" class="jenis-column"></th>
                @foreach ($warehouseLabels as $warehouseName)
                    <th colspan="2">{{ $warehouseName }}</th>
                @endforeach
                <th colspan="2">Total</th>
            </tr>
            <tr>
                @foreach ($warehouseLabels as $warehouseName)
                    <th>Jmlh Sak</th>
                    <th>Berat</th>
                @endforeach
                <th>Jmlh Sak</th>
                <th>Berat</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pivotRows as $jenis => $warehouseData)
                @php
                    $rowJmlhSak = 0.0;
                    $rowBerat = 0.0;
                @endphp
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td style="width: 30%;">{{ $jenis }}</td>
                    @foreach ($warehouseLabels as $warehouseName)
                        @php
                            $jmlhSak = $warehouseData[$warehouseName]['JmlhSak'] ?? 0.0;
                            $berat = $warehouseData[$warehouseName]['Berat'] ?? 0.0;
                            $rowJmlhSak += $jmlhSak;
                            $rowBerat += $berat;
                        @endphp
                        <td class="number">{{ number_format($jmlhSak, 2, '.', ',') }}</td>
                        <td class="number">{{ number_format($berat, 2, '.', ',') }}</td>
                    @endforeach
                    <td class="number" style="font-weight: bold;">{{ number_format($rowJmlhSak, 2, '.', ',') }}</td>
                    <td class="number" style="font-weight: bold;">{{ number_format($rowBerat, 2, '.', ',') }}</td>
                </tr>
            @empty
                <tr class="row-odd">
                    <td colspan="{{ count($warehouseLabels) * 2 + 3 }}" class="center"
                        style="font-weight: bold; font-size: 11px; font-style: italic;">
                        Tidak ada data.
                    </td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td class="center">Total</td>
                @php
                    $grandJmlhSak = 0.0;
                    $grandBerat = 0.0;
                @endphp
                @foreach ($warehouseLabels as $warehouseName)
                    @php
                        $totalJmlhSak = $pivotTotals[$warehouseName]['JmlhSak'] ?? 0.0;
                        $totalBerat = $pivotTotals[$warehouseName]['Berat'] ?? 0.0;
                        $grandJmlhSak += $totalJmlhSak;
                        $grandBerat += $totalBerat;
                    @endphp
                    <td class="number">{{ number_format($totalJmlhSak, 2, '.', ',') }}</td>
                    <td class="number">{{ number_format($totalBerat, 2, '.', ',') }}</td>
                @endforeach
                <td class="number">{{ number_format($grandJmlhSak, 2, '.', ',') }}</td>
                <td class="number">{{ number_format($grandBerat, 2, '.', ',') }}</td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
