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
            padding: 2px 3px;
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

        .group-total-row td {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 2px 3px;
        }

        .grand-row td {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 4px;
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
        $currentMonth = $reportData['current_month'] ?? '';
        $previousMonth = $reportData['previous_month'] ?? '';
        $grandCurrent = (float) ($reportData['grand_current'] ?? 0);
        $grandPrevious = (float) ($reportData['grand_previous'] ?? 0);
        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerTitle = trim((string) ($title ?? $reportData['title'] ?? $fallbackTitle ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));

        function formatAmount($value)
        {
            $value = (float) $value;
            if ($value < 0) {
                return '(' . number_format(abs($value), 2, ',', '.') . ')';
            }
            return number_format($value, 2, ',', '.');
        }

        function formatRasio($value)
        {
            $v = (float) $value;
            $formatted = number_format(abs($v), 2, ',', '.') . '%';
            if ($v < 0) {
                return '(' . $formatted . ')';
            }
            return $formatted;
        }

        function formatBeda($value)
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
                <col style="width: 28%;">
                <col style="width: 16%;">
                <col style="width: 12%;">
                <col style="width: 16%;">
                <col style="width: 12%;">
                <col style="width: 16%;">
            </colgroup>
            <thead>
                <tr>
                    <th style="width: 28%;">PENJUALAN</th>
                    <th style="width: 16%;">{{ $currentMonth }}</th>
                    <th style="width: 12%;">RASIO %</th>
                    <th style="width: 16%;">{{ $previousMonth }}</th>
                    <th style="width: 12%;">RASIO %</th>
                    <th style="width: 16%;">Beda</th>
                </tr>
            </thead>
            <tbody>
                @php $globalRow = 0; @endphp
                @foreach ($groups as $group)
                    @php $groupName = $group['name']; @endphp
                    <tr class="section-header">
                        <td colspan="6">{{ $groupName }}</td>
                    </tr>

                    @foreach ($group['items'] as $item)
                        @php $globalRow++; @endphp
                        @php
                            $currAmt = (float) ($item['current_amount'] ?? 0);
                            $prevAmt = (float) ($item['previous_amount'] ?? 0);
                        @endphp
                        <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }} subsection-row">
                            <td>{{ (string) ($item['account_name'] ?? '') }}</td>
                            <td class="number nowrap {{ $currAmt < 0 ? 'number-negative' : '' }}">
                                {{ $currAmt != 0 ? formatAmount($currAmt) : '-' }}
                            </td>
                            <td class="number nowrap">-</td>
                            <td class="number nowrap {{ $prevAmt < 0 ? 'number-negative' : '' }}">
                                {{ $prevAmt != 0 ? formatAmount($prevAmt) : '-' }}
                            </td>
                            <td class="number nowrap">-</td>
                            <td></td>
                        </tr>
                    @endforeach

                    @php
                        $currTotal = (float) ($group['current_total'] ?? 0);
                        $prevTotal = (float) ($group['previous_total'] ?? 0);
                        $currRasio = (float) ($group['current_rasio'] ?? 0);
                        $prevRasio = (float) ($group['previous_rasio'] ?? 0);
                        $beda = (float) ($group['beda'] ?? 0);
                    @endphp
                    <tr class="group-total-row">
                        <td>LABA (RUGI) KOTOR PENJUALAN {{ $groupName }}</td>
                        <td class="number nowrap {{ $currTotal < 0 ? 'number-negative' : '' }}">
                            {{ $currTotal != 0 ? formatAmount($currTotal) : '-' }}
                        </td>
                        <td class="number nowrap {{ $currRasio < 0 ? 'number-negative' : '' }}">
                            {{ $currRasio != 0 ? formatRasio($currRasio) : '0.00%' }}
                        </td>
                        <td class="number nowrap {{ $prevTotal < 0 ? 'number-negative' : '' }}">
                            {{ $prevTotal != 0 ? formatAmount($prevTotal) : '-' }}
                        </td>
                        <td class="number nowrap {{ $prevRasio < 0 ? 'number-negative' : '' }}">
                            {{ $prevRasio != 0 ? formatRasio($prevRasio) : '0.00%' }}
                        </td>
                        <td class="number nowrap {{ $beda < 0 ? 'number-negative' : '' }}">
                            {{ $beda != 0 ? formatBeda($beda) : '0.00%' }}
                        </td>
                    </tr>
                @endforeach

                <tr class="grand-row">
                    <td>TOTAL LABA (RUGI) KOTOR</td>
                    <td class="number nowrap {{ $grandCurrent < 0 ? 'number-negative' : '' }}">
                        {{ $grandCurrent != 0 ? formatAmount($grandCurrent) : '-' }}
                    </td>
                    <td></td>
                    <td class="number nowrap {{ $grandPrevious < 0 ? 'number-negative' : '' }}">
                        {{ $grandPrevious != 0 ? formatAmount($grandPrevious) : '-' }}
                    </td>
                    <td></td>
                    <td></td>
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
                    <td colspan="6">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
