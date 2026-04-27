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
        $labelFields = ['Jenis', 'NoBroker'];
        $measureDefinitions = [
            'JmlhSak' => ['JmlhSak'],
            'Berat' => ['Berat', 'Weight'],
        ];
        $activeMeasures = [];

        foreach ($rowsData as $row) {
            foreach ($measureDefinitions as $measureName => $candidates) {
                foreach ($candidates as $candidate) {
                    if (array_key_exists($candidate, $row) && $toFloat($row[$candidate] ?? null) !== null) {
                        $activeMeasures[$measureName] = $candidate;
                        break;
                    }
                }
            }
        }

        if ($activeMeasures === []) {
            $activeMeasures = ['Berat' => 'Berat'];
        }

        $warehouseLabels = [];
        $pivotRows = [];
        $pivotTotals = [];

        foreach ($rowsData as $row) {
            $warehouseName = trim((string) ($row['NamaWarehouse'] ?? ($warehouse ?? '')));

            if ($warehouseName === '') {
                continue;
            }

            $label = '-';
            foreach ($labelFields as $field) {
                $value = trim((string) ($row[$field] ?? ''));
                if ($value !== '') {
                    $label = $value;
                    break;
                }
            }

            $warehouseLabels[$warehouseName] = $warehouseName;
            $pivotRows[$label] ??= [];
            $pivotRows[$label][$warehouseName] ??= array_fill_keys(array_keys($activeMeasures), 0.0);

            foreach ($activeMeasures as $measureName => $fieldName) {
                $pivotRows[$label][$warehouseName][$measureName] += $toFloat($row[$fieldName] ?? null) ?? 0.0;
            }
        }

        if (!$isAllWarehouse) {
            $selectedWarehouse = trim((string) ($warehouse ?? ''));
            $warehouseLabels = [$selectedWarehouse !== '' ? $selectedWarehouse : 'Gudang'];

            foreach ($pivotRows as $label => $warehouseData) {
                $currentValues = array_fill_keys(array_keys($activeMeasures), 0.0);

                foreach ($warehouseData as $measureValues) {
                    foreach (array_keys($activeMeasures) as $measureName) {
                        $currentValues[$measureName] += $measureValues[$measureName] ?? 0.0;
                    }
                }

                $pivotRows[$label] = [$warehouseLabels[0] => $currentValues];
            }
        }

        ksort($warehouseLabels);
        ksort($pivotRows);

        foreach ($warehouseLabels as $warehouseName) {
            $pivotTotals[$warehouseName] = array_fill_keys(array_keys($activeMeasures), 0.0);
        }

        foreach ($pivotRows as $label => $warehouseData) {
            foreach ($warehouseLabels as $warehouseName) {
                $pivotRows[$label][$warehouseName] =
                    $warehouseData[$warehouseName] ?? array_fill_keys(array_keys($activeMeasures), 0.0);

                foreach (array_keys($activeMeasures) as $measureName) {
                    $pivotTotals[$warehouseName][$measureName] +=
                        $pivotRows[$label][$warehouseName][$measureName] ?? 0.0;
                }
            }
        }
    @endphp

    <h1 class="report-title">Laporan Stock Broker</h1>
    <p class="report-subtitle">Per Tanggal : {{ $reportDateText }}</p>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 35%;"></th>
                @foreach ($warehouseLabels as $warehouseName)
                    <th colspan="{{ count($activeMeasures) }}">{{ strtoupper(trim((string) $warehouseName)) === 'ALL' ? '' : $warehouseName }}</th>
                @endforeach
                <th colspan="{{ count($activeMeasures) }}">Total</th>
            </tr>
            <tr>
                @foreach ($warehouseLabels as $warehouseName)
                    @foreach (array_keys($activeMeasures) as $measureName)
                        <th>{{ $measureName }}</th>
                    @endforeach
                @endforeach
                @foreach (array_keys($activeMeasures) as $measureName)
                    <th>{{ $measureName }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($pivotRows as $label => $warehouseData)
                @php $rowTotals = array_fill_keys(array_keys($activeMeasures), 0.0); @endphp
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td>{{ $label }}</td>
                    @foreach ($warehouseLabels as $warehouseName)
                        @foreach (array_keys($activeMeasures) as $measureName)
                            @php
                                $value = $warehouseData[$warehouseName][$measureName] ?? 0.0;
                                $rowTotals[$measureName] += $value;
                            @endphp
                            <td class="number">{{ number_format($value, 2, '.', ',') }}</td>
                        @endforeach
                    @endforeach
                    @foreach (array_keys($activeMeasures) as $measureName)
                        <td class="number" style="font-weight: bold;">{{ number_format($rowTotals[$measureName] ?? 0, 2, '.', ',') }}</td>
                    @endforeach
                </tr>
            @empty
                <tr class="row-odd">
                    <td colspan="{{ count($warehouseLabels) * count($activeMeasures) + count($activeMeasures) + 1 }}" class="center"
                        style="font-weight: bold; font-size: 11px; font-style: italic;">
                        Tidak ada data.
                    </td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td class="center">Total</td>
                @php $grandTotals = array_fill_keys(array_keys($activeMeasures), 0.0); @endphp
                @foreach ($warehouseLabels as $warehouseName)
                    @foreach (array_keys($activeMeasures) as $measureName)
                        @php
                            $totalValue = $pivotTotals[$warehouseName][$measureName] ?? 0.0;
                            $grandTotals[$measureName] += $totalValue;
                        @endphp
                        <td class="number">{{ number_format($totalValue, 2, '.', ',') }}</td>
                    @endforeach
                @endforeach
                @foreach (array_keys($activeMeasures) as $measureName)
                    <td class="number">{{ number_format($grandTotals[$measureName] ?? 0, 2, '.', ',') }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
