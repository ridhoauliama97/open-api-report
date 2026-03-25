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

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
            border: 1px solid #000;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            /* Default: hanya garis vertikal antar kolom. */
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th:first-child,
        td:first-child {
            border-left: 0;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            border-bottom: 1px solid #000;
        }

        /* Hilangkan garis horizontal antar baris data. */
        tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .center {
            text-align: center;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
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
        $start = \Carbon\Carbon::parse((string) $startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) $endDate)->locale('id')->translatedFormat('d-M-y');

        $fmt = static fn(float $v): string => abs($v) < 0.0000001 ? '' : number_format($v, 4, '.', ',');
        $fmtTotal = static fn(float $v): string => number_format($v, 4, '.', ',');
        $fmtInt = static fn(int $v): string => $v === 0 ? '' : (string) $v;
        $dateLabel = static function (string $key): string {
            try {
                return \Carbon\Carbon::parse($key)->format('d-M-y');
            } catch (\Throwable $exception) {
                return $key;
            }
        };

        $totJabon = 0.0;
        $totKayuL = 0.0;
        $totMc1 = 0.0;
        $totMc2 = 0.0;
        $totStd = 0.0;
        $totAll = 0.0;
        $totJumlahMeja = 0;
        foreach ($rows as $r) {
            $totJumlahMeja += (int) ($r['JumlahMeja'] ?? 0);
            $totJabon += (float) ($r['JABON'] ?? 0.0);
            $totKayuL += (float) ($r['RAMBUNG KAYU L'] ?? 0.0);
            $totMc1 += (float) ($r['RAMBUNG MC 1'] ?? 0.0);
            $totMc2 += (float) ($r['RAMBUNG MC 2'] ?? 0.0);
            $totStd += (float) ($r['RAMBUNG STD'] ?? 0.0);
            $totAll += (float) ($r['Total'] ?? 0.0);
        }
    @endphp

    <h1 class="report-title">Laporan Rekap Produktivitas Sawmill</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <table>
        <thead>
            <tr>
                <th style="width: 70px;">Tanggal</th>
                <th style="width: 72px;">Jumlah Meja</th>
                <th>Jabon</th>
                <th>Rambung Kayu L</th>
                <th>Rambung MC 1</th>
                <th>Rambung MC 2</th>
                <th>Rambung STD</th>
                <th style="width: 72px;">Total</th>
            </tr>
        </thead>
        
        <tfoot>
            <tr class="table-end-line">
                <td colspan="8"></td>
            </tr>
        </tfoot>
        <tbody>
            @php $rowIndex = 0; @endphp

            @forelse ($rows as $row)
                @php
                    $rowIndex++;
                    $tanggal = trim((string) ($row['Tanggal'] ?? ''));
                    $jumlahMeja = (int) ($row['JumlahMeja'] ?? 0);
                    $jabon = (float) ($row['JABON'] ?? 0.0);
                    $kayuL = (float) ($row['RAMBUNG KAYU L'] ?? 0.0);
                    $mc1 = (float) ($row['RAMBUNG MC 1'] ?? 0.0);
                    $mc2 = (float) ($row['RAMBUNG MC 2'] ?? 0.0);
                    $std = (float) ($row['RAMBUNG STD'] ?? 0.0);
                    $total = (float) ($row['Total'] ?? 0.0);
                @endphp
                <tr class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $dateLabel($tanggal) }}</td>
                    <td class="center">{{ $fmtInt($jumlahMeja) }}</td>
                    <td class="number">{{ $fmt($jabon) }}</td>
                    <td class="number">{{ $fmt($kayuL) }}</td>
                    <td class="number">{{ $fmt($mc1) }}</td>
                    <td class="number">{{ $fmt($mc2) }}</td>
                    <td class="number">{{ $fmt($std) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmt($total) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center">Tidak ada data.</td>
                </tr>
            @endforelse

            @if ($rows !== [])
                <tr class="totals-row">
                    <td class="center" style="font-weight: bold;">Total</td>
                    <td class="center" style="font-weight: bold;">{{ $totJumlahMeja === 0 ? '' : $totJumlahMeja }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmtTotal($totJabon) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmtTotal($totKayuL) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmtTotal($totMc1) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmtTotal($totMc2) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmtTotal($totStd) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmtTotal($totAll) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
