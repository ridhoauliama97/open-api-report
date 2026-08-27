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
            width: 100%;
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 1px 2px;
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
        $monthColumns = is_array($data['month_columns'] ?? null) ? $data['month_columns'] : [];
        $yearGroups = is_array($data['year_groups'] ?? null) ? $data['year_groups'] : [];
        $monthKeys = is_array($data['month_keys'] ?? null) ? $data['month_keys'] : array_column($monthColumns, 'key');
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $totals = is_array($data['totals'] ?? null) ? $data['totals'] : [];
        $totalsByMonth = is_array($totals['by_month'] ?? null) ? $totals['by_month'] : [];
        $grandTotal = (float) ($totals['grand_total'] ?? 0.0);

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse((string) ($startDate ?? ''))->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) ($endDate ?? ''))->locale('id')->translatedFormat('d-M-y');

        $fmtTon = static fn(float $v): string => number_format($v, 4, '.', '');

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
            if ($pctText === '') {
                return $tonText;
            }
            return $tonText . '  ' . $pctText;
        };

        $colCount = max(1, count($monthKeys));
        $noWidth = 5.0;
        $supplierWidth = 22.0;
        $totalWidth = 12.0;
        $colWidth = (100.0 - $noWidth - $supplierWidth - $totalWidth) / $colCount;
        $widths = [];
        for ($i = 0; $i < $colCount; $i++) {
            if ($i === $colCount - 1) {
                $used = $noWidth + $supplierWidth + $totalWidth + $colWidth * ($colCount - 1);
                $widths[] = max(0.0, 100.0 - $used);
            } else {
                $widths[] = $colWidth;
            }
        }

        $hasYears = $yearGroups !== [];
        $computedYearGroups = [];
        if (!$hasYears) {
            // Fallback: group by first 4 chars of month key.
            foreach ($monthKeys as $mk) {
                $y = (int) substr((string) $mk, 0, 4);
                if ($y <= 0) {
                    continue;
                }
                if (!isset($computedYearGroups[$y])) {
                    $computedYearGroups[$y] = ['year' => $y, 'months' => []];
                }
                $computedYearGroups[$y]['months'][] = $mk;
            }
            ksort($computedYearGroups);
            $yearGroups = array_values($computedYearGroups);
            $hasYears = $yearGroups !== [];
        }

        $monthLabelByKey = [];
        foreach ($monthColumns as $m) {
            if (is_array($m) && isset($m['key'])) {
                $monthLabelByKey[(string) $m['key']] = (string) ($m['label'] ?? $m['key']);
            }
        }
    @endphp

    <h1 class="report-title">Laporan Pembelian ST Timeline (Ton)</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <table class="data-table{{ $rows === [] ? ' empty-state' : '' }}">
        <colgroup>
            <col style="width: {{ $noWidth }}%;">
            <col style="width: {{ $supplierWidth }}%;">
            @if ($monthKeys !== [])
                @foreach ($monthKeys as $i => $mk)
                    <col style="width: {{ number_format((float) ($widths[$i] ?? 0), 4, '.', '') }}%;">
                @endforeach
            @endif
            <col style="width: {{ $totalWidth }}%;">
        </colgroup>
        <thead>
            @if ($monthKeys !== [])
                <tr>
                    <th rowspan="2">No</th>
                    <th rowspan="2">Supplier</th>
                    @if ($hasYears)
                        @foreach ($yearGroups as $g)
                            @php
                                $y = (int) ($g['year'] ?? 0);
                                $months = is_array($g['months'] ?? null) ? $g['months'] : [];
                                $span = count($months);
                            @endphp
                            @if ($span > 0)
                                <th colspan="{{ $span }}">{{ $y }}</th>
                            @endif
                        @endforeach
                    @else
                        <th colspan="{{ count($monthKeys) }}">&nbsp;</th>
                    @endif
                    <th rowspan="2">Total</th>
                </tr>
                <tr>
                    @foreach ($monthKeys as $mk)
                        <th>{{ $monthLabelByKey[$mk] ?? $mk }}</th>
                    @endforeach
                </tr>
            @else
                <tr>
                    <th>No</th>
                    <th>Supplier</th>
                    <th>Total</th>
                </tr>
            @endif
        </thead>
        <tbody @if ($rows === []) style="background: #c9d1df;" @endif>
            @php $rowIndex = 0; @endphp
            @forelse ($rows as $row)
                @php
                    $rowIndex++;
                    $supplier = (string) ($row['supplier'] ?? '-');
                    $values = is_array($row['values'] ?? null) ? $row['values'] : [];
                    $rowTotal = (float) ($row['total_ton'] ?? 0.0);
                @endphp
                <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="data-cell">{{ $rowIndex }}</td>
                    <td class="data-cell supplier" style="text-align: left">{{ $supplier }}</td>
                    @foreach ($monthKeys as $mk)
                        @php
                            $ton = (float) ($values[$mk] ?? 0.0);
                            $colTotal = (float) ($totalsByMonth[$mk] ?? 0.0);
                            $tonText = $ton > 0.0 ? $fmtTon($ton) : '';
                            $pctText = $fmtPct($ton, $colTotal);
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
                            <span class="cell-pre" style="font-weight: bold;">{{ $fmtCell($rowTotalText, $rowTotalPct) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr class="empty-state-row">
                    <td colspan="{{ $monthKeys !== [] ? 3 + count($monthKeys) : 3 }}"
                        style="text-align: center; font-weight: bold; font-style: italic; padding: 4px 0 4px 0;">
                        Tidak ada data</td>
                </tr>
            @endforelse

            @if ($rows !== [])
                <tr class="totals-row">
                    <td class="data-cell supplier" colspan="2">Grand Total</td>
                    @foreach ($monthKeys as $mk)
                        @php
                            $colTotal = (float) ($totalsByMonth[$mk] ?? 0.0);
                            $tonText = $colTotal > 0.0 ? $fmtTon($colTotal) : '';
                        @endphp
                        <td class="data-cell">
                            @if ($tonText !== '')
                                <span class="cell-pre">{{ $fmtCell($tonText, '100%') }}</span>
                            @endif
                        </td>
                    @endforeach
                    @php $grandText = $grandTotal > 0.0 ? $fmtTon($grandTotal) : ''; @endphp
                    <td class="data-cell">
                        @if ($grandText !== '')
                            <span class="cell-pre">{{ $fmtCell($grandText, '100%') }}</span>
                        @endif
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    </body>

</html>
