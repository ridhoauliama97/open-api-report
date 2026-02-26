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
            margin: 16mm 10mm 16mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            color: #000;
            line-height: 1.2;
        }

        .report-title {
            text-align: center;
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 14px 0;
            font-size: 12px;
            color: #636466;
        }

        .group-title {
            margin: 12px 0 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #666;
            padding: 3px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
        }

        td.number {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
            white-space: nowrap;
        }

        td.center {
            text-align: center;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .headers-row th {
            font-weight: bold;
            border: 1.5px solid #000;
            font-size: 11px;
        }

        .totals-row td {
            font-weight: bold;
            border: 1.5px solid #000;
            font-size: 11px;
        }

        .section-break {
            page-break-before: always;
        }

        .chart-wrap {
            border: 1px solid #666;
            padding: 6px;
            margin-top: 6px;
        }

        .chart-title {
            text-align: center;
            margin: 0 0 4px 0;
            font-size: 11px;
            font-weight: bold;
        }

        .summary-note {
            margin: 4px 0 10px 0;
            font-size: 10px;
        }

        .summary-note .label {
            font-weight: bold;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left,
        .footer-right {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d M Y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d M Y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

        $fmt4 = static fn($value): string => number_format((float) $value, 4, '.', ',');
        $fmt2 = static fn($value): string => number_format((float) $value, 2, '.', ',');
        $fmt4BlankZero = static function ($value) use ($fmt4): string {
            $num = (float) $value;
            return abs($num) < 0.0000001 ? '' : $fmt4($num);
        };
        $fmt2BlankZero = static function ($value) use ($fmt2): string {
            $num = (float) $value;
            return abs($num) < 0.0000001 ? '' : $fmt2($num);
        };
    @endphp

    <h1 class="report-title">Laporan Penerimaan Kayu Bulat Per Supplier Bulanan (Grafik)</h1>
    <p class="report-subtitle">Dari {{ $start }} Sampai {{ $end }}</p>

    @forelse ($groups as $groupIndex => $group)
        @php
            $groupName = (string) ($group['name'] ?? 'Tanpa Group');
            $suppliers = is_array($group['suppliers'] ?? null) ? $group['suppliers'] : [];
            $monthKeys = is_array($group['month_keys'] ?? null) ? $group['month_keys'] : [];
            $monthLabels = is_array($group['month_labels'] ?? null) ? $group['month_labels'] : [];
            $monthTotals = is_array($group['month_totals'] ?? null) ? $group['month_totals'] : [];
            $summary = is_array($group['summary'] ?? null) ? $group['summary'] : [];
        @endphp

        <div class="group-title">{{ $groupName }}</div>
        <table>
            <thead>
                <tr class="headers-row">
                    <th style="width: 36%">Supplier</th>
                    <th style="width: 12%">Total</th>
                    @foreach ($monthLabels as $label)
                        <th
                            style="width: {{ count($monthLabels) > 0 ? number_format(36 / count($monthLabels), 2, '.', '') : '12' }}%">
                            {{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($suppliers as $supplierRow)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td>{{ (string) ($supplierRow['supplier'] ?? '') }}</td>
                        <td class="number">{{ $fmt4BlankZero($supplierRow['total'] ?? 0) }}</td>
                        @foreach ($monthKeys as $monthKey)
                            <td class="number">{{ $fmt4BlankZero($supplierRow['month_values'][$monthKey] ?? 0) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 2 + count($monthKeys) }}" class="center">Tidak ada data.</td>
                    </tr>
                @endforelse
                @if ($suppliers !== [])
                    <tr class="totals-row">
                        <td style="text-align: center;">Total</td>
                        <td class="number">{{ $fmt4BlankZero($summary['total'] ?? 0) }}</td>
                        @foreach ($monthKeys as $monthKey)
                            <td class="number">{{ $fmt4BlankZero($monthTotals[$monthKey] ?? 0) }}</td>
                        @endforeach
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="summary-note">
            <span class="label">Keterangan:</span>
            Total = {{ $fmt4BlankZero($summary['total'] ?? 0) }},
            Avg = {{ $fmt2BlankZero($summary['avg'] ?? 0) }},
            Min = {{ $fmt2BlankZero($summary['min'] ?? 0) }},
            Max = {{ $fmt2BlankZero($summary['max'] ?? 0) }}.
        </div>

        <div class="section-break"></div>
        <h1 class="report-title">Laporan Penerimaan Kayu Bulat Per Supplier Bulanan (Grafik)</h1>
        <p class="report-subtitle">Dari {{ $start }} Sampai {{ $end }}</p>
        <div class="group-title">{{ $groupName }}</div>

        @php
            $svgWidth = 960;
            $svgHeight = 300;
            $padLeft = 52;
            $padRight = 14;
            $padTop = 18;
            $padBottom = 90;
            $plotWidth = $svgWidth - $padLeft - $padRight;
            $plotHeight = $svgHeight - $padTop - $padBottom;
            $count = max(count($suppliers), 1);
            $barGap = 6;
            $barWidth = max(8, $plotWidth / $count - $barGap);
            $maxVal = 0.0;
            foreach ($suppliers as $s) {
                $maxVal = max($maxVal, (float) ($s['total'] ?? 0));
            }
            $yStep = 10.0;
            $maxVal = $maxVal > 0 ? $maxVal : $yStep;
            $maxVal = ceil($maxVal / $yStep) * $yStep;
            $yTicks = max(1, (int) ($maxVal / $yStep));
        @endphp

        <div class="chart-wrap">
            <p class="chart-title">Grafik Supplier Bulanan - {{ $groupName }}</p>
            <svg width="{{ $svgWidth }}" height="{{ $svgHeight }}"
                viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}" xmlns="http://www.w3.org/2000/svg">
                <rect x="0" y="0" width="{{ $svgWidth }}" height="{{ $svgHeight }}" fill="#fff" />
                <line x1="{{ $padLeft }}" y1="{{ $padTop + $plotHeight }}" x2="{{ $padLeft + $plotWidth }}"
                    y2="{{ $padTop + $plotHeight }}" stroke="#333" stroke-width="1" />
                <line x1="{{ $padLeft }}" y1="{{ $padTop }}" x2="{{ $padLeft }}"
                    y2="{{ $padTop + $plotHeight }}" stroke="#333" stroke-width="1" />

                @for ($i = 0; $i <= $yTicks; $i++)
                    @php
                        $tickVal = $yStep * $i;
                        $y = $padTop + $plotHeight - $plotHeight * ($i / $yTicks);
                    @endphp
                    <line x1="{{ $padLeft }}" y1="{{ $y }}" x2="{{ $padLeft + $plotWidth }}"
                        y2="{{ $y }}" stroke="#ddd" stroke-width="1" />
                    <text x="{{ $padLeft - 6 }}" y="{{ $y + 3 }}" font-size="9" text-anchor="end"
                        fill="#444">{{ number_format($tickVal, 0, '.', ',') }}</text>
                @endfor

                @foreach ($suppliers as $index => $supplier)
                    @php
                        $x = $padLeft + $index * ($barWidth + $barGap);
                        $val = (float) ($supplier['total'] ?? 0);
                        $barH = $maxVal > 0 ? ($val / $maxVal) * $plotHeight : 0;
                        $y = $padTop + $plotHeight - $barH;
                        $label = (string) ($supplier['supplier'] ?? '');
                        $short = mb_substr($label, 0, 24) . (mb_strlen($label) > 24 ? '...' : '');
                    @endphp
                    <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}"
                        height="{{ $barH }}" fill="#0d6efd" />
                    @if ($val > 0)
                        <text x="{{ $x + $barWidth / 2 }}" y="{{ $y - 2 }}" font-size="8" text-anchor="middle"
                            fill="#222">{{ number_format($val, 1, '.', ',') }}</text>
                    @endif
                    <text x="{{ $x + $barWidth / 2 }}" y="{{ $padTop + $plotHeight + 12 }}" font-size="8"
                        text-anchor="end"
                        transform="rotate(-45 {{ $x + $barWidth / 2 }} {{ $padTop + $plotHeight + 12 }})"
                        fill="#333">{{ $short }}</text>
                @endforeach
            </svg>
        </div>

        @if (!$loop->last)
            <div class="section-break"></div>
        @endif
    @empty
        <table>
            <tbody>
                <tr>
                    <td class="center">Tidak ada data untuk periode ini.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
