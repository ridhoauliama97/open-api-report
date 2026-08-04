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
            border-bottom: 1px solid #000;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 1px 2px;
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

        .center {
            text-align: center;
        }

        .left {
            text-align: left;
        }

        .number {
            text-align: right;
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
            color: #9c111d;
            font-weight: bold;
            font-size: 10px;
            padding: 4px;
        }
    </style>
</head>

<body>
    @php
        $rows = $reportData['rows'] ?? [];
        $totalRows = (int) ($reportData['total_rows'] ?? 0);

        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));

        $globalRow = 0;
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    @if ($headerSubtitle !== '')
        <p class="report-subtitle">{{ $headerSubtitle }}</p>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 16%;">Nama Customer</th>
                <th style="width: 14%;">No. SO</th>
                <th style="width: 10%;">Tanggal SO</th>
                <th style="width: 14%;">No. Invoice</th>
                <th style="width: 10%;">Tanggal Invoice</th>
                <th style="width: 10%;">SO Ke SI (Hari)</th>
                <th style="width: 10%;">Tanggal Pelunasan</th>
                <th style="width: 8%;">SI Ke Tagihan (Hari)</th>
                <th style="width: 8%;">Lunas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                @php $globalRow++; @endphp
                <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                    <td class="left">{{ $row['customer_name'] ?? '' }}</td>
                    <td class="center nowrap">{{ $row['so_number'] ?? '' }}</td>
                    <td class="center nowrap">{{ $row['so_date'] ?? '' }}</td>
                    <td class="center nowrap">{{ $row['invoice_number'] ?? '' }}</td>
                    <td class="center nowrap">{{ $row['inv_date'] ?? '' }}</td>
                    <td class="center">{{ $row['so_ke_si'] ?? '' }}</td>
                    <td class="center nowrap">{{ $row['date_pelunas'] ?? '' }}</td>
                    <td class="center">{{ $row['si_ke_tgh'] ?? '' }}</td>
                    <td class="center">{{ $row['lunas'] ?? '' }}</td>
                </tr>
            @endforeach

            @if ($totalRows <= 0)
                <tr class="empty-row">
                    <td colspan="9">Tidak ada data monitoring SO - SI - Tagihan.</td>
                </tr>
            @endif
        </tbody>
    </table>

    @include('ascends.shared.partials.report-footer')
</body>

</html>