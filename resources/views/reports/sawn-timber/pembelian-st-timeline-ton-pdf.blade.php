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
            font-size: 11px;
            white-space: nowrap;
            border-bottom: 1px solid #000;
        }

        table.data-table td {
            text-align: center;
        }

        /* Hilangkan garis horizontal antar baris data. */
        table.data-table tbody tr.data-row td.data-cell {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .empty-state-row td {
            background: #c9d1df !important;
        }

        table.data-table tbody td[colspan] {
            background: #c9d1df !important;
        }

        table.data-table tbody tr:not(.data-row):not(.totals-row) td {
            background: #c9d1df !important;
        }

        .supplier {
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cell-pre {
            display: block;
            white-space: pre;
            font-family: "Calibri", "Courier New", monospace;
            text-align: center;
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

        @include('reports.partials.pdf-footer-table-style')
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

    <h1 class="report-title">Laporan Pembelian ST Time Line (Ton)</h1>
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
        @if ($rows !== [])
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="{{ 3 + count($monthKeys) }}"></td>
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
                            <span class="cell-pre"
                                style="font-weight: bold;">{{ $fmtCell($rowTotalText, $rowTotalPct) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr class="empty-state-row">
                    <td colspan="{{ $monthKeys !== [] ? 3 + count($monthKeys) : 3 }}" style="text-align: center; font-weight: bold;">
                        Tidak ada data.</td>
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

    @include('reports.partials.pdf-footer-table')
</body>

</html>
