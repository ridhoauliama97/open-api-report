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
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.2;
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

        .section-title {
            margin: 14px 0 6px 0;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: calc(100% - 2px);
            line-height: inherit;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px 2px;
        }

        td.center {
            text-align: center;
        }

        td.label {
            white-space: nowrap;
        }

        td.number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .totals-row td {
            font-weight: bold;
        }

        .headers-row th {
            font-weight: bold;
        }

        .empty-row td {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background: #c9d1df;
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
                <tr class="empty-row">
                    <td colspan="6" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
