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

        .group-header td {
            text-align: center;
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 2px;
        }

        .grand-row td {
            text-align: right;
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 5px;
        }

        .subtotal-row td {
            text-align: right;
            font-weight: bold;
            font-style: italic;
            font-size: 10px;
            border-top: 1px solid #000;
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

        .nowrap {
            white-space: nowrap;
        }
    </style>
</head>

<body>
    @php
        $sections = $reportData['sections'] ?? [];
        $totalRows = (int) ($reportData['total_rows'] ?? 0);
        $no = 0;
        $generatedByName = trim((string) ($reportData['printed_by'] ?? 'sistem'));
    @endphp

    @include('ascends.shared.partials.report-header', [
        'subtitle' => $reportData['per_date'] ?? ''
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 22%;">Nama Customer</th>
                <th style="width: 16%;">Salesman</th>
                <th style="width: 11%;">Tipe</th>
                <th style="width: 10%;">Tanggal</th>
                <th style="width: 13%;">Credit Limit</th>
                <th style="width: 12%;">Kota</th>
                <th style="width: 10%;">Tanggal Modifikasi</th>
                <th style="width: 8%;">Modif By</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sections as $section)
                <tr class="group-header">
                    <td colspan="9">{{ $section['label'] }}</td>
                </tr>
                @foreach ($section['rows'] as $row)
                    @php $no++; @endphp
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $no }}</td>
                        <td>{{ (string) ($row['Nama Customer'] ?? '') }}</td>
                        <td>{{ (string) ($row['Salesman'] ?? '') }}</td>
                        <td>{{ (string) ($row['Tipe'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Tanggal'] ?? '') }}</td>
                        <td class="right">{{ (string) ($row['Credit Limit'] ?? '') }}</td>
                        <td>{{ (string) ($row['Kota'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Tanggal Modifikasi'] ?? '') }}</td>
                        <td>{{ (string) ($row['Dimodifikasi Oleh'] ?? '') }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="9">Sub Total : {{ count($section['rows']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="center" style="font-style: italic; padding: 10px 2px;">
                        Tidak ada data.
                    </td>
                </tr>
            @endforelse
            <tr class="grand-row">
                <td colspan="8" class="center">Total :</td>
                <td class="center">{{ $totalRows }}</td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table', [
        'generatedByName' => $generatedByName,
    ])
</body>

</html>
