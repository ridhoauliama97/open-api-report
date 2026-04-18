<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 18mm 10mm 18mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.2;
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

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            table-layout: auto;
            page-break-inside: auto;
            margin-bottom: 4px;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        td.number {
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

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            font-size: 8px;
            font-style: italic;
            text-align: right;
        }

        .report-table {
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
        }

        .report-table thead {
            display: table-header-group;
        }

        .report-table .headers-row th {
            border-top: 0;
            border-bottom: 1px solid #000;
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: 0;
            border-bottom: 0;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
        }

        .report-table tbody tr.total-row td {
            border-bottom: 1px solid #000;
        }
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $generatedByName = $generatedBy->name ?? 'sistem';
        $columns = array_keys($rowsData[0] ?? []);
        $columnLabels = [
            'NoLabel' => 'No Label',
            'NoPallet' => 'Pallet',
            'JlhSak' => 'Sak',
            'NamaWarehouse' => 'Warehouse',
        ];
        $zeroDecimalColumns = ['NoPallet', 'JlhSak'];
        $qtyColumnIndex = array_search('Qty', $columns, true);
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
        $isNumericColumn = static function (string $column, array $rows) use ($toFloat): bool {
            foreach ($rows as $row) {
                $value = $toFloat($row[$column] ?? null);
                if ($value !== null) {
                    return true;
                }
            }
            return false;
        };
        $groupedRows = [];
        foreach ($rowsData as $row) {
            $groupKey = (string) ($row['Ket'] ?? '');
            if (!array_key_exists($groupKey, $groupedRows)) {
                $groupedRows[$groupKey] = [
                    'ket' => $groupKey,
                    'rows' => [],
                    'qty_total' => 0,
                ];
            }
            $groupedRows[$groupKey]['rows'][] = $row;
            $groupedRows[$groupKey]['qty_total'] += $toFloat($row['Qty'] ?? null) ?? 0;
        }
    @endphp

    <h1 class="report-title">Laporan Semua Label</h1>
    <p class="report-subtitle">Tanggal cetak : {{ $generatedAt->copy()->format('d-M-y H:i') }}</p>

    @forelse ($groupedRows as $group)
        @php
            $rowNumber = 1;
        @endphp
        <p style="margin: 0 0 6px 0; font-size: 12px; font-weight: bold;">
            {{ $group['ket'] !== '' ? $group['ket'] : '-' }}
        </p>
        <table class="report-table">
            <thead>
                <tr class="headers-row">
                    <th style="width: 34px;">No</th>
                    @foreach ($columns as $column)
                        <th>{{ $columnLabels[$column] ?? $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($group['rows'] as $row)
                    <tr class="data-row {{ $rowNumber % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="data-cell" style="text-align:center;">{{ $rowNumber }}</td>
                        @foreach ($columns as $column)
                            @php
                                $rawValue = $row[$column] ?? '';
                                $numericValue = $toFloat($rawValue);
                            @endphp
                            @if ($numericValue !== null && $isNumericColumn($column, $rowsData))
                                <td class="data-cell number">
                                    {{ number_format($numericValue, in_array($column, $zeroDecimalColumns, true) ? 0 : 2, '.', ',') }}
                                </td>
                            @else
                                <td class="data-cell">{{ (string) $rawValue }}</td>
                            @endif
                        @endforeach
                    </tr>
                    @php $rowNumber++; @endphp
                @endforeach
                <tr class="total-row">
                    @php
                        $totalLabelColspan = $qtyColumnIndex !== false ? $qtyColumnIndex + 1 : count($columns) + 1;
                    @endphp
                    <td colspan="{{ $totalLabelColspan }}"
                        style="text-align:right; font-weight:bold; padding-right:8px;">
                        Total Qty
                    </td>
                    @if ($qtyColumnIndex !== false)
                        <td class="number" style="font-weight:bold;">
                            {{ number_format($group['qty_total'], 2, '.', ',') }}
                        </td>
                        @for ($columnIndex = $qtyColumnIndex + 1; $columnIndex < count($columns); $columnIndex++)
                            <td style="font-weight:bold;">&nbsp;</td>
                        @endfor
                    @endif
                </tr>
            </tbody>
        </table>
    @empty
        <table class="report-table">
            <tbody>
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" style="text-align:center;">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh {{ $generatedByName }} pada
                {{ $generatedAt->copy()->format('d-M-y H:i') }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
</body>

</html>
