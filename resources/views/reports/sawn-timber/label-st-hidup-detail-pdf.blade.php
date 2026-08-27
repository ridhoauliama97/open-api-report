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

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $fmtDate = static function ($v): string {
            $t = is_string($v) ? trim($v) : '';
            if ($t === '') {
                return '';
            }
            try {
                return \Carbon\Carbon::parse((string) $t)->locale('id')->translatedFormat('d-M-y');
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