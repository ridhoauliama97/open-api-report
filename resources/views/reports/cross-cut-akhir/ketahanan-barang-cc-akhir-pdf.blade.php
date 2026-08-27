<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    <style>
        body {
            font-family: "Noto Serif", serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .report-companyTitle {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 0 0 4px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 0;
        }
        .report-subtitle {
            font-size: 12px;
            color: #636466;
            text-align: center;
            margin: 2px 0 20px;
        }
        .data-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #000;            padding: 1px 2px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .data-table th {
            background-color: #eef2f8;
            font-weight: bold;
        }
        .section-header td {
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .sub-section-header td {
            font-weight: bold;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .item-row td {
            padding-left: 4px;
        }
        .row-odd td {
            background-color: #c9d1df;
        }
        .row-even td {
            background-color: #eef2f8;
        }
        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .empty-row td {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background-color: #c9d1df;
        }
        .number {
            text-align: right;
        }
        .nowrap {
            white-space: nowrap;
        }
        .number-negative {
            color: #9c111d;
        }
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
                    return '-';
                }
                $t = str_replace(',', '', $t);
                $v = is_numeric($t) ? (float) $t : 0.0;
            }
            $n = (float) $v;
            if (!is_finite($n) || abs($n) < $eps) {
                return '-';
            }
            return number_format($n, 2, '.', '');
        };
    @endphp

    <h1 class="report-title">Laporan Ketahanan Barang Dagang CCAkhir</h1>
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
        <tbody>
            @php $i = 0; @endphp
            @forelse ($rows as $r)
                @php $i++; @endphp
                <tr class="{{ $i % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $i }}</td>
                    <td>{{ (string) ($r['Jenis'] ?? '') }}</td>
                    <td class="center">{{ $fmt2OrBlank($r['Stock'] ?? null) }}</td>
                    <td class="center">{{ $fmt2OrBlank($r['Penjualan'] ?? null) }}</td>
                    <td class="center">{{ $fmt2OrBlank($r['AvgPenjualan'] ?? null) }}</td>
                    <td class="center">{{ $fmt2OrBlank($r['Ketahanan'] ?? null) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    </body>

</html>
