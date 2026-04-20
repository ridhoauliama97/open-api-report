<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 18mm 12mm 18mm 12mm;
            footer: html_reportFooter;
        }

        .container-fluid {
            width: 100%;
            padding: 0;
            margin: 0;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 6px;
        }

        .d-flex {
            display: flex;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        .align-items-end {
            align-items: flex-end;
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
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: auto;
            table-layout: fixed;
        }

        .report-table {
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
            background: #ffffff;
            color: #000;
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
            font-size: 11px;
            border: 1px solid #000;
        }

        .footer-left {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            font-size: 8px;
            font-style: italic;
            text-align: right;
        }

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border-top: 0;
            border-bottom: 1px solid #000;
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
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
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $subRowsData =
            isset($subRows) && is_iterable($subRows)
                ? (is_array($subRows)
                    ? $subRows
                    : collect($subRows)->values()->all())
                : [];

        usort(
            $rowsData,
            static fn(array $a, array $b): int => strcmp(
                (string) ($a['NamaBonggolan'] ?? ''),
                (string) ($b['NamaBonggolan'] ?? ''),
            ),
        );
        usort(
            $subRowsData,
            static fn(array $a, array $b): int => strcmp(
                (string) ($a['NamaBonggolan'] ?? ''),
                (string) ($b['NamaBonggolan'] ?? ''),
            ),
        );

        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $fmt = static function ($value, bool $blankWhenZero = true): string {
            if (!is_numeric($value)) {
                return '';
            }

            $float = (float) $value;
            if ($blankWhenZero && abs($float) < 0.0000001) {
                return '';
            }

            return number_format($float, 2, '.', ',');
        };

        $num = static fn(array $row, string $key): float => is_numeric($row[$key] ?? null) ? (float) $row[$key] : 0.0;

        $totals = [
            'BeratAwal' => 0.0,
            'BeratBROKMasuk' => 0.0,
            'BeratINJCMasuk' => 0.0,
            'BeratADJMasuk' => 0.0,
            'BeratBSUMasuk' => 0.0,
            'MasukNot' => 0.0,
            'BeratMasuk' => 0.0,
            'BeratADJKeluar' => 0.0,
            'BeratBSUKeluar' => 0.0,
            'BeratCRUSKeluar' => 0.0,
            'BeratGILKeluar' => 0.0,
            'KeluarNot' => 0.0,
            'BeratKeluar' => 0.0,
            'BeratAkhir' => 0.0,
        ];

        $subTotal = 0.0;
        foreach ($subRowsData as $row) {
            $subTotal += $num($row, 'Berat');
        }
    @endphp

    <h1 class="report-title">Laporan Mutasi Bonggolan</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    <div class="container-fluid">
        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr class="headers-row">
                        <th rowspan="2" style="width: 30px;">No</th>
                        <th rowspan="2" style="width: 210px;">Nama Bonggolan</th>
                        <th rowspan="2" style="width: 76px;">Awal</th>
                        <th colspan="4">Masuk</th>
                        <th rowspan="2" style="width: 76px;">Total<br>Masuk</th>
                        <th colspan="4">Keluar</th>
                        <th rowspan="2" style="width: 76px;">Total<br>Keluar</th>
                        <th rowspan="2" style="width: 76px;">Akhir</th>
                    </tr>
                    <tr class="headers-row">
                        <th style="width: 72px;">BSU<br>Output</th>
                        <th style="width: 72px;">Broker<br>Output</th>
                        <th style="width: 72px;">Inject<br>Output</th>
                        <th style="width: 72px;">Masuk</th>
                        <th style="width: 72px;">BSU<br>Input</th>
                        <th style="width: 72px;">Crusher<br>Input</th>
                        <th style="width: 72px;">Gilingan<br>Input</th>
                        <th style="width: 72px;">Keluar</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($rowsData as $row)
                        @php
                            $beratAwal = $num($row, 'BeratAwal');
                            $beratBsuMasuk = $num($row, 'BeratBSUMasuk');
                            $beratBrokMasuk = $num($row, 'BeratBROKMasuk');
                            $beratInjcMasuk = $num($row, 'BeratINJCMasuk');
                            $masukNot = $num($row, 'MasukNot');
                            $beratMasuk = $beratBsuMasuk + $beratBrokMasuk + $beratInjcMasuk + $masukNot;
                            $beratBsuKeluar = $num($row, 'BeratBSUKeluar');
                            $beratCrusKeluar = $num($row, 'BeratCRUSKeluar');
                            $beratGilKeluar = $num($row, 'BeratGILKeluar');
                            $keluarNot = $num($row, 'KeluarNot');
                            $beratKeluar = $beratBsuKeluar + $beratCrusKeluar + $beratGilKeluar + $keluarNot;
                            $beratAkhir = $num($row, 'BeratAkhir');

                            $totals['BeratAwal'] += $beratAwal;
                            $totals['BeratBROKMasuk'] += $beratBrokMasuk;
                            $totals['BeratINJCMasuk'] += $beratInjcMasuk;
                            $totals['BeratBSUMasuk'] += $beratBsuMasuk;
                            $totals['MasukNot'] += $masukNot;
                            $totals['BeratMasuk'] += $beratMasuk;
                            $totals['BeratBSUKeluar'] += $beratBsuKeluar;
                            $totals['BeratCRUSKeluar'] += $beratCrusKeluar;
                            $totals['BeratGILKeluar'] += $beratGilKeluar;
                            $totals['KeluarNot'] += $keluarNot;
                            $totals['BeratKeluar'] += $beratKeluar;
                            $totals['BeratAkhir'] += $beratAkhir;
                        @endphp
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell">{{ $loop->iteration }}</td>
                            <td class="label data-cell">{{ $row['NamaBonggolan'] ?? '' }}</td>
                            <td class="number data-cell">{{ $fmt($beratAwal, true) }}</td>
                            <td class="number data-cell">{{ $fmt($beratBsuMasuk, true) }}</td>
                            <td class="number data-cell">{{ $fmt($beratBrokMasuk, true) }}</td>
                            <td class="number data-cell">{{ $fmt($beratInjcMasuk, true) }}</td>
                            <td class="number data-cell">{{ $fmt($masukNot, true) }}</td>
                            <td class="number data-cell" style="font-weight: bold;">{{ $fmt($beratMasuk, true) }}</td>
                            <td class="number data-cell">{{ $fmt($beratBsuKeluar, true) }}</td>
                            <td class="number data-cell">{{ $fmt($beratCrusKeluar, true) }}</td>
                            <td class="number data-cell">{{ $fmt($beratGilKeluar, true) }}</td>
                            <td class="number data-cell">{{ $fmt($keluarNot, true) }}</td>
                            <td class="number data-cell" style="font-weight: bold;">{{ $fmt($beratKeluar, true) }}</td>
                            <td class="number data-cell" style="font-weight: bold;">{{ $fmt($beratAkhir, true) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="16" style="text-align:center;">Tidak ada data.</td>
                        </tr>
                    @endforelse
                    @if (!empty($rowsData))
                        <tr class="totals-row">
                            <td colspan="2" style="text-align:center">Total</td>
                            <td class="number">{{ $fmt($totals['BeratAwal'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BeratBSUMasuk'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BeratBROKMasuk'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BeratINJCMasuk'], true) }}</td>
                            <td class="number">{{ $fmt($totals['MasukNot'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BeratMasuk'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BeratBSUKeluar'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BeratCRUSKeluar'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BeratGILKeluar'], true) }}</td>
                            <td class="number">{{ $fmt($totals['KeluarNot'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BeratKeluar'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BeratAkhir'], true) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- <div class="section-title">Sub Mutasi Bonggolan</div>
    <div class="container-fluid">
        <div class="table-responsive" style="width: 420px;">
            <table class="report-table">
                <thead>
                    <tr class="headers-row">
                        <th style="width: 300px;">Nama Bonggolan</th>
                        <th style="width: 120px;">Berat</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($subRowsData as $row)
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="label data-cell">{{ $row['NamaBonggolan'] ?? '' }}</td>
                            <td class="number data-cell">{{ $fmt($row['Berat'] ?? null, true) }}</td>
                        </tr>
                    @empty
                        <tr class="data-row row-even">
                            <td class="data-cell" colspan="2" style="text-align: center;">Tidak ada data sub mutasi.
                            </td>
                        </tr>
                    @endforelse
                    <tr class="totals-row">
                        <td style="text-align:center;">Total :</td>
                        <td class="number">{{ $fmt($subTotal, true) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div> --}}

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap d-flex justify-content-between align-items-end">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
