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

        .kd-title {
            margin: 10px 0 6px 0;
            font-weight: bold;
            font-size: 11px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
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

        table.data-table tfoot td {
            border-top: 1px solid #000;
            font-weight: bold;
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
        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse((string) ($startDate ?? ''))->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) ($endDate ?? ''))->locale('id')->translatedFormat('d-M-y');

        $fmtTon = static function ($v): string {
            $n = (float) ($v ?? 0.0);
            if (abs($n) < 0.0000001) {
                return '';
            }
            return number_format($n, 4, '.', ',');
        };

        $fmtDate = static function ($v): string {
            $t = is_string($v) ? trim($v) : '';
            if ($t === '') {
                return '';
            }
            try {
                return \Carbon\Carbon::parse($t)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $e) {
                return $t;
            }
        };

        $calcDays = static function ($in, $out): string {
            $inDate = is_string($in) ? trim($in) : '';
            $outDate = is_string($out) ? trim($out) : '';

            if ($inDate === '' || $outDate === '') {
                return '';
            }

            try {
                return (string) \Carbon\Carbon::parse($outDate)->diffInDays(\Carbon\Carbon::parse($inDate), false);
            } catch (\Throwable $e) {
                return '';
            }
        };
    @endphp

    <h1 class="report-title">Laporan Mutasi KD</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @forelse ($groups as $group)
        @php
            $kd = (int) ($group['no_ruang_kd'] ?? 0);
            $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            $totals = is_array($group['totals'] ?? null) ? $group['totals'] : [];
            $totalDays = 0;

            foreach ($rows as $row) {
                $days = $calcDays($row['TglKeluar'] ?? '', $row['TglMasuk'] ?? '');

                if ($days !== '') {
                    $totalDays += (int) $days;
                }
            }
        @endphp

        <div class="kd-title">No KD : {{ $kd }}</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 20%;">Tanggal (In)</th>
                    <th style="width: 20%;">Ton (In)</th>
                    <th style="width: 20%;">Tanggal (Out)</th>
                    <th style="width: 20%;">Ton (Out)</th>
                    <th style="width: 16%;">Jumlah Hari</th>
                </tr>
            </thead>
            <tbody>
                @php $i = 0; @endphp
                @forelse ($rows as $r)
                    @php $i++; @endphp
                    <tr class="{{ $i % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $i }}</td>
                        <td class="center">{{ $fmtDate($r['TglMasuk'] ?? '') }}</td>
                        <td class="number">{{ $fmtTon($r['TonIn'] ?? 0) }}</td>
                        <td class="center">{{ $fmtDate($r['TglKeluar'] ?? '') }}</td>
                        <td class="number">{{ $fmtTon($r['TonOut'] ?? 0) }}</td>
                        <td class="center">{{ $calcDays($r['TglKeluar'] ?? '', $r['TglMasuk'] ?? '') }} Hari</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="center">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="center" style="font-weight: bold;">Total</td>
                    <td class="number">{{ $fmtTon($totals['ton_in'] ?? 0) }}</td>
                    <td></td>
                    <td class="number">{{ $fmtTon($totals['ton_out'] ?? 0) }}</td>
                    <td class="center">{{ $totalDays }} Hari</td>
                </tr>
            </tfoot>
        </table>
    @empty
        <div class="center">Tidak ada data.</div>
    @endforelse

    @include('reports.partials.pdf-footer-table')
</body>

</html>
