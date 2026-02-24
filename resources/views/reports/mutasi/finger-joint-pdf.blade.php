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
            font-size: 14px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 10px 0;
            font-size: 10px;
            color: #636466;
        }

        .section-title {
            margin: 14px 0 6px 0;
            font-size: 12px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: auto;
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

        .footer-wrap {}
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

        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d M Y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d M Y');
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

            return number_format($float, 4, '.', '');
        };

        $totalClass = static fn($value): string => $fmt($value, true) !== '' ? 'value-total' : '';

        $valueOf = static function (array $row, array $keys): float {
            foreach ($keys as $key) {
                $value = $row[$key] ?? null;
                if (is_numeric($value)) {
                    return (float) $value;
                }
            }

            return 0.0;
        };

        $normalizeKey = static function (string $key): string {
            return strtoupper((string) preg_replace('/[^a-zA-Z0-9]/', '', $key));
        };

        $valueFromAliases = static function (array $row, array $aliases) use ($normalizeKey): float {
            $normalized = [];
            foreach ($row as $key => $value) {
                if (!is_string($key)) {
                    continue;
                }

                $normalized[$normalizeKey($key)] = $value;
            }

            foreach ($aliases as $alias) {
                $candidate = $normalized[$normalizeKey($alias)] ?? null;
                if (is_numeric($candidate)) {
                    return (float) $candidate;
                }
            }

            return 0.0;
        };

        $mainTotals = [
            'FJAwal' => 0.0,
            'AdjOutFJ' => 0.0,
            'BSOutFJ' => 0.0,
            'FJProdOut' => 0.0,
            'TotalMasuk' => 0.0,
            'AdjInpFJ' => 0.0,
            'BSInpFJ' => 0.0,
            'FJJual' => 0.0,
            'CCAProdInpt' => 0.0,
            'MldProdInpt' => 0.0,
            'S4SProdInpt' => 0.0,
            'SandProdInpt' => 0.0,
            'TotalKeluar' => 0.0,
            'Akhir' => 0.0,
        ];

        $subTotals = [
            'CCAkhir' => 0.0,
            'S4S' => 0.0,
            'Total' => 0.0,
        ];
    @endphp

    <h1 class="report-title">Laporan Mutasi Finger Joint (m3)</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    <div class="container-fluid">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 30px;">No</th>
                        <th rowspan="2" style="width: 210px;">Jenis</th>
                        <th rowspan="2" style="width: 60px;">FJ Awal</th>
                        <th colspan="3">Masuk</th>
                        <th rowspan="2" style="width: 62px;">Total<br>Masuk</th>
                        <th colspan="7">Keluar</th>
                        <th rowspan="2" style="width: 62px;">Total<br>Keluar</th>
                        <th rowspan="2" style="width: 55px;">Akhir</th>
                    </tr>
                    <tr>
                        <th style="width: 58px;">Adj Out FJ</th>
                        <th style="width: 58px;">BS Out FJ</th>
                        <th style="width: 58px;">FJ Prod Out</th>
                        <th style="width: 58px;">Adj Inp FJ</th>
                        <th style="width: 58px;">BS Inp FJ</th>
                        <th style="width: 58px;">FJ Jual</th>
                        <th style="width: 58px;">CCAProd Inpt</th>
                        <th style="width: 58px;">Mld Prod Inpt</th>
                        <th style="width: 58px;">S4SProd Inpt</th>
                        <th style="width: 58px;">SandProd Inpt</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rowsData as $row)
                        @php
                            $fjAwal = $valueFromAliases($row, ['FJAwal', 'FJ Awal', 'Awal', 'FJ_Awal']);
                            $adjOutFJ = $valueFromAliases($row, [
                                'AdjOutFJ',
                                'AdjOutputFJ',
                                'Adj Out FJ',
                                'AdjOut',
                                'AdjOutputFJ',
                                'AdjOutput',
                            ]);
                            $bsOutFJ = $valueFromAliases($row, [
                                'BSOutFJ',
                                'BSOutputFJ',
                                'BS Out FJ',
                                'BSOut',
                                'BSOutputFJ',
                                'BSOutput',
                            ]);
                            $fjProdOut = $valueFromAliases($row, [
                                'FJProdOut',
                                'FJProdOutput',
                                'FJ Prod Out',
                                'Prod Out FJ',
                                'ProdOutFJ',
                                'ProdOutputFJ',
                                'FJ Out',
                                'Out FJ',
                                'FJMasuk',
                                'Masuk',
                            ]);
                            $totalMasukDirect = $valueFromAliases($row, ['TotalMasuk', 'Total Masuk', 'TTL Masuk']);
                            $totalMasuk =
                                $totalMasukDirect !== 0.0 ? $totalMasukDirect : $adjOutFJ + $bsOutFJ + $fjProdOut;

                            $adjInpFJ = $valueFromAliases($row, [
                                'AdjInpFJ',
                                'AdjInptFJ',
                                'Adj Inp FJ',
                                'Adj Inpt FJ',
                                'Adj Input FJ',
                                'AdjInp',
                                'AdjInpt',
                                'AdjInputFJ',
                                'AdjInput',
                            ]);
                            $bsInpFJ = $valueFromAliases($row, [
                                'BSInpFJ',
                                'BSInptFJ',
                                'BS Inp FJ',
                                'BS Inpt FJ',
                                'BS Input FJ',
                                'BSInp',
                                'BSInpt',
                                'BSInputFJ',
                                'BSInput',
                            ]);
                            $fjJual = $valueFromAliases($row, ['FJJual', 'FJ Jual', 'Jual FJ', 'JualFJ', 'Jual']);
                            $ccaProdInpt = $valueFromAliases($row, [
                                'CCAProdInpt',
                                'CCAInptFJ',
                                'CCA Input FJ',
                                'CCAProd Input',
                                'CCAProdInput',
                                'CCA Prod Inpt',
                                'CCA Prod Input',
                                'CCA Input',
                                'CCAInput',
                            ]);
                            $mldProdInpt = $valueFromAliases($row, [
                                'MldProdInpt',
                                'MldInptFJ',
                                'Mld Input FJ',
                                'Mld Prod Inpt',
                                'MLDProd Inpt',
                                'MldProdInput',
                                'MLDProdInput',
                                'Mld Prod Input',
                                'MLD Prod Input',
                                'Mld Input',
                                'MLDInput',
                            ]);
                            $s4sProdInpt = $valueFromAliases($row, [
                                'S4SProdInpt',
                                'S4SInptFJ',
                                'S4S Input FJ',
                                'S4SProd Input',
                                'S4SProdInput',
                                'S4S Prod Inpt',
                                'S4S Prod Input',
                                'S4S Input',
                                'S4SInput',
                                'LMTInput',
                            ]);
                            $sandProdInpt = $valueFromAliases($row, [
                                'SandProdInpt',
                                'SandInptFJ',
                                'Sand Input FJ',
                                'Sand Prod Inpt',
                                'SANDProd Inpt',
                                'SandProdInput',
                                'SANDProdInput',
                                'Sand Prod Input',
                                'SAND Prod Input',
                                'Sand Input',
                                'SANDInput',
                            ]);
                            $totalKeluarDirect = $valueFromAliases($row, ['TotalKeluar', 'Total Keluar', 'TTL Keluar']);
                            $totalKeluar =
                                $totalKeluarDirect !== 0.0
                                    ? $totalKeluarDirect
                                    : $adjInpFJ +
                                        $bsInpFJ +
                                        $fjJual +
                                        $ccaProdInpt +
                                        $mldProdInpt +
                                        $s4sProdInpt +
                                        $sandProdInpt;

                            $akhir = $valueFromAliases($row, ['Akhir', 'FJAkhir', 'FJ Akhir']);

                            $mainTotals['FJAwal'] += $fjAwal;
                            $mainTotals['AdjOutFJ'] += $adjOutFJ;
                            $mainTotals['BSOutFJ'] += $bsOutFJ;
                            $mainTotals['FJProdOut'] += $fjProdOut;
                            $mainTotals['TotalMasuk'] += $totalMasuk;
                            $mainTotals['AdjInpFJ'] += $adjInpFJ;
                            $mainTotals['BSInpFJ'] += $bsInpFJ;
                            $mainTotals['FJJual'] += $fjJual;
                            $mainTotals['CCAProdInpt'] += $ccaProdInpt;
                            $mainTotals['MldProdInpt'] += $mldProdInpt;
                            $mainTotals['S4SProdInpt'] += $s4sProdInpt;
                            $mainTotals['SandProdInpt'] += $sandProdInpt;
                            $mainTotals['TotalKeluar'] += $totalKeluar;
                            $mainTotals['Akhir'] += $akhir;
                        @endphp
                        <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center">{{ $loop->iteration }}</td>
                            <td class="label">{{ $row['Jenis'] ?? '' }}</td>
                            <td class="number">{{ $fmt($fjAwal, true) }}</td>
                            <td class="number">{{ $fmt($adjOutFJ, true) }}</td>
                            <td class="number">{{ $fmt($bsOutFJ, true) }}</td>
                            <td class="number">{{ $fmt($fjProdOut, true) }}</td>
                            <td class="number {{ $totalClass($totalMasuk) }}" style="font-weight: 700">
                                {{ $fmt($totalMasuk, true) }}</td>
                            <td class="number">{{ $fmt($adjInpFJ, true) }}</td>
                            <td class="number">{{ $fmt($bsInpFJ, true) }}</td>
                            <td class="number">{{ $fmt($fjJual, true) }}</td>
                            <td class="number">{{ $fmt($ccaProdInpt, true) }}</td>
                            <td class="number">{{ $fmt($mldProdInpt, true) }}</td>
                            <td class="number">{{ $fmt($s4sProdInpt, true) }}</td>
                            <td class="number">{{ $fmt($sandProdInpt, true) }}</td>
                            <td class="number {{ $totalClass($totalKeluar) }}" style="font-weight: 700">
                                {{ $fmt($totalKeluar, true) }}</td>
                            <td class="number" style="font-weight: 700">{{ $fmt($akhir, true) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="16" class="center">Tidak ada data.</td>
                        </tr>
                    @endforelse
                    <tr class="totals-row">
                        <td colspan="2" class="blank" style="text-align: center">Total</td>
                        <td class="number {{ $totalClass($mainTotals['FJAwal']) }}">
                            {{ $fmt($mainTotals['FJAwal'], true) }}</td>
                        <td class="number {{ $totalClass($mainTotals['AdjOutFJ']) }}">
                            {{ $fmt($mainTotals['AdjOutFJ'], true) }}</td>
                        <td class="number {{ $totalClass($mainTotals['BSOutFJ']) }}">
                            {{ $fmt($mainTotals['BSOutFJ'], true) }}</td>
                        <td class="number {{ $totalClass($mainTotals['FJProdOut']) }}">
                            {{ $fmt($mainTotals['FJProdOut'], true) }}</td>
                        <td class="number {{ $totalClass($mainTotals['TotalMasuk']) }}" style="font-weight: 700">
                            {{ $fmt($mainTotals['TotalMasuk'], true) }}
                        </td>
                        <td class="number {{ $totalClass($mainTotals['AdjInpFJ']) }}">
                            {{ $fmt($mainTotals['AdjInpFJ'], true) }}</td>
                        <td class="number {{ $totalClass($mainTotals['BSInpFJ']) }}">
                            {{ $fmt($mainTotals['BSInpFJ'], true) }}</td>
                        <td class="number {{ $totalClass($mainTotals['FJJual']) }}">
                            {{ $fmt($mainTotals['FJJual'], true) }}</td>
                        <td class="number {{ $totalClass($mainTotals['CCAProdInpt']) }}">
                            {{ $fmt($mainTotals['CCAProdInpt'], true) }}</td>
                        <td class="number {{ $totalClass($mainTotals['MldProdInpt']) }}">
                            {{ $fmt($mainTotals['MldProdInpt'], true) }}</td>
                        <td class="number {{ $totalClass($mainTotals['S4SProdInpt']) }}">
                            {{ $fmt($mainTotals['S4SProdInpt'], true) }}</td>
                        <td class="number {{ $totalClass($mainTotals['SandProdInpt']) }}">
                            {{ $fmt($mainTotals['SandProdInpt'], true) }}</td>
                        <td class="number {{ $totalClass($mainTotals['TotalKeluar']) }}" style="font-weight: 700">
                            {{ $fmt($mainTotals['TotalKeluar'], true) }}</td>
                        <td class="number {{ $totalClass($mainTotals['Akhir']) }}" style="font-weight: 700">
                            {{ $fmt($mainTotals['Akhir'], true) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @if ($subRowsData !== [])
        <div class="section-title">Input FJ Produksi</div>
        <div class="container-fluid" style="width: 70%;">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style="width: 32px;">No</th>
                            <th style="width: 280px; text-align: center;">Jenis</th>
                            <th style="width: 95px;">CCAkhir</th>
                            <th style="width: 95px;">S4S</th>
                            <th style="width: 95px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subRowsData as $row)
                            @php
                                $ccAkhir = $valueFromAliases($row, ['CCAkhir', 'CCA Akhir', 'CCA_Akhir']);
                                $s4s = $valueFromAliases($row, ['S4S', 'S4S FJ', 'S4S_FJ']);
                                $total = $ccAkhir + $s4s;

                                $subTotals['CCAkhir'] += $ccAkhir;
                                $subTotals['S4S'] += $s4s;
                                $subTotals['Total'] += $total;
                            @endphp
                            <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                                <td class="center">{{ $loop->iteration }}</td>
                                <td class="label">{{ $row['Jenis'] ?? '' }}</td>
                                <td class="number">{{ $fmt($ccAkhir, true) }}</td>
                                <td class="number">{{ $fmt($s4s, true) }}</td>
                                <td class="number {{ $totalClass($total) }}" style="font-weight: 700">
                                    {{ $fmt($total, true) }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="totals-row">
                            <td colspan="2" class="blank" style="text-align:center">Total</td>
                            <td class="number {{ $totalClass($subTotals['CCAkhir']) }}">
                                {{ $fmt($subTotals['CCAkhir'], true) }}</td>
                            <td class="number {{ $totalClass($subTotals['S4S']) }}">
                                {{ $fmt($subTotals['S4S'], true) }}</td>
                            <td class="number {{ $totalClass($subTotals['Total']) }}" style="font-weight: 700">
                                {{ $fmt($subTotals['Total'], true) }}</td>
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
