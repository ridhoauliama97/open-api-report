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

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');

        $fmtDate = static function ($v): string {
            $t = is_string($v) ? trim($v) : '';
            if ($t === '') {
                return '';
            }
            try {
                return \Carbon\Carbon::parse((string) $t)->locale('id')->isoFormat('DD-MMM-YY');
            } catch (\Throwable) {
                return $t;
            }
        };

        $fmtDim = static function ($v): string {
            if ($v === null || $v === '') {
                return '';
            }
            $n = is_numeric($v) ? (float) $v : null;
            if ($n === null) {
                $t = is_string($v) ? trim($v) : '';
                return $t;
            }
            return number_format($n, 1, '.', ',');
        };

        $fmtInt = static function ($v): string {
            $n = is_numeric($v) ? (int) $v : 0;
            return $n === 0 ? '' : (string) $n;
        };

        $fmtTon = static function ($v): string {
            $n = is_numeric($v) ? (float) $v : 0.0;
            if (abs($n) < 0.0000001) {
                return '';
            }
            return number_format($n, 4, '.', '');
        };
    @endphp

    <h1 class="report-title">Laporan Label ST (Hidup) Detail</h1>
    <p class="report-subtitle"></p>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 10%;">No ST</th>
                <th style="width: 7%;">Tanggal</th>
                <th style="width: 12%;">No SPK</th>
                <th style="width: 15%;">Jenis</th>
                <th style="width: 6%;">Tebal<br>(mm)</th>
                <th style="width: 6%;">Lebar<br>(mm)</th>
                <th style="width: 8%;">Panjang<br>(ft)</th>
                <th style="width: 12%;">Jmlh Batang<br>(pcs)</th>
                <th style="width: 7%;">Lokasi</th>
                <th style="width: 10%;">Total<br>(Ton)</th>
            </tr>
        </thead>
        <tbody>
            @php $i = 0; @endphp
            @forelse ($rows as $r)
                @php $i++; @endphp
                <tr class="{{ $i % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $i }}</td>
                    <td class="center">{{ $r['NoST'] ?? '' }}</td>
                    <td class="center">{{ $fmtDate($r['Date'] ?? '') }}</td>
                    <td class="center">{{ $r['NoSPK'] ?? '' }}</td>
                    <td>{{ $r['Jenis'] ?? '' }}</td>
                    <td class="number">{{ $fmtDim($r['Tebal'] ?? '') }}</td>
                    <td class="number">{{ $fmtDim($r['Lebar'] ?? '') }}</td>
                    <td class="number">{{ $fmtDim($r['Panjang'] ?? '') }}</td>
                    <td class="number">{{ $fmtInt($r['JmlhBatang'] ?? 0) }}</td>
                    <td class="center">{{ $r['Lokasi'] ?? '' }}</td>
                    <td class="number" style="font-weight: bold">{{ $fmtTon($r['Total'] ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    </body>

</html>