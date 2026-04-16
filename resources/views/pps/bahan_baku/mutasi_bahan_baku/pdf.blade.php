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

        .footer-wrap {}

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
        usort(
            $rowsData,
            static fn(array $a, array $b): int => strcmp((string) ($a['Jenis'] ?? ''), (string) ($b['Jenis'] ?? '')),
        );

        $generatedByName = $generatedBy->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');

        $detectedColumns = array_keys($rowsData[0] ?? []);

        $toFloat = static function ($value): ?float {
            if (is_numeric($value)) {
                return (float) $value;
            }

            if (!is_string($value)) {
                return null;
            }

            $normalized = trim(str_replace([' ', ','], ['', '.'], $value));

            return is_numeric($normalized) ? (float) $normalized : null;
        };

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
        $num = static function (array $row, string $key) use ($toFloat): float {
            return $toFloat($row[$key] ?? null) ?? 0.0;
        };

        $hasMasukProd = in_array('MasukProd', $detectedColumns, true);
        $hasMasuk = in_array('Masuk', $detectedColumns, true);
        $penerimaanKey = $hasMasukProd ? 'MasukProd' : ($hasMasuk ? 'Masuk' : '');

        $totals = [
            'Awal' => 0.0,
            'BSUOutput' => 0.0,
            'PenerimaanBB' => 0.0,
            'TotalMasuk' => 0.0,
            'BrokerInputBahanBaku' => 0.0,
            'BSUInput' => 0.0,
            'MixerInputBahanBaku' => 0.0,
            'WashInput' => 0.0,
            'TotalKeluar' => 0.0,
            'Akhir' => 0.0,
        ];
    @endphp

    <h1 class="report-title">Laporan Mutasi Bahan Baku</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    <div class="container-fluid">
        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr class="headers-row">
                        <th rowspan="2" style="width: 32px;">No</th>
                        <th rowspan="2" style="width: 220px;">Jenis</th>
                        <th rowspan="2" style="width: 76px;">Awal</th>
                        <th colspan="2">Masuk</th>
                        <th rowspan="2" style="width: 82px;">Total<br>Masuk</th>
                        <th colspan="4">Keluar</th>
                        <th rowspan="2" style="width: 82px;">Total<br>Keluar</th>
                        <th rowspan="2" style="width: 76px;">Akhir</th>
                    </tr>
                    <tr class="headers-row">
                        <th style="width: 80px;">BSU<br>Output</th>
                        <th style="width: 92px;">Penerimaan<br>BB</th>
                        <th style="width: 92px;">Broker Input<br>BB</th>
                        <th style="width: 80px;">BSU Input<br>BB</th>
                        <th style="width: 92px;">Mixer Input<br>BB</th>
                        <th style="width: 92px;">Wash Input<br>BB</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="table-end-line">
                        <td colspan="11"></td>
                    </tr>
                </tfoot>
                <tbody>
                    @forelse ($rowsData as $row)
                        @php
                            $awal = $num($row, 'Awal');
                            $bsuOutput = $num($row, 'BSUOutput');
                            $penerimaanBb = $penerimaanKey !== '' ? $num($row, $penerimaanKey) : 0.0;
                            $totalMasuk = $bsuOutput + $penerimaanBb;
                            $brokerInput = $num($row, 'BrokerInputBahanBaku');
                            $bsuInput = $num($row, 'BSUInput');
                            $mixerInput = $num($row, 'MixerInputBahanBaku');
                            $washInput = $num($row, 'WashInput');
                            $totalKeluar = $brokerInput + $bsuInput + $mixerInput + $washInput;
                            $akhir = $num($row, 'Akhir');

                            $totals['Awal'] += $awal;
                            $totals['BSUOutput'] += $bsuOutput;
                            $totals['PenerimaanBB'] += $penerimaanBb;
                            $totals['TotalMasuk'] += $totalMasuk;
                            $totals['BrokerInputBahanBaku'] += $brokerInput;
                            $totals['BSUInput'] += $bsuInput;
                            $totals['MixerInputBahanBaku'] += $mixerInput;
                            $totals['WashInput'] += $washInput;
                            $totals['TotalKeluar'] += $totalKeluar;
                            $totals['Akhir'] += $akhir;
                        @endphp
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell">{{ $loop->iteration }}</td>
                            <td class="label data-cell">{{ (string) ($row['Jenis'] ?? '') }}</td>
                            <td class="number data-cell">{{ $fmt($awal, true) }}</td>
                            <td class="number data-cell">{{ $fmt($bsuOutput, true) }}</td>
                            <td class="number data-cell">{{ $fmt($penerimaanBb, true) }}</td>
                            <td class="number data-cell">{{ $fmt($totalMasuk, true) }}</td>
                            <td class="number data-cell">{{ $fmt($brokerInput, true) }}</td>
                            <td class="number data-cell">{{ $fmt($bsuInput, true) }}</td>
                            <td class="number data-cell">{{ $fmt($mixerInput, true) }}</td>
                            <td class="number data-cell">{{ $fmt($washInput, true) }}</td>
                            <td class="number data-cell">{{ $fmt($totalKeluar, true) }}</td>
                            <td class="number data-cell">{{ $fmt($akhir, true) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" style="text-align:center;">Tidak ada data.</td>
                        </tr>
                    @endforelse
                    @if (!empty($rowsData))
                        <tr class="totals-row">
                            <td colspan="2" style="text-align:center">Total</td>
                            <td class="number">{{ $fmt($totals['Awal'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BSUOutput'], true) }}</td>
                            <td class="number">{{ $fmt($totals['PenerimaanBB'], true) }}</td>
                            <td class="number">{{ $fmt($totals['TotalMasuk'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BrokerInputBahanBaku'], true) }}</td>
                            <td class="number">{{ $fmt($totals['BSUInput'], true) }}</td>
                            <td class="number">{{ $fmt($totals['MixerInputBahanBaku'], true) }}</td>
                            <td class="number">{{ $fmt($totals['WashInput'], true) }}</td>
                            <td class="number">{{ $fmt($totals['TotalKeluar'], true) }}</td>
                            <td class="number">{{ $fmt($totals['Akhir'], true) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap d-flex justify-content-between align-items-end">
            <div class="footer-left">Dicetak oleh {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
