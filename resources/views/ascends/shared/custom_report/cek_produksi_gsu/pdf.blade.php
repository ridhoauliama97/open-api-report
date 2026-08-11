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

        .section-header {
            font-size: 11px;
            font-weight: bold;
            font-style: italic;
            margin-bottom: 2px;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            color: #9c111d;
            font-weight: bold;
            font-size: 11px;
            padding: 3px 4px;
        }
    </style>
</head>

<body>
    @php
        $sections = $reportData['sections'] ?? [];
        $totalRows = (int) ($reportData['total_rows'] ?? 0);

        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $startDate = trim((string) ($reportData['start_date'] ?? ''));
        $endDate = trim((string) ($reportData['end_date'] ?? ''));
        $headerSubtitle = '';
        if ($startDate !== '' && $endDate !== '') {
            $headerSubtitle = 'Dari ' . $startDate . ' s/d ' . $endDate;
        }

        if (!function_exists('fmtAmt_cek_produksi_gsu')) {
            function fmtAmt_cek_produksi_gsu($value)
            {
                $v = (float) $value;
                if ($v == 0.0) {
                    return '-';
                }
                return number_format($v, 2, '.', ',');
            }
        }

        $globalRow = 0;
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    @if ($headerSubtitle !== '')
        <p class="report-subtitle">{{ $headerSubtitle }}</p>
    @endif

    @if (count($sections) > 0)
        @foreach ($sections as $section)
            <p class="section-header" @if(!$loop->first) style="page-break-before: always;" @endif>
                {{ $section['category_name'] ?? '' }}
            </p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 12%;">Kode Barang</th>
                        <th style="width: 39%;">Nama Barang</th>
                        {{-- <th style="width: 14%;">Kategori</th> --}}
                        <th style="width: 19%;">Family</th>
                        <th style="width: 12%;">Saldo Awal</th>
                        <th style="width: 9%;">Qty Sales</th>
                        <th style="width: 9%;">Qty Prod</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($section['rows'] as $row)
                        @php $globalRow++; @endphp
                        <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                            <td class="center nowrap">{{ $row['item_code'] ?? '' }}</td>
                            <td>{{ $row['item_name'] ?? '' }}</td>
                            {{-- <td class="center">{{ $row['category_name'] ?? '' }}</td> --}}
                            <td class="center">{{ $row['family_name'] ?? '' }}</td>
                            <td class="number nowrap">{{ fmtAmt_cek_produksi_gsu($row['saldo_awal'] ?? 0) }}</td>
                            <td class="number nowrap">{{ fmtAmt_cek_produksi_gsu($row['qty_sales'] ?? 0) }}</td>
                            <td class="number nowrap">{{ fmtAmt_cek_produksi_gsu($row['qty_prod'] ?? 0) }}</td>
                        </tr>
                    @endforeach

                    @if ($totalRows <= 0)
                        <tr class="empty-row">
                            <td colspan="7">Tidak ada data laporan item tidak ada penjualan dan tidak ada produksi.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endforeach
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td>Tidak ada data laporan item tidak ada penjualan dan tidak ada produksi.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>