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
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 2px 3px;
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

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .grand-row td {
            font-weight: bold;
            text-align: right;
            font-size: 10px;
            font-style: italic;
            padding: 4px 5px;
            color: #000;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .center {
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        $rows = array_values($rows ?? ($reportData['rows'] ?? []));
        $printedAt = \Carbon\Carbon::parse($reportData['printed_at'] ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y');
        $periodLabel = trim((string) ($reportData['period']['label'] ?? ''));
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
    @endphp

    @include('ascends.shared.partials.report-header', [
        'subtitle' => $periodLabel !== '' ? 'Periode : ' . $periodLabel : 'Per : ' . $printedAt,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 9%;">NIK</th>
                <th style="width: 21%;">Nama Lengkap</th>
                <th style="width: 22%;">Jabatan</th>
                <th style="width: 17%;">Departemen</th>
                <th style="width: 10%;">Tanggal Masuk</th>
                <th style="width: 10%;">Tanggal Berakhir</th>
                <th style="width: 6%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center">{{ (string) ($row['Code'] ?? '') }}</td>
                    <td>{{ (string) ($row['Full Name'] ?? '') }}</td>
                    <td>{{ (string) ($row['Job Title'] ?? '') }}</td>
                    <td>{{ (string) ($row['Department'] ?? '') }}</td>
                    <td class="center">{{ (string) ($row['Join Date'] ?? '') }}</td>
                    <td class="center">{{ (string) ($row['Expiry Date'] ?? '') }}</td>
                    <td class="center">{{ (string) ($row['Active'] ?? '') }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="8">Tidak ada data.</td>
                </tr>
            @endforelse

            <tr class="grand-row">
                <td colspan="8">Grand Total = {{ (int) ($reportData['total_rows'] ?? count($rows)) }}</td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table', [
        'generatedAtText' => $generatedAtText,
        'generatedByName' => $generatedByName,
    ])
</body>

</html>
