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
            border-spacing: 0;
            border: 1px solid #000;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 2px 2px;
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

        .grand-row td {
            text-align: right;
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 5px;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $rows = $reportData['rows'] ?? [];
        $totalRows = (int) ($reportData['total_rows'] ?? 0);
        $generatedByName = trim((string) ($reportData['printed_by'] ?? 'sistem'));
    @endphp

    @include('ascends.shared.partials.report-header', [
        'subtitle' => $reportData['per_date'] ?? ''
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 14%;">Kode Customer</th>
                <th style="width: 16%;">Nama Customer</th>
                <th style="width: 17%;">Alamat</th>
                <th style="width: 11%;">Kota</th>
                <th style="width: 12%;">Nama Pemilik</th>
                <th style="width: 10%;">Telepon</th>
                <th style="width: 8%;">Syarat (Term)</th>
                <th style="width: 10%;">Credit Limit</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ (string) ($row['Kode Customer'] ?? '') }}</td>
                    <td>{{ (string) ($row['Nama Customer'] ?? '') }}</td>
                    <td>{{ (string) ($row['Alamat'] ?? '') }}</td>
                    <td>{{ (string) ($row['Kota'] ?? '') }}</td>
                    <td>{{ (string) ($row['Nama Pemilik'] ?? '') }}</td>
                    <td class="center">{{ (string) ($row['Telepon'] ?? '') }}</td>
                    <td class="center">{{ (string) ($row['Syarat (Term)'] ?? '') }}</td>
                    <td class="right">{{ (string) ($row['Credit Limit'] ?? '') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="center" style="font-style: italic; padding: 10px 2px;">
                        Tidak ada data.
                    </td>
                </tr>
            @endforelse
            <tr class="grand-row">
                <td colspan="9">Grand Total : {{ $totalRows }}</td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table', [
        'generatedByName' => $generatedByName,
    ])
</body>

</html>
