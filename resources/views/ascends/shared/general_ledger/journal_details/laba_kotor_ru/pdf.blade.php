<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
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

        .report-companyTitle {
            text-align: center;
            margin: 0 0 4px 0;
            font-size: 18px;
            font-weight: bold;
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

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
            border: 1px solid #000;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 1px 2px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .data-table td {
            font-size: 10px;
            border-top: none;
            border-bottom: none;
        }

        .section-header td {
            font-weight: bold;
            font-size: 10px;
            font-style: italic;
            padding: 3px 3px;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .subsection-row td {
            font-size: 10px;
            padding: 1px 3px;
        }

        .subsection-row td:first-child {
            padding-left: 10px;
        }

        .margin-row td {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 2px 3px;
        }

        .grand-row td {
            font-weight: bold;
            font-size: 10px;
            border-bottom: 1px solid #000;
            padding: 2px 3px;
        }

        .hpp-global-row td {
            font-weight: bold;
            font-size: 10px;
            border-bottom: 1px solid #000;
            padding: 2px 3px;
        }

        .keterangan {
            margin-top: 10px;
            font-size: 10px;
            font-style: italic;
            color: #636466;
            text-align: left;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
        }

        .number-negative {
            color: #9c111d;
        }

        .nowrap {
            white-space: nowrap;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            color: #9c111d;
            font-size: 11px;
            padding: 8px 4px;
        }
    </style>
</head>

<body>
    @php
        $groups = $reportData['groups'] ?? [];
        $months = $reportData['months'] ?? [];
        $totalMonthlySales = $reportData['total_monthly_sales'] ?? [];
        $totalMonthlyHpp = $reportData['total_monthly_hpp'] ?? [];
        $hppGlobal = $reportData['hpp_global'] ?? [];
        $numMonths = count($months);
        $namePct = 22;
        $statPct = 6;
        $monthPct = $numMonths > 0 ? (100 - $namePct - ($statPct * 3)) / $numMonths : 0;

        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerTitle = trim((string) ($title ?? $reportData['title'] ?? $fallbackTitle ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $negativeGroupStyle = 'color: #9c111d;';

        function formatAmount($value)
        {
            $value = (float) $value;
            if ($value < 0) {
                return '(' . number_format(abs($value), 2, ',', '.') . ')';
            }
            return number_format($value, 2, ',', '.');
        }

        function formatPct($value)
        {
            $v = (float) $value;
            $formatted = number_format(abs($v), 2, ',', '.') . '%';
            if ($v < 0) {
                return '(' . $formatted . ')';
            }
            return $formatted;
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($groups) > 0)
        <table class="data-table">
            <colgroup>
                <col style="width: {{ $namePct }}%;">
                @foreach ($months as $mk => $ml)
                    <col style="width: {{ $monthPct }}%;">
                @endforeach
                <col style="width: {{ $statPct }}%;">
                <col style="width: {{ $statPct }}%;">
                <col style="width: {{ $statPct }}%;">
            </colgroup>
            <thead>
                <tr>
                    <th style="width: {{ $namePct }}%;">PENJUALAN</th>
                    @foreach ($months as $mk => $ml)
                        <th style="width: {{ $monthPct }}%;">{{ $ml }}</th>
                    @endforeach
                    <th style="width: {{ $statPct }}%;">Rata - Rata</th>
                    <th style="width: {{ $statPct }}%;">Terendah</th>
                    <th style="width: {{ $statPct }}%;">Tertinggi</th>
                </tr>
            </thead>
            <tbody>
                @php $globalRow = 0; @endphp
                @foreach ($groups as $group)
                    @php $groupName = $group['name']; @endphp
                    <tr class="section-header">
                        <td colspan="{{ 1 + $numMonths + 3 }}">{{ $groupName }}</td>
                    </tr>

                    @foreach ($group['penjualan_items'] as $item)
                        @php $globalRow++; @endphp
                        <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }} subsection-row">
                            <td>{{ (string) ($item['account_name'] ?? '') }}</td>
                            @foreach ($months as $mk => $ml)
                                @php $amt = $item['monthly_amounts'][$mk] ?? 0; @endphp
                                <td class="number nowrap">{{ $amt != 0 ? formatAmount($amt) : '-' }}</td>
                            @endforeach
                            <td class="number nowrap">{{ formatAmount($item['total_amount']) }}</td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endforeach

                    <tr class="margin-row">
                        <td>LABA (RUGI) KOTOR {{ $groupName }}</td>
                        @foreach ($months as $mk => $ml)
                            @php $margin = $group['monthly_margin'][$mk] ?? 0; @endphp
                            <td class="number nowrap {{ $margin < 0 ? 'number-negative' : '' }}">{{ formatPct($margin) }}</td>
                        @endforeach
                        <td class="number nowrap">{{ formatPct($group['rata_rata'] ?? 0) }}</td>
                        <td class="number nowrap {{ ($group['terendah'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ formatPct($group['terendah'] ?? 0) }}
                        </td>
                        <td class="number nowrap">{{ formatPct($group['tertinggi'] ?? 0) }}</td>
                    </tr>
                @endforeach

                <tr class="grand-row">
                    <td>TOTAL LABA (RUGI) KOTOR</td>
                    @foreach ($months as $mk => $ml)
                        @php
                            $ts = $totalMonthlySales[$mk] ?? 0;
                            $th = $totalMonthlyHpp[$mk] ?? 0;
                            $totalMargin = $ts != 0 ? ($ts - $th) / $ts * 100 : 0;
                        @endphp
                        <td class="number nowrap {{ $totalMargin < 0 ? 'number-negative' : '' }}">
                            {{ formatPct(round($totalMargin, 2)) }}
                        </td>
                    @endforeach
                    @php
                        $allMargins = [];
                        foreach ($months as $mk => $ml) {
                            $ts = $totalMonthlySales[$mk] ?? 0;
                            $th = $totalMonthlyHpp[$mk] ?? 0;
                            $allMargins[$mk] = $ts != 0 ? ($ts - $th) / $ts * 100 : 0;
                        }
                        $marginValues = array_values($allMargins);
                        $avgMargin = count($marginValues) > 0 ? round(array_sum($marginValues) / count($marginValues), 2) : 0;
                        $minMargin = count($marginValues) > 0 ? round(min($marginValues), 2) : 0;
                        $maxMargin = count($marginValues) > 0 ? round(max($marginValues), 2) : 0;
                    @endphp
                    <td class="number nowrap {{ $avgMargin < 0 ? 'number-negative' : '' }}">{{ formatPct($avgMargin) }}</td>
                    <td class="number nowrap {{ $minMargin < 0 ? 'number-negative' : '' }}">{{ formatPct($minMargin) }}</td>
                    <td class="number nowrap">{{ formatPct($maxMargin) }}</td>
                </tr>

                <tr class="hpp-global-row">
                    <td>HPP GLOBAL</td>
                    @foreach ($months as $mk => $ml)
                        @php $hpp = $hppGlobal[$mk] ?? 0; @endphp
                        <td class="number nowrap number-negative">{{ formatPct(-$hpp) }}</td>
                    @endforeach
                    @php
                        $hppValues = array_values($hppGlobal);
                        $avgHpp = count($hppValues) > 0 ? round(array_sum($hppValues) / count($hppValues), 2) : 0;
                        $minHpp = count($hppValues) > 0 ? round(min($hppValues), 2) : 0;
                        $maxHpp = count($hppValues) > 0 ? round(max($hppValues), 2) : 0;
                    @endphp
                    <td class="number nowrap number-negative">{{ formatPct(-$avgHpp) }}</td>
                    <td class="number nowrap number-negative">{{ formatPct(-$maxHpp) }}</td>
                    <td class="number nowrap number-negative">{{ formatPct(-$minHpp) }}</td>
                </tr>
            </tbody>
        </table>

        <p class="keterangan">
            Keterangan : Angka ini harus cocok dengan di bagian laba (Rugi) kotor di Laporan Laba Rugi
        </p>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="{{ 1 + $numMonths + 3 }}">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>