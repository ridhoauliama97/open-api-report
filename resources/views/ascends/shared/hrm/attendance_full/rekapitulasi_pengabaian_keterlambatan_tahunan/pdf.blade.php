<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta charset="utf-8">
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
            line-height: 1.2;
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
            border-spacing: 0;
            /* border: 1px solid #000; */
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .total-row td {
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
        $rows = $reportData['rows'] ?? [];
        $months = $reportData['months'] ?? [];
        $monthLabels = $reportData['month_labels'] ?? [];
        $monthTotals = $reportData['month_totals'] ?? [];
        $grandTotal = (int) ($reportData['grand_total'] ?? 0);
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $numberWidth = 5;
        $nameWidth = count($months) > 8 ? 28 : 36;
        $monthWidth = count($months) > 0 ? max(5, min(7, (int) floor((86 - $numberWidth - $nameWidth) / (count($months) + 1)))) : 6;
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => $periodLabel])

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: {{ $numberWidth }}%;">No</th>
                <th rowspan="2" style="width: {{ $nameWidth }}%;">Nama</th>
                <th colspan="{{ count($months) }}">Bulan</th>
                <th rowspan="2" style="width: {{ $monthWidth }}%;">Total</th>
            </tr>
            <tr>
                @foreach ($months as $month)
                    <th style="width: {{ $monthWidth }}%;">{{ (string) ($monthLabels[$month] ?? str_pad((string) $month, 2, '0', STR_PAD_LEFT)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                    @foreach ($months as $month)
                        <td class="center">{{ (string) ($row[(string) $month] ?? '-') }}</td>
                    @endforeach
                    <td class="center">{{ (int) ($row['Total'] ?? 0) }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="{{ 3 + count($months) }}">Tidak Ada Data</td>
                </tr>
            @endforelse

            @if (count($rows) > 0)
                <tr class="total-row">
                    <td colspan="2" class="center">Total</td>
                    @foreach ($months as $month)
                        <td class="center">{{ (int) ($monthTotals[$month] ?? 0) }}</td>
                    @endforeach
                    <td class="center">{{ $grandTotal }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <htmlpagefooter name="reportFooter">
        @include('reports.partials.pdf-footer-table', [
            'generatedByName' => $generatedByName,
            'generatedAtText' => $generatedAtText,
        ])
    </htmlpagefooter>
</body>

</html>
