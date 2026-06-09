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
            border-spacing: 0;
            border: 1px solid #000;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 10px;
            text-align: center;
            background: #fff;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .grand-row td {
            font-weight: bold;
            background: #fff;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            font-size: 11px;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }
    </style>
</head>

<body>
    @php
        $rows = array_values($rows ?? ($reportData['rows'] ?? []));
        $grandSummary = $reportData['grand_summary'] ?? [];
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $footerCenterText = '';
    @endphp

    @include('ascends.shared.partials.report-header', [
        'fallbackTitle' => 'Laporan Rekapitulasi Absensi Briefing Harian',
        'subtitle' => $periodLabel,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 20%;">Divisi</th>
                <th style="width: 14%;">Jumlah Hadir<br>Tidak Telat</th>
                <th style="width: 10%;">Jumlah<br>Telat</th>
                <th style="width: 12%;">Jumlah<br>Tidak Hadir</th>
                <th style="width: 14%;">Jumlah Saat<br>Pukul 12.55<br>Wib</th>
                <th style="width: 10%;">Selisih</th>
                <th style="width: 20%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td>{{ (string) ($row['Divisi'] ?? '') }}</td>
                    <td class="center">{{ number_format((int) ($row['Jumlah Hadir Tidak Telat'] ?? 0), 0, ',', '.') }}</td>
                    <td class="center">{{ number_format((int) ($row['Jumlah Telat'] ?? 0), 0, ',', '.') }}</td>
                    <td class="center">{{ number_format((int) ($row['Jumlah Tidak Hadir'] ?? 0), 0, ',', '.') }}</td>
                    <td class="center">{{ (string) ($row['Jumlah Saat Pukul 12.55 Wib'] ?? '') }}</td>
                    <td class="center">{{ (string) ($row['Selisih'] ?? '') }}</td>
                    <td>{{ (string) ($row['Keterangan'] ?? '') }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="7">Tidak Ada Data</td>
                </tr>
            @endforelse

            <tr class="grand-row">
                <td>Total</td>
                <td class="center">{{ number_format((int) ($grandSummary['Jumlah Hadir Tidak Telat'] ?? 0), 0, ',', '.') }}</td>
                <td class="center">{{ number_format((int) ($grandSummary['Jumlah Telat'] ?? 0), 0, ',', '.') }}</td>
                <td class="center">{{ number_format((int) ($grandSummary['Jumlah Tidak Hadir'] ?? 0), 0, ',', '.') }}</td>
                <td class="center">{{ (string) ($grandSummary['Jumlah Saat Pukul 12.55 Wib'] ?? '') }}</td>
                <td class="center">{{ (string) ($grandSummary['Selisih'] ?? '') }}</td>
                <td>{{ (string) ($grandSummary['Keterangan'] ?? '') }}</td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table', [
        'generatedByName' => $generatedByName,
        'generatedAtText' => $generatedAtText,
        'footerCenterText' => $footerCenterText,
    ])
</body>

</html>
