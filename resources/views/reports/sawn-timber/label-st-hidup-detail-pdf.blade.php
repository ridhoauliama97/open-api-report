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
            font-family: "Noto Serif", "DejaVu Sans", sans-serif;
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
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #000;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
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

        /* Hilangkan garis horizontal antar baris data. */
        table.data-table tbody td {
            border-top: 0;
            border-bottom: 0;
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

        .text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }


        tfoot {
            display: table-footer-group;
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

        @include('reports.partials.pdf-footer-table-style')
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
                return \Carbon\Carbon::parse($t)->format('d-M-y');
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
            return number_format($n, 1, ',', '');
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
                <th style="width: 4%;">No</th>
                <th style="width: 10%;">No ST</th>
                <th style="width: 7%;">Tanggal</th>
                <th style="width: 9%;">No SPK</th>
                <th style="width: 15%;">Jenis</th>
                <th style="width: 6%;">Tebal (mm)</th>
                <th style="width: 6%;">Lebar (mm)</th>
                <th style="width: 6%;">Panjang (ft)</th>
                <th style="width: 15%;">Jmlh Batang (pcs)</th>
                <th style="width: 7%;">Lokasi</th>
                <th style="width: 10%;">Total (Ton)</th>
            </tr>
        </thead>

        <tfoot>
            <tr class="table-end-line">
                <td colspan="11"></td>
            </tr>
        </tfoot>
        <tbody>
            @php $i = 0; @endphp
            @forelse ($rows as $r)
                @php $i++; @endphp
                <tr class="{{ $i % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $i }}</td>
                    <td class="text">{{ $r['NoST'] ?? '' }}</td>
                    <td class="text">{{ $fmtDate($r['Date'] ?? '') }}</td>
                    <td class="text">{{ $r['NoSPK'] ?? '' }}</td>
                    <td class="text">{{ $r['Jenis'] ?? '' }}</td>
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

    @include('reports.partials.pdf-footer-table')
</body>

</html>
