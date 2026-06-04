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

        .report-title {
            text-align: center;
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: bold;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .meta-table td {
            padding: 1px 2px;
            vertical-align: top;
        }

        .meta-label {
            width: 115px;
            font-weight: bold;
        }

        .meta-separator {
            width: 8px;
            text-align: center;
            font-weight: bold;
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
            padding: 2px 3px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 10px;
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
            font-size: 10px;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .signature-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 12px;
        }

        .signature-label {
            font-weight: bold;
            margin-bottom: 4px;
        }

        .signature-box {
            height: 54px;
            border: 1px solid #000;
            padding: 4px;
        }

        .center {
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        $rows = array_values($rows ?? ($reportData['rows'] ?? []));
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $group = trim((string) ($reportData['group'] ?? $company ?? ''));
    @endphp

    <h1 class="report-title">{{ $reportData['title'] }}</h1>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Divisi</td>
            <td class="meta-separator">:</td>
            <td>{{ $group }}</td>
            <td class="meta-label">Tanggal</td>
            <td class="meta-separator">:</td>
            <td>{{ (string) ($reportData['report_date'] ?? '') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Penanggung Jawab</td>
            <td class="meta-separator">:</td>
            <td>{{ (string) ($reportData['responsible_person'] ?? '') }}</td>
            <td class="meta-label">Tema</td>
            <td class="meta-separator">:</td>
            <td>{{ (string) ($reportData['theme'] ?? '') }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 5%;">No</th>
                <th rowspan="2" style="width: 30%;">Nama</th>
                <th rowspan="2" style="width: 12%;">Jam Masuk</th>
                <th rowspan="2" style="width: 16%;">Briefing</th>
                <th colspan="4">Telat / Tidak Briefing</th>
            </tr>
            <tr>
                <th style="width: 10%;">Telat</th>
                <th style="width: 9%;">Sakit</th>
                <th style="width: 9%;">Izin</th>
                <th style="width: 9%;">Alfa</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                    <td class="center">{{ (string) ($row['Jam Masuk'] ?? '') }}</td>
                    <td class="center">{{ (string) ($row['Briefing'] ?? '') }}</td>
                    <td class="center">{{ (string) ($row['Telat'] ?? '') }}</td>
                    <td class="center">{{ (string) ($row['Sakit'] ?? '') }}</td>
                    <td class="center">{{ (string) ($row['Izin'] ?? '') }}</td>
                    <td class="center">{{ (string) ($row['Alfa'] ?? '') }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="8">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-label">Penanggung Jawab</div>
                <div class="signature-box"></div>
            </td>
            <td>
                <div class="signature-label">Kesimpulan Briefing</div>
                <div class="signature-box"></div>
            </td>
        </tr>
    </table>

    @include('reports.partials.pdf-footer-table', [
        'generatedAtText' => $generatedAtText,
        'generatedByName' => $generatedByName,
    ])
</body>

</html>
