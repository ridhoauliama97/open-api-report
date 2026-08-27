<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    <style>
        body {
            font-family: "Noto Serif", serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .report-companyTitle {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 0 0 4px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 0;
        }
        .report-subtitle {
            font-size: 12px;
            color: #636466;
            text-align: center;
            margin: 2px 0 20px;
        }
        .data-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #000;            padding: 1px 2px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .data-table th {
            background-color: #eef2f8;
            font-weight: bold;
        }
        .section-header td {
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .sub-section-header td {
            font-weight: bold;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .item-row td {
            padding-left: 4px;
        }
        .row-odd td {
            background-color: #c9d1df;
        }
        .row-even td {
            background-color: #eef2f8;
        }
        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .empty-row td {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background-color: #c9d1df;
        }
        .number {
            text-align: right;
        }
        .nowrap {
            white-space: nowrap;
        }
        .number-negative {
            color: #9c111d;
        }
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

    </body>

</html>
