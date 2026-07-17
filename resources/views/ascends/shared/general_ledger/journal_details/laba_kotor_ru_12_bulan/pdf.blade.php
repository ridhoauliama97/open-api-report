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

        .desc-row td {
            font-size: 10px;
            padding: 1px 3px;
        }

        .desc-row td:first-child {
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

        function fmtPct($value)
        {
            if ($value === null) return '- %';
            $v = (float) $value;
            if ($v == 0.0) return '0.00%';
            $formatted = number_format(abs($v), 2, ',', '.') . '%';
            if ($v < 0) {
                return '-' . $formatted;
            }
            return $formatted;
        }

        function stripSortPrefix($name)
        {
            return preg_replace('/^\d+/', '', $name);
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
                    @php
                        $groupDisplay = trim(stripSortPrefix($group['name']));
                    @endphp

                    @foreach ($group['description_names'] as $desc)
                        @php $globalRow++; @endphp
                        <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }} desc-row">
                            <td>{{ $desc['account_name'] }}</td>
                            @foreach ($months as $mk => $ml)
                                <td></td>
                            @endforeach
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endforeach

                    <tr class="margin-row">
                        <td>LABA (RUGI) KOTOR {{ $groupDisplay }}</td>
                        @foreach ($months as $mk => $ml)
                            @php $margin = $group['monthly_margin'][$mk] ?? null; @endphp
                            <td class="number nowrap {{ $margin !== null && $margin < 0 ? 'number-negative' : '' }}">
                                {{ fmtPct($margin) }}
                            </td>
                        @endforeach
                        <td class="number nowrap {{ ($group['rata_rata'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtPct($group['rata_rata'] ?? null) }}
                        </td>
                        <td class="number nowrap {{ ($group['terendah'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtPct($group['terendah'] ?? null) }}
                        </td>
                        <td class="number nowrap">{{ fmtPct($group['tertinggi'] ?? null) }}</td>
                    </tr>
                @endforeach

                <tr class="grand-row">
                    <td>TOTAL LABA (RUGI) KOTOR</td>
                    @foreach ($months as $mk => $ml)
                        @php
                            $ts = $totalMonthlySales[$mk] ?? 0;
                            $th = $totalMonthlyHpp[$mk] ?? 0;
                            $totalMargin = $ts != 0 ? ($ts - $th) / $ts * 100 : null;
                        @endphp
                        <td class="number nowrap {{ $totalMargin !== null && $totalMargin < 0 ? 'number-negative' : '' }}">
                            {{ $totalMargin !== null ? fmtPct(round($totalMargin, 2)) : '- %' }}
                        </td>
                    @endforeach
                    @php
                        $allMargins = [];
                        foreach ($months as $mk => $ml) {
                            $ts = $totalMonthlySales[$mk] ?? 0;
                            $th = $totalMonthlyHpp[$mk] ?? 0;
                            $m = $ts != 0 ? ($ts - $th) / $ts * 100 : null;
                            if ($m !== null) $allMargins[$mk] = $m;
                        }
                        $marginValues = array_values($allMargins);
                        $avgMargin = count($marginValues) > 0 ? round(array_sum($marginValues) / count($marginValues), 2) : null;
                        $minMargin = count($marginValues) > 0 ? round(min($marginValues), 2) : null;
                        $maxMargin = count($marginValues) > 0 ? round(max($marginValues), 2) : null;
                    @endphp
                    <td class="number nowrap {{ $avgMargin !== null && $avgMargin < 0 ? 'number-negative' : '' }}">{{ fmtPct($avgMargin) }}</td>
                    <td class="number nowrap {{ $minMargin !== null && $minMargin < 0 ? 'number-negative' : '' }}">{{ fmtPct($minMargin) }}</td>
                    <td class="number nowrap">{{ fmtPct($maxMargin) }}</td>
                </tr>

                <tr class="hpp-global-row">
                    <td>HPP GLOBAL</td>
                    @foreach ($months as $mk => $ml)
                        @php $hpp = $hppGlobal[$mk] ?? null; @endphp
                        <td class="number nowrap {{ $hpp !== null ? 'number-negative' : '' }}">
                            {{ $hpp !== null ? fmtPct(-$hpp) : '- %' }}
                        </td>
                    @endforeach
                    @php
                        $hppValues = array_values(array_filter($hppGlobal, fn ($v) => $v !== null));
                        $avgHpp = count($hppValues) > 0 ? round(array_sum($hppValues) / count($hppValues), 2) : null;
                        $minHpp = count($hppValues) > 0 ? round(min($hppValues), 2) : null;
                        $maxHpp = count($hppValues) > 0 ? round(max($hppValues), 2) : null;
                    @endphp
                    <td class="number nowrap {{ $avgHpp !== null ? 'number-negative' : '' }}">{{ $avgHpp !== null ? fmtPct(-$avgHpp) : '- %' }}</td>
                    <td class="number nowrap {{ $maxHpp !== null ? 'number-negative' : '' }}">{{ $maxHpp !== null ? fmtPct(-$maxHpp) : '- %' }}</td>
                    <td class="number nowrap {{ $minHpp !== null ? 'number-negative' : '' }}">{{ $minHpp !== null ? fmtPct(-$minHpp) : '- %' }}</td>
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
