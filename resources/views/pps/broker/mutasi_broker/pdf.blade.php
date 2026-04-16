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
            static fn(array $a, array $b): int => strcmp((string) ($a['Jenis'] ?? ''), (string) ($b['Jenis'] ?? '')),
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
            'OutputBSU' => 0.0,
            'OutputBroker' => 0.0,
            'BeratMasuk' => 0.0,
            'InputBSU' => 0.0,
            'InputBroker' => 0.0,
            'InputInject' => 0.0,
            'InputMixer' => 0.0,
            'BeratKeluar' => 0.0,
            'BeratAkhir' => 0.0,
        ];

        $subInputTotals = [
            'InputBroker' => 0.0,
            'InputBahanBaku' => 0.0,
            'InputCrusher' => 0.0,
            'InputGilingan' => 0.0,
            'InputMixer' => 0.0,
            'InputWashing' => 0.0,
            'InputReject' => 0.0,
        ];

        foreach ($subRowsData as $row) {
            $subInputTotals['InputBroker'] += $num($row, 'InputBroker');
            $subInputTotals['InputBahanBaku'] += $num($row, 'InputBahanBaku');
            $subInputTotals['InputCrusher'] += $num($row, 'InputCrusher');
            $subInputTotals['InputGilingan'] += $num($row, 'InputGilingan');
            $subInputTotals['InputMixer'] += $num($row, 'InputMixer');
            $subInputTotals['InputWashing'] += $num($row, 'InputWashing');
            $subInputTotals['InputReject'] += $num($row, 'InputReject');
        }

        $wasteTotal = 0.0;
        foreach ($wasteRowsData as $row) {
            $wasteTotal += $num($row, 'OutputWaste');
        }
    @endphp

    <h1 class="report-title">Laporan Mutasi Broker</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    <div class="container-fluid">
        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr class="headers-row">
                        <th rowspan="2" style="width: 30px;">No</th>
                        <th rowspan="2" style="width: 220px;">Jenis</th>
                        <th rowspan="2" style="width: 85px;">Berat<br>Awal</th>
                        <th colspan="2">Masuk</th>
                        <th rowspan="2" style="width: 85px;">Total<br>Masuk</th>
                        <th colspan="4">Keluar</th>
                        <th rowspan="2" style="width: 85px;">Total<br>Keluar</th>
                        <th rowspan="2" style="width: 85px;">Berat<br>Akhir</th>
                    </tr>
                    <tr class="headers-row">
                        <th style="width: 80px;">Output<br>BSU</th>
                        <th style="width: 80px;">Output<br>Broker</th>
                        <th style="width: 80px;">Input<br>BSU</th>
                        <th style="width: 80px;">Input<br>Broker</th>
                        <th style="width: 80px;">Input<br>Inject</th>
                        <th style="width: 80px;">Input<br>Mixer</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="table-end-line">
                        <td colspan="12"></td>
                    </tr>
                </tfoot>
                <tbody>
                    @forelse ($rowsData as $row)
                        @php
                            $beratAwal = $num($row, 'BeratAwal');
                            $outputBsu = $num($row, 'OutputBSU');
                            $outputBroker = $num($row, 'OutputBroker');
                            $beratMasuk = $num($row, 'BeratMasuk');
                            $inputBsu = $num($row, 'InputBSU');
                            $inputBroker = $num($row, 'InputBroker');
                            $inputInject = $num($row, 'InputInject');
                            $inputMixer = $num($row, 'InputMixer');
                            $beratKeluar = $num($row, 'BeratKeluar');
                            $beratAkhir = $num($row, 'BeratAkhir');

                            $totals['BeratAwal'] += $beratAwal;
                            $totals['OutputBSU'] += $outputBsu;
                            $totals['OutputBroker'] += $outputBroker;
                            $totals['BeratMasuk'] += $beratMasuk;
                            $totals['InputBSU'] += $inputBsu;
                            $totals['InputBroker'] += $inputBroker;
                            $totals['InputInject'] += $inputInject;
                            $totals['InputMixer'] += $inputMixer;
                            $totals['BeratKeluar'] += $beratKeluar;
                            $totals['BeratAkhir'] += $beratAkhir;
                        @endphp
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell">{{ $loop->iteration }}</td>
                            <td class="label data-cell">{{ $row['Jenis'] ?? '' }}</td>
                            <td class="number data-cell">{{ $fmt($beratAwal, true) }}</td>
                            <td class="number data-cell">{{ $fmt($outputBsu, true) }}</td>
                            <td class="number data-cell">{{ $fmt($outputBroker, true) }}</td>
                            <td class="number data-cell">{{ $fmt($beratMasuk, true) }}</td>
                            <td class="number data-cell">{{ $fmt($inputBsu, true) }}</td>
                            <td class="number data-cell">{{ $fmt($inputBroker, true) }}</td>
                            <td class="number data-cell">{{ $fmt($inputInject, true) }}</td>
                            <td class="number data-cell">{{ $fmt($inputMixer, true) }}</td>
                            <td class="number data-cell">{{ $fmt($beratKeluar, true) }}</td>
                            <td class="number data-cell">{{ $fmt($beratAkhir, true) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" style="text-align:center;">Tidak ada data.</td>
                        </tr>
                    @endforelse
                    @if (!empty($rowsData))
                        <tr class="totals-row">
                            <td colspan="2" style="text-align:center">Total</td>
                            <td class="number">{{ $fmt($totals['BeratAwal'], true) }}</td>
                            <td class="number">{{ $fmt($totals['OutputBSU'], true) }}</td>
                            <td class="number">{{ $fmt($totals['OutputBroker'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BeratMasuk'], true) }}</td>
                            <td class="number">{{ $fmt($totals['InputBSU'], true) }}</td>
                            <td class="number">{{ $fmt($totals['InputBroker'], true) }}</td>
                            <td class="number">{{ $fmt($totals['InputInject'], true) }}</td>
                            <td class="number">{{ $fmt($totals['InputMixer'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BeratKeluar'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BeratAkhir'], true) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    @if (!empty($subRowsData))
        <div class="section-title" style="margin-top: 20px;">Input</div>
        <div class="container-fluid">
            <div class="table-responsive">
                <table class="report-table">
                    <thead>
                        <tr class="headers-row">
                            <th style="width: 240px;">Jenis</th>
                            <th style="width: 85px;">Input<br>Broker</th>
                            <th style="width: 95px;">Input Bahan<br>Baku</th>
                            <th style="width: 85px;">Input<br>Crusher</th>
                            <th style="width: 85px;">Input<br>Gilingan</th>
                            <th style="width: 85px;">Input<br>Mixer</th>
                            <th style="width: 85px;">Input<br>Washing</th>
                            <th style="width: 85px;">Input<br>Reject</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="table-end-line">
                            <td colspan="8"></td>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach ($subRowsData as $row)
                            <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                                <td class="label data-cell">{{ $row['Jenis'] ?? '' }}</td>
                                <td class="number data-cell">{{ $fmt($row['InputBroker'] ?? null, true) }}</td>
                                <td class="number data-cell">{{ $fmt($row['InputBahanBaku'] ?? null, true) }}</td>
                                <td class="number data-cell">{{ $fmt($row['InputCrusher'] ?? null, true) }}</td>
                                <td class="number data-cell">{{ $fmt($row['InputGilingan'] ?? null, true) }}</td>
                                <td class="number data-cell">{{ $fmt($row['InputMixer'] ?? null, true) }}</td>
                                <td class="number data-cell">{{ $fmt($row['InputWashing'] ?? null, true) }}</td>
                                <td class="number data-cell">{{ $fmt($row['InputReject'] ?? null, true) }}</td>
                            </tr>
                        @endforeach
                        <tr class="totals-row">
                            <td style="text-align:center;">Total</td>
                            <td class="number">{{ $fmt($subInputTotals['InputBroker'], true) }}</td>
                            <td class="number">{{ $fmt($subInputTotals['InputBahanBaku'], true) }}</td>
                            <td class="number">{{ $fmt($subInputTotals['InputCrusher'], true) }}</td>
                            <td class="number">{{ $fmt($subInputTotals['InputGilingan'], true) }}</td>
                            <td class="number">{{ $fmt($subInputTotals['InputMixer'], true) }}</td>
                            <td class="number">{{ $fmt($subInputTotals['InputWashing'], true) }}</td>
                            <td class="number">{{ $fmt($subInputTotals['InputReject'], true) }}</td>
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
                            <td class="number data-cell">{{ $fmt($row['OutputWaste'] ?? null, true) }}</td>
                        </tr>
                    @empty
                        <tr class="data-row row-even">
                            <td class="data-cell" colspan="2" style="text-align: center;">Tidak ada data waste.
                            </td>
                        </tr>
                    @endforelse
                    <tr class="totals-row">
                        <td style="text-align:center;">Total</td>
                        <td class="number">{{ $fmt($wasteTotal, true) }}</td>
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
