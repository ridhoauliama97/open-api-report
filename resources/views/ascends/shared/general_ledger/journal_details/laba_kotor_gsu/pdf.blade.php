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
            line-height: 1.12;
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
            padding: 2px 3px;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .item-row td:first-child {
            padding-left: 8px;
        }

        .margin-row td,
        .grand-row td,
        .hpp-global-row td {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 2px 3px;
        }

        .keterangan {
            margin-top: 8px;
            font-size: 10px;
            font-style: italic;
            color: #636466;
            text-align: left;
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
            font-size: 10px;
            padding: 8px 4px;
        }
    </style>
</head>

<body>
    @php
        $groups = $reportData['groups'] ?? [];
        $months = $reportData['months'] ?? [];
        $totalMargins = $reportData['total_margins'] ?? [];
        $hppGlobal = $reportData['hpp_global'] ?? [];
        $numMonths = count($months);
        $namePct = 23;
        $statPct = 6;
        $monthPct = $numMonths > 0 ? (100 - $namePct - $statPct * 3) / $numMonths : 0;
        $totalCols = 1 + $numMonths + 3;

        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));

        $formatAmount = static function ($value): string {
            $value = (float) $value;
            if ($value == 0.0) {
                return '-';
            }
            if ($value < 0) {
                return '(' . number_format(abs($value), 0, '.', ',') . ')';
            }
            return number_format($value, 0, '.', ',');
        };

        $formatPct = static function ($value): string {
            $value = (float) $value;
            if ($value == 0.0) {
                return '- %';
            }
            $formatted = number_format(abs($value), 2, '.', ',') . '%';
            if ($value < 0) {
                return '(' . $formatted . ')';
            }
            return $formatted;
        };
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($groups) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20%">PENJUALAN</th>
                    @foreach ($months as $month)
                        <th style="width:fit-content;">{{ $month }}</th>
                    @endforeach
                    <th>Rata-Rata</th>
                    <th>Terendah</th>
                    <th>Tertinggi</th>
                </tr>
            </thead>
            <tbody>
                @php $globalRow = 0; @endphp
                @foreach ($groups as $group)
                    <tr class="section-header">
                        <td colspan="{{ $totalCols }}">{{ (string) ($group['name'] ?? '') }}</td>
                    </tr>

                    @foreach ($group['items'] as $item)
                        @php $globalRow++; @endphp
                        <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }} item-row">
                            <td>{{ (string) ($item['account_name'] ?? '') }}</td>
                            @foreach ($months as $monthKey => $monthLabel)
                                <td class="number nowrap">{{ $formatAmount($item['monthly_amounts'][$monthKey] ?? 0) }}
                                </td>
                            @endforeach
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endforeach

                    <tr class="margin-row">
                        <td>LABA (RUGI) KOTOR PENJUALAN {{ (string) ($group['name'] ?? '') }}</td>
                        @foreach ($months as $monthKey => $monthLabel)
                            @php $margin = (float) ($group['monthly_margin'][$monthKey] ?? 0); @endphp
                            <td class="number nowrap {{ $margin < 0 ? 'number-negative' : '' }}">
                                {{ $formatPct($margin) }}</td>
                        @endforeach
                        <td class="number nowrap">{{ $formatPct($group['rata_rata'] ?? 0) }}</td>
                        <td class="number nowrap {{ ($group['terendah'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ $formatPct($group['terendah'] ?? 0) }}</td>
                        <td class="number nowrap">{{ $formatPct($group['tertinggi'] ?? 0) }}</td>
                    </tr>
                @endforeach

                <tr class="grand-row">
                    <td>TOTAL LABA (RUGI) KOTOR</td>
                    @foreach ($months as $monthKey => $monthLabel)
                        @php $margin = (float) ($totalMargins[$monthKey] ?? 0); @endphp
                        <td class="number nowrap {{ $margin < 0 ? 'number-negative' : '' }}">{{ $formatPct($margin) }}
                        </td>
                    @endforeach
                    <td class="number nowrap">{{ $formatPct($reportData['total_rata_rata'] ?? 0) }}</td>
                    <td class="number nowrap {{ ($reportData['total_terendah'] ?? 0) < 0 ? 'number-negative' : '' }}">
                        {{ $formatPct($reportData['total_terendah'] ?? 0) }}</td>
                    <td class="number nowrap">{{ $formatPct($reportData['total_tertinggi'] ?? 0) }}</td>
                </tr>

                <tr class="hpp-global-row">
                    <td>HPP GLOBAL</td>
                    @foreach ($months as $monthKey => $monthLabel)
                        @php $hpp = (float) ($hppGlobal[$monthKey] ?? 0); @endphp
                        <td class="number nowrap {{ $hpp > 0 ? 'number-negative' : '' }}">{{ $formatPct(-$hpp) }}</td>
                    @endforeach
                    <td class="number nowrap number-negative">
                        {{ $formatPct(-($reportData['hpp_global_rata_rata'] ?? 0)) }}</td>
                    <td class="number nowrap number-negative">
                        {{ $formatPct(-($reportData['hpp_global_tertinggi'] ?? 0)) }}</td>
                    <td class="number nowrap number-negative">
                        {{ $formatPct(-($reportData['hpp_global_terendah'] ?? 0)) }}</td>
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
                    <td colspan="{{ $totalCols }}">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
