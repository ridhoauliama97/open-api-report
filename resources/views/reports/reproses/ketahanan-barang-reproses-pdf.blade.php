<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 12mm 10mm 14mm 10mm;
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

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            font-size: 10px;
            border: 1px solid #000;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
        }

        table.data-table th,
        table.data-table td {
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        table.data-table th:first-child,
        table.data-table td:first-child {
            border-left: 0;
        }

        table.data-table th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            border-bottom: 1px solid #000;
            background: #fff;
        }

        table.data-table tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        .table-end-line td {
            border-top: 1px solid #000 !important;
            border-right: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: #fff !important;
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

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];

        $start = \Carbon\Carbon::parse((string) ($startDate ?? ''))->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) ($endDate ?? ''))->locale('id')->translatedFormat('d-M-y');

        $eps = 0.0000001;
        $fmt2OrBlank = static function ($v) use ($eps): string {
            if ($v === null) {
                return '';
            }
            if (is_string($v)) {
                $t = trim($v);
                if ($t === '' || $t === '-') {
                    return '';
                }
                $t = str_replace(',', '', $t);
                $v = is_numeric($t) ? (float) $t : 0.0;
            }
            $n = (float) $v;
            if (!is_finite($n) || abs($n) < $eps) {
                return '';
            }
            return number_format($n, 2, '.', '');
        };
    @endphp

    <h1 class="report-title">Laporan Ketahanan Barang Dagang Reproses</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 44%;">Jenis</th>
                <th style="width: 12%;">Stock</th>
                <th style="width: 12%;">Penjualan</th>
                <th style="width: 14%;">Avg Penjualan</th>
                <th style="width: 12%;">Ketahanan</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="table-end-line">
                <td colspan="6"></td>
            </tr>
        </tfoot>
        <tbody>
            @php $i = 0; @endphp
            @forelse ($rows as $r)
                @php $i++; @endphp
                <tr class="{{ $i % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $i }}</td>
                    <td>{{ (string) ($r['Jenis'] ?? '') }}</td>
                    <td class="number">{{ $fmt2OrBlank($r['Stock'] ?? null) }}</td>
                    <td class="number">{{ $fmt2OrBlank($r['Penjualan'] ?? null) }}</td>
                    <td class="number">{{ $fmt2OrBlank($r['AvgPenjualan'] ?? null) }}</td>
                    <td class="number">{{ $fmt2OrBlank($r['Ketahanan'] ?? null) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
