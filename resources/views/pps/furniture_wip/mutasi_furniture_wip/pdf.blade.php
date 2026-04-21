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
        $wasteRowsData =
            isset($wasteRows) && is_iterable($wasteRows)
                ? (is_array($wasteRows)
                    ? $wasteRows
                    : collect($wasteRows)->values()->all())
                : [];

        usort(
            $rowsData,
            static fn(array $a, array $b): int => strcmp((string) ($a['Nama'] ?? ''), (string) ($b['Nama'] ?? '')),
        );
        usort(
            $subRowsData,
            static fn(array $a, array $b): int => strcmp((string) ($a['Jenis'] ?? ''), (string) ($b['Jenis'] ?? '')),
        );
        usort(
            $wasteRowsData,
            static fn(array $a, array $b): int => strcmp((string) ($a['Jenis'] ?? ''), (string) ($b['Jenis'] ?? '')),
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
            'OutputInjc' => 0.0,
            'OutHStamp' => 0.0,
            'OutputPKunci' => 0.0,
            'OutputSpan' => 0.0,
            'Masuk' => 0.0,
            'InputBJSort' => 0.0,
            'InputHStamp' => 0.0,
            'InputPack' => 0.0,
            'InputPKunci' => 0.0,
            'InputSpaner' => 0.0,
            'InputBSU' => 0.0,
            'Keluar' => 0.0,
            'Akhir' => 0.0,
        ];

        $subTotals = [
            'BeratInjctBroker' => 0.0,
            'BeratInjctMixer' => 0.0,
            'BeratInjcGili' => 0.0,
            'PcsInjcFWIP' => 0.0,
            'PcsHStamFWIP' => 0.0,
            'PcsPKunciFWIP' => 0.0,
            'PcsSpanFWIP' => 0.0,
            'PcsHStampMaterial' => 0.0,
            'PcsPkncMaterial' => 0.0,
            'PcsSPNMaterial' => 0.0,
            'PcsINJCMaterial' => 0.0,
        ];

        foreach ($subRowsData as $row) {
            $subTotals['BeratInjctBroker'] += $num($row, 'BeratInjctBroker');
            $subTotals['BeratInjctMixer'] += $num($row, 'BeratInjctMixer');
            $subTotals['BeratInjcGili'] += $num($row, 'BeratInjcGili');
            $subTotals['PcsInjcFWIP'] += $num($row, 'PcsInjcFWIP');
            $subTotals['PcsHStamFWIP'] += $num($row, 'PcsHStamFWIP');
            $subTotals['PcsPKunciFWIP'] += $num($row, 'PcsPKunciFWIP');
            $subTotals['PcsSpanFWIP'] += $num($row, 'PcsSpanFWIP');
            $subTotals['PcsHStampMaterial'] += $num($row, 'PcsHStampMaterial');
            $subTotals['PcsPkncMaterial'] += $num($row, 'PcsPkncMaterial');
            $subTotals['PcsSPNMaterial'] += $num($row, 'PcsSPNMaterial');
            $subTotals['PcsINJCMaterial'] += $num($row, 'PcsINJCMaterial');
        }

        $wasteTotal = 0.0;
        foreach ($wasteRowsData as $row) {
            $wasteTotal += $num($row, 'Berat');
        }
    @endphp

    <h1 class="report-title">Laporan Mutasi Furniture WIP</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    <div class="container-fluid">
        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr class="headers-row">
                        <th rowspan="2" style="width: 30px;">No</th>
                        <th rowspan="2" style="width: 220px;">Nama</th>
                        <th rowspan="2" style="width: 70px;">Awal</th>
                        <th colspan="4">Masuk</th>
                        <th rowspan="2" style="width: 70px;">Total<br>Masuk</th>
                        <th colspan="6">Keluar</th>
                        <th rowspan="2" style="width: 70px;">Total<br>Keluar</th>
                        <th rowspan="2" style="width: 70px;">Akhir</th>
                    </tr>
                    <tr class="headers-row">
                        <th style="width: 72px;">Output<br>HStamp</th>
                        <th style="width: 72px;">Output<br>Injc</th>
                        <th style="width: 72px;">Output<br>PKunci</th>
                        <th style="width: 72px;">Output<br>Span</th>
                        <th style="width: 72px;">Input<br>BJSort</th>
                        <th style="width: 72px;">Input<br>HStamp</th>
                        <th style="width: 72px;">Input<br>Pack</th>
                        <th style="width: 72px;">Input<br>PKunci</th>
                        <th style="width: 72px;">Input<br>Spaner</th>
                        <th style="width: 72px;">Input<br>BSU</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($rowsData as $row)
                        @php
                            $awal = $num($row, 'Awal');
                            $outHStamp = $num($row, 'OutHStamp');
                            $outputInjc = $num($row, 'OutputInjc');
                            $outputPKunci = $num($row, 'OutputPKunci');
                            $outputSpan = $num($row, 'OutputSpan');
                            $masuk = $num($row, 'Masuk');
                            $inputBJSort = $num($row, 'InputBJSort');
                            $inputHStamp = $num($row, 'InputHStamp');
                            $inputPack = $num($row, 'InputPack');
                            $inputPKunci = $num($row, 'InputPKunci');
                            $inputSpaner = $num($row, 'InputSpaner');
                            $inputBSU = $num($row, 'InputBSU');
                            $keluar = $num($row, 'Keluar');
                            $akhir = $num($row, 'Akhir');

                            $totals['Awal'] += $awal;
                            $totals['OutHStamp'] += $outHStamp;
                            $totals['OutputInjc'] += $outputInjc;
                            $totals['OutputPKunci'] += $outputPKunci;
                            $totals['OutputSpan'] += $outputSpan;
                            $totals['Masuk'] += $masuk;
                            $totals['InputBJSort'] += $inputBJSort;
                            $totals['InputHStamp'] += $inputHStamp;
                            $totals['InputPack'] += $inputPack;
                            $totals['InputPKunci'] += $inputPKunci;
                            $totals['InputSpaner'] += $inputSpaner;
                            $totals['InputBSU'] += $inputBSU;
                            $totals['Keluar'] += $keluar;
                            $totals['Akhir'] += $akhir;
                        @endphp
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell">{{ $loop->iteration }}</td>
                            <td class="label data-cell">{{ $row['Nama'] ?? '' }}</td>
                            <td class="number data-cell">{{ $fmtMain($awal, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($outHStamp, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($outputInjc, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($outputPKunci, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($outputSpan, true) }}</td>
                            <td class="number data-cell" style="font-weight: bold;">{{ $fmtMain($masuk, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($inputBJSort, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($inputHStamp, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($inputPack, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($inputPKunci, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($inputSpaner, true) }}</td>
                            <td class="number data-cell">{{ $fmtMain($inputBSU, true) }}</td>
                            <td class="number data-cell" style="font-weight: bold;">{{ $fmtMain($keluar, true) }}</td>
                            <td class="number data-cell" style="font-weight: bold;">{{ $fmtMain($akhir, true) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="16" style="text-align:center;">Tidak ada data.</td>
                        </tr>
                    @endforelse
                    @if (!empty($rowsData))
                        <tr class="totals-row">
                            <td colspan="2" style="text-align:center">Total</td>
                            <td class="number">{{ $fmtMain($totals['Awal'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['OutHStamp'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['OutputInjc'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['OutputPKunci'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['OutputSpan'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['Masuk'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['InputBJSort'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['InputHStamp'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['InputPack'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['InputPKunci'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['InputSpaner'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['InputBSU'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['Keluar'], true) }}</td>
                            <td class="number">{{ $fmtMain($totals['Akhir'], true) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    @if (!empty($subRowsData))
        <div class="section-title" style="margin-top: 10px;">Input</div>
        <div class="container-fluid">
            <div class="table-responsive">
                <table class="report-table">
                    <thead>
                        <tr class="headers-row">
                            <th style="width: 30px;">No</th>
                            <th style="width: 190px;">Jenis</th>
                            <th style="width: 75px;">Berat Injct<br>Broker</th>
                            <th style="width: 75px;">Berat Injc<br>Gili</th>
                            <th style="width: 75px;">Berat Injct<br>Mixer</th>
                            <th style="width: 75px;">Pcs Injc<br>FWIP</th>
                            <th style="width: 75px;">Pcs HStam<br>FWIP</th>
                            <th style="width: 75px;">Pcs PKunci<br>FWIP</th>
                            <th style="width: 75px;">Pcs Span<br>FWIP</th>
                            <th style="width: 75px;">Pcs HStamp<br>Material</th>
                            <th style="width: 75px;">Pcs INJC<br>Material</th>
                            <th style="width: 75px;">Pcs PKnc<br>Material</th>
                            <th style="width: 75px;">Pcs SPN<br>Material</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($subRowsData as $row)
                            <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                                <td class="center data-cell">{{ $loop->iteration }}</td>
                                <td class="label data-cell">{{ $row['Jenis'] ?? '' }}</td>
                                <td class="number data-cell">{{ $fmtSub($row['BeratInjctBroker'] ?? null, true) }}</td>
                                <td class="number data-cell">{{ $fmtSub($row['BeratInjcGili'] ?? null, true) }}</td>
                                <td class="number data-cell">{{ $fmtSub($row['BeratInjctMixer'] ?? null, true) }}</td>
                                <td class="number data-cell">{{ $fmtSub($row['PcsInjcFWIP'] ?? null, true) }}</td>
                                <td class="number data-cell">{{ $fmtSub($row['PcsHStamFWIP'] ?? null, true) }}</td>
                                <td class="number data-cell">{{ $fmtSub($row['PcsPKunciFWIP'] ?? null, true) }}</td>
                                <td class="number data-cell">{{ $fmtSub($row['PcsSpanFWIP'] ?? null, true) }}</td>
                                <td class="number data-cell">{{ $fmtSub($row['PcsHStampMaterial'] ?? null, true) }}
                                </td>
                                <td class="number data-cell">{{ $fmtSub($row['PcsINJCMaterial'] ?? null, true) }}</td>
                                <td class="number data-cell">{{ $fmtSub($row['PcsPkncMaterial'] ?? null, true) }}</td>
                                <td class="number data-cell">{{ $fmtSub($row['PcsSPNMaterial'] ?? null, true) }}</td>
                            </tr>
                        @endforeach
                        <tr class="totals-row">
                            <td style="text-align:center;" colspan="2">Total </td>
                            <td class="number">{{ $fmtSub($subTotals['BeratInjctBroker'], true) }}</td>
                            <td class="number">{{ $fmtSub($subTotals['BeratInjcGili'], true) }}</td>
                            <td class="number">{{ $fmtSub($subTotals['BeratInjctMixer'], true) }}</td>
                            <td class="number">{{ $fmtSub($subTotals['PcsInjcFWIP'], true) }}</td>
                            <td class="number">{{ $fmtSub($subTotals['PcsHStamFWIP'], true) }}</td>
                            <td class="number">{{ $fmtSub($subTotals['PcsPKunciFWIP'], true) }}</td>
                            <td class="number">{{ $fmtSub($subTotals['PcsSpanFWIP'], true) }}</td>
                            <td class="number">{{ $fmtSub($subTotals['PcsHStampMaterial'], true) }}</td>
                            <td class="number">{{ $fmtSub($subTotals['PcsINJCMaterial'], true) }}</td>
                            <td class="number">{{ $fmtSub($subTotals['PcsPkncMaterial'], true) }}</td>
                            <td class="number">{{ $fmtSub($subTotals['PcsSPNMaterial'], true) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="section-title">Waste</div>
    <div class="container-fluid">
        <div class="table-responsive" style="width: 370px;">
            <table class="report-table">
                <thead>
                    <tr class="headers-row">
                        <th style="width: 30px;">No</th>
                        <th style="width: 280px;">Jenis</th>
                        <th style="width: 90px;">Berat</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($wasteRowsData as $row)
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell">{{ $loop->iteration }}</td>
                            <td class="label data-cell">{{ $row['Jenis'] ?? '' }}</td>
                            <td class="number data-cell">{{ $fmtSub($row['Berat'] ?? null, true) }}</td>
                        </tr>
                    @empty
                        <tr class="data-row row-even">
                            <td class="data-cell" colspan="2" style="text-align: center;">Tidak ada data waste.
                            </td>
                        </tr>
                    @endforelse
                    <tr class="totals-row">
                        <td style="text-align:center;" colspan="2">Total</td>
                        <td class="number">{{ $fmtSub($wasteTotal, true) }}</td>
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
