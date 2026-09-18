<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    @include('reports.partials.pdf-reference-style')
    <style>
        .data-table th, .data-table td {
            border: 1px solid #000;
        }
    </style>
    
    
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];

        $start = \Carbon\Carbon::parse((string) ($startDate ?? ''))->locale('id')->isoFormat('DD-MMM-YY');
        $end = \Carbon\Carbon::parse((string) ($endDate ?? ''))->locale('id')->isoFormat('DD-MMM-YY');

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

    <h1 class="report-title">Laporan Ketahanan Barang Dagang Moulding</h1>
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
                <tr>
                    <td colspan="6" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    </body>

</html>
