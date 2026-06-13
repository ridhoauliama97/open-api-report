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
            margin: 2px 0 18px 0;
            font-size: 12px;
            color: #636466;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border-spacing: 0;
            border: 1px solid #000;
            margin-bottom: 10px;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 3px 4px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 10px;
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
            border-top: 1px solid #000;
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
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
        $monthKeys = $reportData['month_keys'] ?? [];
        $monthLabels = $reportData['month_labels'] ?? [];
        $totals = $reportData['totals'] ?? [];
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $title = trim((string) ($reportData['title'] ?? 'Laporan Keterlambatan Kehadiran Briefing Harian'));
        $monthCount = max(count($monthKeys), 1);
        $monthWidth = 24 / $monthCount;
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => $periodLabel, 'title' => $title])

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 11%;">Kode</th>
                <th style="width: 31%;">Nama</th>
                <th style="width: 34%;">Jabatan</th>
                @foreach ($monthKeys as $monthKey)
                    <th style="width: {{ $monthWidth }}%;">{{ (string) ($monthLabels[$monthKey] ?? $monthKey) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ (string) ($row['Kode'] ?? '') }}</td>
                    <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                    <td>{{ (string) ($row['Jabatan'] ?? '') }}</td>
                    @foreach ($monthKeys as $monthKey)
                        <td class="right">{{ (string) (($row['months'][$monthKey]['value'] ?? 0)) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="{{ 3 + count($monthKeys) }}">Tidak ada data.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="3" class="center">Total</td>
                @foreach ($monthKeys as $monthKey)
                    <td class="right">{{ (string) ((int) ($totals[$monthKey] ?? 0)) }}</td>
                @endforeach
            </tr>
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
