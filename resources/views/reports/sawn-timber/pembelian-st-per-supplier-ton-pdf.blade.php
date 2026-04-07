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
            margin: 12mm 10mm 14mm 10mm;
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
            border: 1px solid #000;
            table-layout: fixed;
        }

        table.data-table.empty-state tbody td {
            background: #c9d1df !important;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        table.data-table th {
            text-align: center;
            font-weight: bold;
            background: #fff;
            font-size: 10px;
            white-space: nowrap;
            border-bottom: 1px solid #000;
        }

        table.data-table td {
            text-align: center;
        }

        .empty-state-row td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        table.data-table tbody td[colspan] {
            background: #c9d1df !important;
        }

        table.data-table tbody tr:not(.data-row):not(.totals-row) td {
            background: #c9d1df !important;
        }

        table.data-table tbody td[colspan="3"] {
            background: #c9d1df;
        }

        table.data-table tbody tr.data-row td.data-cell {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .supplier {
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }


        .totals-row td {
            border-top: 1px solid #000 !important;
            font-weight: bold;
            background: #fff;
            font-size: 11px;
        }

        .table-end-line td {
            border-top: 1px solid #000 !important;
            border-right: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: #fff !important;
        }

        .center {
            text-align: center;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $jenisColumns = is_array($data['jenis_columns'] ?? null) ? $data['jenis_columns'] : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $totals = is_array($data['totals'] ?? null) ? $data['totals'] : [];
        $totalsByJenis = is_array($totals['by_jenis'] ?? null) ? $totals['by_jenis'] : [];
        $grandTotal = (float) ($totals['grand_total'] ?? 0.0);

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse((string) ($startDate ?? ''))->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) ($endDate ?? ''))->locale('id')->translatedFormat('d-M-y');

        $fmtTon = static function (float $v): string {
            return number_format($v, 4, '.', '');
        };

        $fmtPct = static function (float $ton, float $colTotal): string {
            if ($ton <= 0.0 || $colTotal <= 0.0) {
                return '';
            }
            $pct = ($ton / $colTotal) * 100.0;
            $pctInt = (int) round($pct);
            return $pctInt === 0 ? '-%' : $pctInt . '%';
        };

        $fmtCell = static function (string $tonText, string $pctText): string {
            $tonText = trim($tonText);
            $pctText = trim($pctText);

            if ($tonText === '' && $pctText === '') {
                return '';
            }

            // Pad the left value so percentage appears visually on the right side of the cell (mPDF-safe).
            // Use monospace rendering via .cell-pre.
            $leftWidth = 16;
            $tonPadded = str_pad($tonText, $leftWidth, ' ', STR_PAD_RIGHT);

            $pctWrapped = $pctText !== '' ? '(' . $pctText . ')' : '';

            return $pctWrapped !== '' ? $tonPadded . $pctWrapped : $tonText;
        };

        $pivotColumns = array_values($jenisColumns);
        $pivotColumns[] = 'Total';

        $jenisCount = max(1, count($pivotColumns));
        $noWidth = 6.0;
        $supplierWidth = 24.0;
        $colWidth = (100.0 - $noWidth - $supplierWidth) / $jenisCount;
        // Make the last column absorb rounding so total stays 100%.
        $widths = [];
        for ($i = 0; $i < $jenisCount; $i++) {
            if ($i === $jenisCount - 1) {
                $used = $noWidth + $supplierWidth + $colWidth * ($jenisCount - 1);
                $widths[] = max(0.0, 100.0 - $used);
            } else {
                $widths[] = $colWidth;
            }
        }
    @endphp

    <h1 class="report-title">Laporan Pembelian ST Per Supplier (Ton)</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <table class="data-table{{ $rows === [] ? ' empty-state' : '' }}">
        <colgroup>
            <col style="width: {{ $noWidth }}%;">
            <col style="width: {{ $supplierWidth }}%;">
            @foreach ($pivotColumns as $i => $jenis)
                <col style="width: {{ number_format((float) ($widths[$i] ?? 0), 4, '.', '') }}%;">
            @endforeach
        </colgroup>
        <thead>
            <tr>
                <th>No</th>
                <th>Supplier</th>
                @foreach ($jenisColumns as $jenis)
                    <th>{{ $jenis }}</th>
                @endforeach
                <th>Total</th>
            </tr>
        </thead>
        @if ($rows !== [])
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="{{ 3 + count($jenisColumns) }}"></td>
                </tr>
            </tfoot>
        @endif
        <tbody @if ($rows === []) style="background: #c9d1df;" @endif>
            @php $rowIndex = 0; @endphp
            @forelse ($rows as $row)
                @php
                    $rowIndex++;
                    $supplier = (string) ($row['supplier'] ?? '-');
                    $values = is_array($row['values'] ?? null) ? $row['values'] : [];
                    $rowTotal = 0.0;
                @endphp
                <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center data-cell">{{ $rowIndex }}</td>
                    <td class="data-cell" style="text-align: left">{{ $supplier }}</td>
                    @foreach ($jenisColumns as $jenis)
                        @php
                            $ton = (float) ($values[$jenis] ?? 0.0);
                            $colTotal = (float) ($totalsByJenis[$jenis] ?? 0.0);
                            $tonText = $ton > 0.0 ? $fmtTon($ton) : '';
                            $pctText = $fmtPct($ton, $colTotal);
                            $rowTotal += $ton;
                        @endphp
                        <td class="data-cell">
                            @if ($tonText !== '' || $pctText !== '')
                                <span class="cell-pre">{{ $fmtCell($tonText, $pctText) }}</span>
                            @endif
                        </td>
                    @endforeach
                    @php
                        $rowTotalText = $rowTotal > 0.0 ? $fmtTon($rowTotal) : '';
                        $rowTotalPct = $fmtPct($rowTotal, $grandTotal);
                    @endphp
                    <td class="data-cell">
                        @if ($rowTotalText !== '' || $rowTotalPct !== '')
                            <span class="cell-pre">{{ $fmtCell($rowTotalText, $rowTotalPct) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 3 + count($jenisColumns) }}" class="center">Tidak ada data.</td>
                </tr>
            @endforelse

            @if ($rows !== [])
                <tr class=" totals-row">
                    <td class="data-cell" colspan="2" style="text-align: center">Total</td>
                    @foreach ($jenisColumns as $jenis)
                        @php
                            $colTotal = (float) ($totalsByJenis[$jenis] ?? 0.0);
                            $tonText = $colTotal > 0.0 ? $fmtTon($colTotal) : '';
                        @endphp
                        <td class="data-cell">
                            @if ($tonText !== '')
                                <span class="cell-pre">{{ $fmtCell($tonText, '100%') }}</span>
                            @endif
                        </td>
                    @endforeach
                    @php
                        $grandTotalText = $grandTotal > 0.0 ? $fmtTon($grandTotal) : '';
                    @endphp
                    <td class="data-cell">
                        @if ($grandTotalText !== '')
                            <span class="cell-pre">{{ $fmtCell($grandTotalText, '100%') }}</span>
                        @endif
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
