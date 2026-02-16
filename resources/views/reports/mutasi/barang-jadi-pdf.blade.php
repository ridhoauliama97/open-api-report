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
            margin: 24mm 12mm 20mm 12mm;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000;
        }

        .report-title {
            text-align: center;
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 10px 0;
            font-size: 10px;
            color: #555;
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
        }

        th,
        td {
            border: 1px solid #9ca3af;
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
            font-family: "Calibry", "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .totals-row td {
            background: #dde4f2;
        }

        .totals-row td.blank {
            background: transparent;
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

        .footer-wrap {
            position: fixed;
            right: 12mm;
            left: 12mm;
            bottom: 14px;
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
            static fn(array $a, array $b): int => strcmp((string) ($a['Jenis'] ?? ''), (string) ($b['Jenis'] ?? '')),
        );
        usort(
            $subRowsData,
            static fn(array $a, array $b): int => strcmp((string) ($a['Jenis'] ?? ''), (string) ($b['Jenis'] ?? '')),
        );

        $start = \Carbon\Carbon::parse($startDate)->format('d/m/Y');
        $end = \Carbon\Carbon::parse($endDate)->format('d/m/Y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

        $fmt = static function ($value, bool $blankWhenZero = true): string {
            if (!is_numeric($value)) {
                return '';
            }

            $float = (float) $value;
            if ($blankWhenZero && abs($float) < 0.0000001) {
                return '';
            }

            return number_format($float, 4, ',', '.');
        };
        $totalClass = static fn($value): string => $fmt($value) !== '' ? 'value-total' : '';

        $num = static fn(array $row, string $key): float => is_numeric($row[$key] ?? null) ? (float) $row[$key] : 0.0;

        $mainTotals = [
            'Awal' => 0.0,
            'AdjOutput' => 0.0,
            'BSOutput' => 0.0,
            'PackingOutput' => 0.0,
            'TotalMasuk' => 0.0,
            'AdjInput' => 0.0,
            'BSInput' => 0.0,
            'Jual' => 0.0,
            'CCAInput' => 0.0,
            'LMTInput' => 0.0,
            'MLDInput' => 0.0,
            'PackingInput' => 0.0,
            'SANDInput' => 0.0,
            'TotalKeluar' => 0.0,
            'Akhir' => 0.0,
        ];

        $subTotals = [
            'BarangJadi' => 0.0,
            'CCAkhir' => 0.0,
            'Moulding' => 0.0,
            'Sanding' => 0.0,
            'WIP' => 0.0,
            'Total' => 0.0,
        ];
    @endphp

    <h1 class="report-title">Laporan Mutasi Barang Jadi (m3)</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    <div class="container-fluid">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 30px;">No</th>
                        <th rowspan="2" style="width: 210px;">Jenis Kayu</th>
                        <th rowspan="2" style="width: 55px;">Awal</th>
                        <th colspan="3">Masuk</th>
                        <th rowspan="2" style="width: 62px;">Total<br>Masuk</th>
                        <th colspan="8">Keluar</th>
                        <th rowspan="2" style="width: 62px;">Total<br>Keluar</th>
                        <th rowspan="2" style="width: 55px;">Akhir</th>
                    </tr>
                    <tr>
                        <th style="width: 58px;">Adj Output</th>
                        <th style="width: 58px;">B.Susun Output</th>
                        <th style="width: 58px;">Packing Outp</th>
                        <th style="width: 58px;">Adj Input</th>
                        <th style="width: 58px;">B.Susun Input</th>
                        <th style="width: 58px;">Jual</th>
                        <th style="width: 58px;">CCAProd Input</th>
                        <th style="width: 58px;">LMT Prod Input</th>
                        <th style="width: 58px;">MLD Prod Input</th>
                        <th style="width: 58px;">Packing Prod Inpt</th>
                        <th style="width: 58px;">SAND Prod Input</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rowsData as $row)
                        @php
                            $adjOutput = $num($row, 'AdjOutput');
                            $bsOutput = $num($row, 'BSOutput');
                            $packingOutput = $num($row, 'Masuk');
                            $totalMasuk = $adjOutput + $bsOutput + $packingOutput;

                            $adjInput = $num($row, 'AdjInput');
                            $bsInput = $num($row, 'BSInput');
                            $jual = $num($row, 'Jual');
                            $ccaInput = $num($row, 'CCAInput');
                            $lmtInput = $num($row, 'LMTInput');
                            $mldInput = $num($row, 'MLDInput');
                            $packingInput = $num($row, 'Keluar');
                            $sandInput = $num($row, 'SANDInput');
                            $totalKeluar =
                                $adjInput +
                                $bsInput +
                                $jual +
                                $ccaInput +
                                $lmtInput +
                                $mldInput +
                                $packingInput +
                                $sandInput;

                            $mainTotals['Awal'] += $num($row, 'Awal');
                            $mainTotals['AdjOutput'] += $adjOutput;
                            $mainTotals['BSOutput'] += $bsOutput;
                            $mainTotals['PackingOutput'] += $packingOutput;
                            $mainTotals['TotalMasuk'] += $totalMasuk;
                            $mainTotals['AdjInput'] += $adjInput;
                            $mainTotals['BSInput'] += $bsInput;
                            $mainTotals['Jual'] += $jual;
                            $mainTotals['CCAInput'] += $ccaInput;
                            $mainTotals['LMTInput'] += $lmtInput;
                            $mainTotals['MLDInput'] += $mldInput;
                            $mainTotals['PackingInput'] += $packingInput;
                            $mainTotals['SANDInput'] += $sandInput;
                            $mainTotals['TotalKeluar'] += $totalKeluar;
                            $mainTotals['Akhir'] += $num($row, 'Akhir');
                        @endphp
                        <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center">{{ $loop->iteration }}</td>
                            <td class="label">{{ $row['Jenis'] ?? '' }}</td>
                            <td class="number">{{ $fmt($row['Awal'] ?? null) }}</td>
                            <td class="number">{{ $fmt($adjOutput) }}</td>
                            <td class="number">{{ $fmt($bsOutput) }}</td>
                            <td class="number">{{ $fmt($packingOutput) }}</td>
                            <td class="number {{ $totalClass($totalMasuk) }}" style="font-weight: 700">
                                {{ $fmt($totalMasuk) }}</td>
                            <td class="number">{{ $fmt($adjInput) }}</td>
                            <td class="number">{{ $fmt($bsInput) }}</td>
                            <td class="number">{{ $fmt($jual) }}</td>
                            <td class="number">{{ $fmt($ccaInput) }}</td>
                            <td class="number">{{ $fmt($lmtInput) }}</td>
                            <td class="number">{{ $fmt($mldInput) }}</td>
                            <td class="number">{{ $fmt($packingInput) }}</td>
                            <td class="number">{{ $fmt($sandInput) }}</td>
                            <td class="number {{ $totalClass($totalKeluar) }}" style="font-weight: 700">
                                {{ $fmt($totalKeluar) }}</td>
                            <td class="number" style="font-weight: 700">{{ $fmt($row['Akhir'] ?? null) }}</td>
                        </tr>
                    @endforeach
                    <tr class="totals-row">
                        <td colspan="2" class="blank" style="text-align: center">Total</td>
                        <td class="number {{ $totalClass($mainTotals['Awal']) }}">{{ $fmt($mainTotals['Awal']) }}</td>
                        <td class="number {{ $totalClass($mainTotals['AdjOutput']) }}">
                            {{ $fmt($mainTotals['AdjOutput']) }}
                        </td>
                        <td class="number {{ $totalClass($mainTotals['BSOutput']) }}">
                            {{ $fmt($mainTotals['BSOutput']) }}</td>
                        <td class="number {{ $totalClass($mainTotals['PackingOutput']) }}">
                            {{ $fmt($mainTotals['PackingOutput']) }}</td>
                        <td class="number {{ $totalClass($mainTotals['TotalMasuk']) }}" style="font-weight: 700">
                            {{ $fmt($mainTotals['TotalMasuk']) }}
                        </td>
                        <td class="number {{ $totalClass($mainTotals['AdjInput']) }}">
                            {{ $fmt($mainTotals['AdjInput']) }}</td>
                        <td class="number {{ $totalClass($mainTotals['BSInput']) }}">
                            {{ $fmt($mainTotals['BSInput']) }}</td>
                        <td class="number {{ $totalClass($mainTotals['Jual']) }}">{{ $fmt($mainTotals['Jual']) }}</td>
                        <td class="number {{ $totalClass($mainTotals['CCAInput']) }}">
                            {{ $fmt($mainTotals['CCAInput']) }}</td>
                        <td class="number {{ $totalClass($mainTotals['LMTInput']) }}">
                            {{ $fmt($mainTotals['LMTInput']) }}</td>
                        <td class="number {{ $totalClass($mainTotals['MLDInput']) }}">
                            {{ $fmt($mainTotals['MLDInput']) }}</td>
                        <td class="number {{ $totalClass($mainTotals['PackingInput']) }}">
                            {{ $fmt($mainTotals['PackingInput']) }}</td>
                        <td class="number {{ $totalClass($mainTotals['SANDInput']) }}">
                            {{ $fmt($mainTotals['SANDInput']) }}
                        </td>
                        <td class="number {{ $totalClass($mainTotals['TotalKeluar']) }}" style="font-weight: 700">
                            {{ $fmt($mainTotals['TotalKeluar']) }}</td>
                        <td class="number {{ $totalClass($mainTotals['Akhir']) }}" style="font-weight: 700">
                            {{ $fmt($mainTotals['Akhir']) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="section-title">Input Barang Jadi</div>
    <div class="container-fluid" style="width: 70%;">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th style="width: 32px;">No</th>
                        <th style="width: 270px; text-align: center;">Jenis Kayu</th>
                        <th style="width: 95px;">Barang Jadi</th>
                        <th style="width: 95px;">CCAkhir</th>
                        <th style="width: 95px;">Moulding</th>
                        <th style="width: 95px;">Sanding</th>
                        <th style="width: 95px;">WIP</th>
                        <th style="width: 95px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($subRowsData as $row)
                        @php
                            $barangJadi = $num($row, 'BarangJadi');
                            $ccAkhir = $num($row, 'CCAkhir');
                            $moulding = $num($row, 'Moulding');
                            $sanding = $num($row, 'Sanding');
                            $wip = $num($row, 'WIP') + $num($row, 'WIPLama');
                            $total = $barangJadi + $ccAkhir + $moulding + $sanding + $wip;

                            $subTotals['BarangJadi'] += $barangJadi;
                            $subTotals['CCAkhir'] += $ccAkhir;
                            $subTotals['Moulding'] += $moulding;
                            $subTotals['Sanding'] += $sanding;
                            $subTotals['WIP'] += $wip;
                            $subTotals['Total'] += $total;
                        @endphp
                        <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center">{{ $loop->iteration }}</td>
                            <td class="label">{{ $row['Jenis'] ?? '' }}</td>
                            <td class="number">{{ $fmt($barangJadi) }}</td>
                            <td class="number">{{ $fmt($ccAkhir) }}</td>
                            <td class="number">{{ $fmt($moulding) }}</td>
                            <td class="number">{{ $fmt($sanding) }}</td>
                            <td class="number">{{ $fmt($wip) }}</td>
                            <td class="number {{ $totalClass($total) }}" style="font-weight: 700">
                                {{ $fmt($total) }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="totals-row">
                        <td colspan="2" class="blank" style="text-align:center">Total</td>
                        <td class="number {{ $totalClass($subTotals['BarangJadi']) }}">
                            {{ $fmt($subTotals['BarangJadi']) }}
                        </td>
                        <td class="number {{ $totalClass($subTotals['CCAkhir']) }}">{{ $fmt($subTotals['CCAkhir']) }}
                        </td>
                        <td class="number {{ $totalClass($subTotals['Moulding']) }}">
                            {{ $fmt($subTotals['Moulding']) }}</td>
                        <td class="number {{ $totalClass($subTotals['Sanding']) }}">{{ $fmt($subTotals['Sanding']) }}
                        </td>
                        <td class="number {{ $totalClass($subTotals['WIP']) }}">{{ $fmt($subTotals['WIP']) }}</td>
                        <td class="number {{ $totalClass($subTotals['Total']) }}" style="font-weight: 700">
                            {{ $fmt($subTotals['Total']) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer-wrap d-flex justify-content-between align-items-end">
        <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
        <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
    </div>
</body>

</html>
