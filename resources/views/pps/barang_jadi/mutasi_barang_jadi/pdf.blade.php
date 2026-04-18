<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 20mm 12mm 20mm 12mm;
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
        $wasteRowsData =
            isset($wasteRows) && is_iterable($wasteRows)
                ? (is_array($wasteRows)
                    ? $wasteRows
                    : collect($wasteRows)->values()->all())
                : [];

        usort(
            $rowsData,
            static fn(array $a, array $b): int => strcmp((string) ($a['NamaBJ'] ?? ''), (string) ($b['NamaBJ'] ?? '')),
        );

        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $fmtMain = static function ($value, bool $blankWhenZero = true): string {
            if (!is_numeric($value)) {
                return '';
            }

            $float = (float) $value;
            if ($blankWhenZero && abs($float) < 0.0000001) {
                return '';
            }

            return number_format($float, 2, '.', ',');
        };

        $fmtSub = static function ($value, bool $blankWhenZero = true): string {
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
            'PackOutput' => 0.0,
            'InjectOutput' => 0.0,
            'BSUOutput' => 0.0,
            'ReturOutput' => 0.0,
            'Masuk' => 0.0,
            'BSUInput' => 0.0,
            'BSortInput' => 0.0,
            'BJJual' => 0.0,
            'Keluar' => 0.0,
            'Akhir' => 0.0,
        ];

        $subInputRows = [];

        foreach ($subRowsData as $row) {
            $subInputRows[] = $row;
        }

        $subInputTotals = [
            'BeratBroker' => 0.0,
            'BeratInjctMixer' => 0.0,
            'BeratInjcGili' => 0.0,
            'PcsInjcFWIP' => 0.0,
            'PcsPackFWIP' => 0.0,
            'PcsMatInjc' => 0.0,
            'PcsMatPack' => 0.0,
        ];

        foreach ($subInputRows as $row) {
            $subInputTotals['BeratBroker'] += $num($row, 'BeratBroker');
            $subInputTotals['BeratInjctMixer'] += $num($row, 'BeratInjctMixer');
            $subInputTotals['BeratInjcGili'] += $num($row, 'BeratInjcGili');
            $subInputTotals['PcsInjcFWIP'] += $num($row, 'PcsInjcFWIP');
            $subInputTotals['PcsPackFWIP'] += $num($row, 'PcsPackFWIP');
            $subInputTotals['PcsMatInjc'] += $num($row, 'PcsMatInjc');
            $subInputTotals['PcsMatPack'] += $num($row, 'PcsMatPack');
        }

        $subWasteTotal = 0.0;
        foreach ($wasteRowsData as $row) {
            $subWasteTotal += $num($row, 'Berat');
        }
    @endphp

    <h1 class="report-title">Laporan Mutasi Barang Jadi</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    <div class="container-fluid">
        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr class="headers-row">
                        <th rowspan="2" style="width: 30px;">No</th>
                        <th rowspan="2" style="width: 230px;">Nama Barang Jadi</th>
                        <th rowspan="2" style="width: 55px;">Awal</th>
                        <th colspan="4">Masuk</th>
                        <th rowspan="2" style="width: 62px;">Total<br>Masuk</th>
                        <th colspan="3">Keluar</th>
                        <th rowspan="2" style="width: 62px;">Total<br>Keluar</th>
                        <th rowspan="2" style="width: 55px;">Akhir</th>
                    </tr>
                    <tr class="headers-row">
                        <th style="width: 58px;">Pack<br>Output</th>
                        <th style="width: 58px;">Inject<br>Output</th>
                        <th style="width: 58px;">BSU<br>Output</th>
                        <th style="width: 58px;">Retur<br>Output</th>
                        <th style="width: 58px;">BSU<br>Input</th>
                        <th style="width: 58px;">BSort<br>Input</th>
                        <th style="width: 58px;">BJ<br>Jual</th>
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
                            $packOutput = $num($row, 'PackOutput');
                            $injectOutput = $num($row, 'InjectOutput');
                            $bsuOutput = $num($row, 'BSUOutput');
                            $returOutput = $num($row, 'ReturOutput');
                            $totalMasuk = $num($row, 'Masuk');
                            $bsuInput = $num($row, 'BSUInput');
                            $bsortInput = $num($row, 'BSortInput');
                            $bjJual = $num($row, 'BJJual');
                            $totalKeluar = $num($row, 'Keluar');
                            $akhir = $num($row, 'Akhir');

                            $totals['Awal'] += $awal;
                            $totals['PackOutput'] += $packOutput;
                            $totals['InjectOutput'] += $injectOutput;
                            $totals['BSUOutput'] += $bsuOutput;
                            $totals['ReturOutput'] += $returOutput;
                            $totals['Masuk'] += $totalMasuk;
                            $totals['BSUInput'] += $bsuInput;
                            $totals['BSortInput'] += $bsortInput;
                            $totals['BJJual'] += $bjJual;
                            $totals['Keluar'] += $totalKeluar;
                            $totals['Akhir'] += $akhir;
                        @endphp
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell">{{ $loop->iteration }}</td>
                            <td class="label data-cell">{{ $row['NamaBJ'] ?? '' }}</td>
                            <td class="number data-cell">{{ $fmtMain($awal, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($packOutput, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($injectOutput, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($bsuOutput, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($returOutput, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($totalMasuk, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($bsuInput, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($bsortInput, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($bjJual, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($totalKeluar, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($akhir, true) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" style="text-align:center;">Tidak ada data.</td>
                        </tr>
                    @endforelse
                    @if (!empty($rowsData))
                        <tr class="totals-row">
                            <td colspan="2" style="text-align:center">Total</td>
                            <td class="number">{{ $fmtMain($totals['Awal'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['PackOutput'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['InjectOutput'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['BSUOutput'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['ReturOutput'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['Masuk'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['BSUInput'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['BSortInput'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['BJJual'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['Keluar'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['Akhir'], true) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    @if (!empty($subRowsData))
        <div class="container-fluid">
            @if (!empty($subInputRows))
                <div class="section-title" style="margin-top: 20px;">Input</div>
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr class="headers-row">
                                <th style="width: 280px;">Jenis</th>
                                <th style="width: 78px;">Berat Injct<br>Broker</th>
                                <th style="width: 96px;">Berat Injct<br>Mixer</th>
                                <th style="width: 78px;">Berat Injc<br>Gili</th>
                                <th style="width: 78px;">Pcs Injc<br>FWIP</th>
                                <th style="width: 78px;">Pcs Pack<br>FWIP</th>
                                <th style="width: 78px;">Pcs Injc<br>Material</th>
                                <th style="width: 78px;">Pcs Pack<br>Material</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="table-end-line">
                                <td colspan="8"></td>
                            </tr>
                        </tfoot>
                        <tbody>
                            @foreach ($subInputRows as $row)
                                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                                    <td class="label data-cell">{{ $row['Jenis'] ?? '' }}</td>
                                    <td class="number data-cell">{{ $fmtSub($row['BeratBroker'] ?? null, true) }}</td>
                                    <td class="number data-cell">{{ $fmtSub($row['BeratInjctMixer'] ?? null, true) }}
                                    </td>
                                    <td class="number data-cell">{{ $fmtSub($row['BeratInjcGili'] ?? null, true) }}
                                    </td>
                                    <td class="number data-cell">{{ $fmtSub($row['PcsInjcFWIP'] ?? null, true) }}</td>
                                    <td class="number data-cell">{{ $fmtSub($row['PcsPackFWIP'] ?? null, true) }}</td>
                                    <td class="number data-cell">{{ $fmtSub($row['PcsMatInjc'] ?? null, true) }}</td>
                                    <td class="number data-cell">{{ $fmtSub($row['PcsMatPack'] ?? null, true) }}</td>
                                </tr>
                            @endforeach
                            <tr class="totals-row">
                                <td style="text-align:center;">Total</td>
                                <td class="number">{{ $fmtSub($subInputTotals['BeratBroker'], true) }}</td>
                                <td class="number">{{ $fmtSub($subInputTotals['BeratInjctMixer'], true) }}</td>
                                <td class="number">{{ $fmtSub($subInputTotals['BeratInjcGili'], true) }}</td>
                                <td class="number">{{ $fmtSub($subInputTotals['PcsInjcFWIP'], true) }}</td>
                                <td class="number">{{ $fmtSub($subInputTotals['PcsPackFWIP'], true) }}</td>
                                <td class="number">{{ $fmtSub($subInputTotals['PcsMatInjc'], true) }}</td>
                                <td class="number">{{ $fmtSub($subInputTotals['PcsMatPack'], true) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="section-title">Waste</div>
            <div class="table-responsive" style="width: 370px;">
                <table class="report-table">
                    <thead>
                        <tr class="headers-row">
                            <th style="width: 280px;">Jenis</th>
                            <th style="width: 90px;">Berat</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="table-end-line">
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    <tbody>
                        @forelse ($wasteRowsData as $row)
                            <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                                <td class="label data-cell">{{ $row['Jenis'] ?? '' }}</td>
                                <td class="number data-cell">{{ $fmtSub($row['Berat'] ?? null, true) }}</td>
                            </tr>
                        @empty
                            <tr class="data-row row-even">
                                <td class="data-cell" colspan="2" style="text-align: center;">Tidak ada data
                                    waste.</td>
                            </tr>
                        @endforelse
                        <tr class="totals-row">
                            <td style="text-align:center;">Total</td>
                            <td class="number">{{ $fmtSub($subWasteTotal, false) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap d-flex justify-content-between align-items-end">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
