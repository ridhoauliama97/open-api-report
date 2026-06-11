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

        .center {
            text-align: center;
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
            font-size: 11px;
        }

        .summary-info {
            margin-top: 8px;
            width: 260px;
            font-size: 11px;
            font-weight: bold;
            border-collapse: collapse;
        }

        .summary-info td {
            padding: 2px 0;
        }

        .summary-info .value {
            width: 40px;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $rows = $reportData['rows'] ?? [];
        $summary = $reportData['summary'] ?? [];
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $title = trim((string) ($reportData['title'] ?? 'Daftar Libur Dan Cuti Bersama'));
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => $periodLabel, 'title' => $title])

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 8%;">No</th>
                <th style="width: 22%;">Tanggal</th>
                <th style="width: 70%;">Nama Libur / Cuti Bersama</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ (int) ($row['No'] ?? $loop->iteration) }}</td>
                    <td class="center">{{ (string) ($row['Tanggal'] ?? '') }}</td>
                    <td>{{ (string) ($row['Nama Libur / Cuti Bersama'] ?? '') }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="3">Tidak ada data libur dan cuti bersama yang dapat ditampilkan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary-info">
        <tr>
            <td>Total Cuti Bersama</td>
            <td class="value">{{ (int) ($summary['total_cuti_bersama'] ?? 0) }}</td>
        </tr>
        <tr>
            <td>Total Libur</td>
            <td class="value">{{ (int) ($summary['total_libur'] ?? 0) }}</td>
        </tr>
        <tr>
            <td>Total Libur dan Cuti Bersama</td>
            <td class="value">{{ (int) ($summary['total'] ?? ($reportData['total_rows'] ?? count($rows))) }}</td>
        </tr>
    </table>

    <htmlpagefooter name="reportFooter">
        @include('reports.partials.pdf-footer-table', [
            'generatedByName' => $generatedByName,
            'generatedAtText' => $generatedAtText,
        ])
    </htmlpagefooter>
</body>

</html>
