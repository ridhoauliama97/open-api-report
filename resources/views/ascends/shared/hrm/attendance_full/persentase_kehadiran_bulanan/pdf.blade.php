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
            border: 1px solid #000;
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

        .group-row td {
            font-weight: bold;
            text-align: center;
            font-size: 11px;
            font-style: italic;
            padding: 5px 6px;
            color: #9c111d;

            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .center {
            text-align: center;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
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
        $groupedRows = $reportData['grouped_rows'] ?? [];
        $months = $reportData['months'] ?? [];
        $monthLabels = $reportData['month_labels'] ?? [];
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $monthWidth = count($months) > 0 ? max(5, min(8, (int) floor(37 / count($months)))) : 7;
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => $periodLabel])

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 22%;">Nama</th>
                <th style="width: 23%;">Jabatan</th>
                <th style="width: 9%;">Masa Kerja</th>
                @foreach ($months as $month)
                    <th style="width: {{ $monthWidth }}%;">{{ (string) ($monthLabels[$month] ?? $month) }}</th>
                @endforeach
                <th style="width: 9%;">Total<br>&lt; 93%</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groupedRows as $group)
                @php
                    $rows = $group['rows'] ?? [];
                    $summary = $group['summary'] ?? [];
                @endphp
                <tr class="group-row">
                    <td colspan="{{ 4 + count($months) }}">Departemen : {{ (string) ($group['department'] ?? '') }}</td>
                </tr>
                @foreach ($rows as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                        <td>{{ (string) ($row['Jabatan'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Masa Kerja'] ?? '') }}</td>
                        @foreach ($months as $month)
                            <td class="center">{{ (string) ($row[(string) $month] ?? '') }}</td>
                        @endforeach
                        <td class="center">{{ (int) ($row['Total < 93%'] ?? 0) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td class="center" colspan="{{ 3 + count($months) }}">Total</td>
                    <td class="center">{{ (int) ($summary['subtotal'] ?? 0) }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="{{ 4 + count($months) }}">Tidak ada data attendance yang dapat ditampilkan.</td>
                </tr>
            @endforelse
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
