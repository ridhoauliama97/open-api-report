<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 24mm 12mm 20mm 12mm;
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
            border-collapse: separate;
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
                (string) ($a['NamaGilingan'] ?? ''),
                (string) ($b['NamaGilingan'] ?? ''),
            ),
        );
        usort(
            $subRowsData,
            static fn(array $a, array $b): int => strcmp((string) ($a['Jenis'] ?? ''), (string) ($b['Jenis'] ?? '')),
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
            'Awal' => 0.0,
            'BeratProdOutput' => 0.0,
            'BeratBSUOutput' => 0.0,
            'BeratMasuk' => 0.0,
            'BrokInput' => 0.0,
            'InjectInput' => 0.0,
            'MixInput' => 0.0,
            'BeratBSUInput' => 0.0,
            'WashInput' => 0.0,
            'BeratKeluar' => 0.0,
            'Akhir' => 0.0,
        ];
        $subTotals = ['BeratBong' => 0.0, 'BeratCrsh' => 0.0, 'BeratRejc' => 0.0];
    @endphp

    <h1 class="report-title">Laporan Mutasi Gilingan</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    <div class="container-fluid">
        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr class="headers-row">
                        <th rowspan="2" style="width: 30px;">No</th>
                        <th rowspan="2" style="width: 230px;">Nama Gilingan</th>
                        <th rowspan="2" style="width: 80px;">Awal</th>
                        <th colspan="2">Masuk</th>
                        <th rowspan="2" style="width: 80px;">Total<br>Masuk</th>
                        <th colspan="5">Keluar</th>
                        <th rowspan="2" style="width: 80px;">Total<br>Keluar</th>
                        <th rowspan="2" style="width: 80px;">Akhir</th>
                    </tr>
                    <tr class="headers-row">
                        <th style="width: 78px;">Prod<br>Output</th>
                        <th style="width: 78px;">BSU<br>Output</th>
                        <th style="width: 78px;">Broker<br>Input</th>
                        <th style="width: 78px;">Inject<br>Input</th>
                        <th style="width: 78px;">Mixer<br>Input</th>
                        <th style="width: 78px;">BSU<br>Input</th>
                        <th style="width: 78px;">Wash<br>Input</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="table-end-line">
                        <td colspan="13"></td>
                    </tr>
                </tfoot>
                <tbody>
                    @forelse ($rowsData as $row)
                        @php
                            $awal = $num($row, 'Awal');
                            $prodOutput = $num($row, 'BeratProdOutput');
                            $bsuOutput = $num($row, 'BeratBSUOutput');
                            $beratMasuk = $num($row, 'BeratMasuk');
                            $brokInput = $num($row, 'BrokInput');
                            $injectInput = $num($row, 'InjectInput');
                            $mixInput = $num($row, 'MixInput');
                            $bsuInput = $num($row, 'BeratBSUInput');
                            $washInput = $num($row, 'WashInput');
                            $beratKeluar = $num($row, 'BeratKeluar');
                            $akhir = $num($row, 'Akhir');
                            $totals['Awal'] += $awal;
                            $totals['BeratProdOutput'] += $prodOutput;
                            $totals['BeratBSUOutput'] += $bsuOutput;
                            $totals['BeratMasuk'] += $beratMasuk;
                            $totals['BrokInput'] += $brokInput;
                            $totals['InjectInput'] += $injectInput;
                            $totals['MixInput'] += $mixInput;
                            $totals['BeratBSUInput'] += $bsuInput;
                            $totals['WashInput'] += $washInput;
                            $totals['BeratKeluar'] += $beratKeluar;
                            $totals['Akhir'] += $akhir;
                        @endphp
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell">{{ $loop->iteration }}</td>
                            <td class="label data-cell">{{ $row['NamaGilingan'] ?? '' }}</td>
                            <td class="number data-cell">{{ $fmt($awal, true) }}</td>
                            <td class="number data-cell">{{ $fmt($prodOutput, true) }}</td>
                            <td class="number data-cell">{{ $fmt($bsuOutput, true) }}</td>
                            <td class="number data-cell">{{ $fmt($beratMasuk, true) }}</td>
                            <td class="number data-cell">{{ $fmt($brokInput, true) }}</td>
                            <td class="number data-cell">{{ $fmt($injectInput, true) }}</td>
                            <td class="number data-cell">{{ $fmt($mixInput, true) }}</td>
                            <td class="number data-cell">{{ $fmt($bsuInput, true) }}</td>
                            <td class="number data-cell">{{ $fmt($washInput, true) }}</td>
                            <td class="number data-cell">{{ $fmt($beratKeluar, true) }}</td>
                            <td class="number data-cell">{{ $fmt($akhir, true) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" style="text-align:center;">Tidak ada data.</td>
                        </tr>
                    @endforelse
                    @if (!empty($rowsData))
                        <tr class="totals-row">
                            <td colspan="2" style="text-align:center">Total</td>
                            <td class="number">{{ $fmt($totals['Awal'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BeratProdOutput'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BeratBSUOutput'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BeratMasuk'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BrokInput'], true) }}</td>
                            <td class="number">{{ $fmt($totals['InjectInput'], true) }}</td>
                            <td class="number">{{ $fmt($totals['MixInput'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BeratBSUInput'], true) }}</td>
                            <td class="number">{{ $fmt($totals['WashInput'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BeratKeluar'], true) }}</td>
                            <td class="number">{{ $fmt($totals['Akhir'], true) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="section-title">Input</div>
    <div class="container-fluid">
        <div class="table-responsive" style="width: 640px;">
            <table class="report-table">
                <thead>
                    <tr class="headers-row">
                        <th style="width: 340px;">Jenis</th>
                        <th style="width: 100px;">Bonggolan</th>
                        <th style="width: 100px;">Crusher</th>
                        <th style="width: 100px;">Reject</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="table-end-line">
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
                <tbody>
                    @forelse ($subRowsData as $row)
                        @php
                            $subTotals['BeratBong'] += $num($row, 'BeratBong');
                            $subTotals['BeratCrsh'] += $num($row, 'BeratCrsh');
                            $subTotals['BeratRejc'] += $num($row, 'BeratRejc');
                        @endphp
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="label data-cell">{{ $row['Jenis'] ?? '' }}</td>
                            <td class="number data-cell">{{ $fmt($row['BeratBong'] ?? null, true) }}</td>
                            <td class="number data-cell">{{ $fmt($row['BeratCrsh'] ?? null, true) }}</td>
                            <td class="number data-cell">{{ $fmt($row['BeratRejc'] ?? null, true) }}</td>
                        </tr>
                    @empty
                        <tr class="data-row row-even">
                            <td class="data-cell" colspan="4" style="text-align: center;">Tidak ada data sub mutasi.
                            </td>
                        </tr>
                    @endforelse
                    <tr class="totals-row">
                        <td style="text-align:center;">Total :</td>
                        <td class="number">{{ $fmt($subTotals['BeratBong'], true) }}</td>
                        <td class="number">{{ $fmt($subTotals['BeratCrsh'], true) }}</td>
                        <td class="number">{{ $fmt($subTotals['BeratRejc'], true) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap d-flex justify-content-between align-items-end">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
