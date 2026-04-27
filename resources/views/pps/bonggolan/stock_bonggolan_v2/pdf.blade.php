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
        $jenisLabels = [];
        $pivotRows = [];
        $pivotTotals = [];

        if ($isAllWarehouse) {
            foreach ($rowsData as $row) {
                $warehouseName = trim((string) ($row['NamaWarehouse'] ?? ''));

                if ($warehouseName === '') {
                    continue;
                }

                $jenis = trim((string) ($row['NamaBonggolan'] ?? ($row['Jenis'] ?? '')));
                if ($jenis === '') {
                    $jenis = '-';
                }

                $jenisLabels[$warehouseName] = $warehouseName;

                if (!isset($pivotRows[$jenis])) {
                    $pivotRows[$jenis] = [];
                }

                if (!isset($pivotRows[$jenis][$warehouseName])) {
                    $pivotRows[$jenis][$warehouseName] = 0.0;
                }

                $pivotRows[$jenis][$warehouseName] += $toFloat($row['Berat'] ?? null) ?? 0.0;
            }

            ksort($jenisLabels);
            ksort($pivotRows);

            foreach ($jenisLabels as $warehouseName) {
                $pivotTotals[$warehouseName] = 0.0;
            }

            foreach ($pivotRows as $jenis => $warehouseData) {
                foreach ($jenisLabels as $warehouseName) {
                    $pivotRows[$jenis][$warehouseName] = $warehouseData[$warehouseName] ?? 0.0;
                    $pivotTotals[$warehouseName] += $pivotRows[$jenis][$warehouseName];
                }
            }
        } else {
            $selectedWarehouse = trim((string) ($warehouse ?? ''));
            $jenisLabels = [$selectedWarehouse !== '' ? $selectedWarehouse : 'Gudang'];

            foreach ($rowsData as $row) {
                $jenis = trim((string) ($row['NamaBonggolan'] ?? ($row['Jenis'] ?? '')));

                if ($jenis === '') {
                    $jenis = '-';
                }

                $warehouseName = $jenisLabels[0];

                if (!isset($pivotRows[$jenis])) {
                    $pivotRows[$jenis] = [
                        $warehouseName => 0.0,
                    ];
                }

                $pivotRows[$jenis][$warehouseName] += $toFloat($row['Berat'] ?? null) ?? 0.0;
            }

            ksort($pivotRows);

            $pivotTotals[$jenisLabels[0]] = 0.0;

            foreach ($pivotRows as $jenis => $warehouseData) {
                $pivotTotals[$jenisLabels[0]] += $warehouseData[$jenisLabels[0]] ?? 0.0;
            }
        }
    @endphp

    <h1 class="report-title">Laporan Stock Bonggolan</h1>
    <p class="report-subtitle">Per Tanggal : {{ $reportDateText }}</p>

    <table class="data-table">
        <thead>
            <tr>
                <th class="jenis-column">Jenis</th>
                @foreach ($jenisLabels as $warehouseName)
                    <th>{{ $warehouseName }}</th>
                @endforeach
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pivotRows as $jenis => $warehouseData)
                @php
                    $rowTotal = 0.0;
                @endphp
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td>{{ $jenis }}</td>
                    @foreach ($jenisLabels as $warehouseName)
                        @php
                            $berat = $warehouseData[$warehouseName] ?? 0.0;
                            $rowTotal += $berat;
                        @endphp
                        <td class="number">{{ number_format($berat, 2, '.', ',') }}</td>
                    @endforeach
                    <td class="number" style="font-weight: bold;">{{ number_format($rowTotal, 2, '.', ',') }}</td>
                </tr>
            @empty
                <tr class="row-odd">
                    <td colspan="{{ count($jenisLabels) + 2 }}" class="center"
                        style="font-weight: bold; font-size: 11px; font-style: italic;">
                        Tidak ada data.
                    </td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td class="center">Total</td>
                @php
                    $grandTotal = 0.0;
                @endphp
                @foreach ($jenisLabels as $warehouseName)
                    @php
                        $totalBerat = $pivotTotals[$warehouseName] ?? 0.0;
                        $grandTotal += $totalBerat;
                    @endphp
                    <td class="number">{{ number_format($totalBerat, 2, '.', ',') }}</td>
                @endforeach
                <td class="number">{{ number_format($grandTotal, 2, '.', ',') }}</td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
