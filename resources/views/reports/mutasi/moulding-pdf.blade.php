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
            margin: 0;
            padding: 0;
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
            width: calc(100% - 2px);
            line-height: inherit;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px 2px;
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
        }

        .headers-row th {
            font-weight: bold;
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

            return number_format($float, 4, '.', ',');
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
            'Awal' => 0.0,
            'AdjOut' => 0.0,
            'BSOut' => 0.0,
            'ProdOut' => 0.0,
            'TotalMasuk' => 0.0,
            'AdjInpt' => 0.0,
            'BSInpt' => 0.0,
            'MLDJual' => 0.0,
            'CCAInpt' => 0.0,
            'LMTInpt' => 0.0,
            'MLDInpt' => 0.0,
            'PACKInpt' => 0.0,
            'SANDInpt' => 0.0,
            'S4SInpt' => 0.0,
            'TotalKeluar' => 0.0,
            'Akhir' => 0.0,
        ];

        $subSpec = [
            ['key' => 'BJ', 'label' => 'BJ'],
            ['key' => 'CCAkhir', 'label' => 'CCAkhir'],
            ['key' => 'FJ', 'label' => 'FJ'],
            ['key' => 'Laminating', 'label' => 'Laminating'],
            ['key' => 'Moulding', 'label' => 'Moulding'],
            ['key' => 'Reproses', 'label' => 'Reproses'],
            ['key' => 'S4S', 'label' => 'S4S'],
            ['key' => 'Sanding', 'label' => 'Sanding'],
            ['key' => 'WIP', 'label' => 'WIP'],
        ];
        $subTotals = [
            'BJ' => 0.0,
            'CCAkhir' => 0.0,
            'FJ' => 0.0,
            'Laminating' => 0.0,
            'Moulding' => 0.0,
            'Reproses' => 0.0,
            'S4S' => 0.0,
            'Sanding' => 0.0,
            'WIP' => 0.0,
            'Total' => 0.0,
        ];
    @endphp

    <h1 class="report-title">Laporan Mutasi Moulding (m3)</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th rowspan="2" style="width: 26px;">No</th>
                <th rowspan="2" style="width: 180px;">Jenis</th>
                <th rowspan="2" style="width: 48px;">Awal</th>
                <th colspan="3">Masuk</th>
                <th rowspan="2" style="width: 56px;">Total<br>Masuk</th>
                <th colspan="9">Keluar</th>
                <th rowspan="2" style="width: 56px;">Total<br>Keluar</th>
                <th rowspan="2" style="width: 48px;">Akhir</th>
            </tr>
            <tr class="headers-row">
                <th style="width: 53px;">Adj Outp MLD</th>
                <th style="width: 53px;">BS Outp MLD</th>
                <th style="width: 53px;">Prod Outp MLD</th>
                <th style="width: 53px;">Adj Inpt MLD</th>
                <th style="width: 53px;">BS Inpt MLD</th>
                <th style="width: 53px;">MLD Jual</th>
                <th style="width: 53px;">CCAInpt MLD</th>
                <th style="width: 53px;">LMT Inpt MLD</th>
                <th style="width: 53px;">MLD Inpt MLD</th>
                <th style="width: 53px;">PACKInpt MLD</th>
                <th style="width: 53px;">SAND Inpt MLD</th>
                <th style="width: 53px;">S4Sinpt MLD</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                @php
                    $awal = $valueFromAliases($row, ['MLDAwal', 'Awal']);
                    $adjOut = $valueFromAliases($row, ['AdjOutputMLD', 'AdjOutptMLD', 'AdjOutMLD']);
                    $bsOut = $valueFromAliases($row, ['BSOutptutMLD', 'BSOutputMLD', 'BSOutptMLD', 'BSOutMLD']);
                    $prodOut = $valueFromAliases($row, ['MLDProdOutput', 'ProdOutputMLD', 'MLDMasuk', 'Masuk']);
                    $totalMasukDirect = $valueFromAliases($row, ['TotalMasuk', 'Total Masuk']);
                    $totalMasuk = $totalMasukDirect !== 0.0 ? $totalMasukDirect : $adjOut + $bsOut + $prodOut;

                    $adjInpt = $valueFromAliases($row, ['AdjInptMLD', 'AdjInputMLD', 'AdjInpt']);
                    $bsInpt = $valueFromAliases($row, ['BSInptMLD', 'BSInputMLD', 'BSInpt']);
                    $mldJual = $valueFromAliases($row, ['MLDJual', 'JualMLD', 'Jual']);
                    $ccaInpt = $valueFromAliases($row, ['CCAInptMLD', 'CCAInputMLD', 'CCAInpt']);
                    $lmtInpt = $valueFromAliases($row, ['LMTInptMLD', 'LMTInputMLD', 'LMTInpt']);
                    $mldInpt = $valueFromAliases($row, ['MLDInptMLD', 'MldInptMLD', 'MLDInputMLD']);
                    $packInpt = $valueFromAliases($row, ['PACKInptMLD', 'PackInptMLD', 'PACKInputMLD']);
                    $sandInpt = $valueFromAliases($row, ['SANDInptMLD', 'SandInptMLD', 'SANDInputMLD']);
                    $s4sInpt = $valueFromAliases($row, ['S4SinptMLD', 'S4SInptMLD', 'S4SInputMLD']);
                    $totalKeluarDirect = $valueFromAliases($row, ['TotalKeluar', 'Total Keluar']);
                    $totalKeluar =
                        $totalKeluarDirect !== 0.0
                        ? $totalKeluarDirect
                        : $adjInpt +
                        $bsInpt +
                        $mldJual +
                        $ccaInpt +
                        $lmtInpt +
                        $mldInpt +
                        $packInpt +
                        $sandInpt +
                        $s4sInpt;

                    $akhir = $valueFromAliases($row, ['MLDAkhir', 'Akhir']);

                    $mainTotals['Awal'] += $awal;
                    $mainTotals['AdjOut'] += $adjOut;
                    $mainTotals['BSOut'] += $bsOut;
                    $mainTotals['ProdOut'] += $prodOut;
                    $mainTotals['TotalMasuk'] += $totalMasuk;
                    $mainTotals['AdjInpt'] += $adjInpt;
                    $mainTotals['BSInpt'] += $bsInpt;
                    $mainTotals['MLDJual'] += $mldJual;
                    $mainTotals['CCAInpt'] += $ccaInpt;
                    $mainTotals['LMTInpt'] += $lmtInpt;
                    $mainTotals['MLDInpt'] += $mldInpt;
                    $mainTotals['PACKInpt'] += $packInpt;
                    $mainTotals['SANDInpt'] += $sandInpt;
                    $mainTotals['S4SInpt'] += $s4sInpt;
                    $mainTotals['TotalKeluar'] += $totalKeluar;
                    $mainTotals['Akhir'] += $akhir;
                @endphp
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center data-cell">{{ $loop->iteration }}</td>
                    <td class="label data-cell">{{ $row['Jenis'] ?? '' }}</td>
                    <td class="number data-cell">{{ $fmt($awal, true) }}</td>
                    <td class="number data-cell">{{ $fmt($adjOut, true) }}</td>
                    <td class="number data-cell">{{ $fmt($bsOut, true) }}</td>
                    <td class="number data-cell">{{ $fmt($prodOut, true) }}</td>
                    <td class="number data-cell" style="font-weight: bold;">{{ $fmt($totalMasuk, true) }}</td>
                    <td class="number data-cell">{{ $fmt($adjInpt, true) }}</td>
                    <td class="number data-cell">{{ $fmt($bsInpt, true) }}</td>
                    <td class="number data-cell">{{ $fmt($mldJual, true) }}</td>
                    <td class="number data-cell">{{ $fmt($ccaInpt, true) }}</td>
                    <td class="number data-cell">{{ $fmt($lmtInpt, true) }}</td>
                    <td class="number data-cell">{{ $fmt($mldInpt, true) }}</td>
                    <td class="number data-cell">{{ $fmt($packInpt, true) }}</td>
                    <td class="number data-cell">{{ $fmt($sandInpt, true) }}</td>
                    <td class="number data-cell">{{ $fmt($s4sInpt, true) }}</td>
                    <td class="number data-cell" style="font-weight: bold;">{{ $fmt($totalKeluar, true) }}</td>
                    <td class="number data-cell" style="font-weight: bold;">{{ $fmt($akhir, true) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="18" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
            <tr class="totals-row">
                <td colspan="2" class="blank" style="text-align:center">Total</td>
                <td class="number">{{ $fmt($mainTotals['Awal'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['AdjOut'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['BSOut'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['ProdOut'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['TotalMasuk'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['AdjInpt'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['BSInpt'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['MLDJual'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['CCAInpt'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['LMTInpt'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['MLDInpt'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['PACKInpt'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['SANDInpt'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['S4SInpt'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['TotalKeluar'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['Akhir'], true) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($subRowsData !== [])
        <div class="section-title">Input Moulding Produksi</div>
        <table class="report-table" style="width: 70%;">
            <thead>
                <tr class="headers-row">
                    <th style="width: 29px;">No</th>
                    <th style="width: 198px;">Jenis</th>
                    @foreach ($subSpec as $spec)
                        <th style="width: 76px;">{{ $spec['label'] }}</th>
                    @endforeach
                    <th style="width: 76px;">Total</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($subRowsData as $row)
                    @php
                        $calculatedTotal = 0.0;
                    @endphp
                    <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center data-cell">{{ $loop->iteration }}</td>
                        <td class="label data-cell">{{ $row['Jenis'] ?? '' }}</td>
                        @foreach ($subSpec as $spec)
                            @php
                                $value = $valueFromAliases($row, [$spec['key']]);
                                $subTotals[$spec['key']] += $value;
                                $calculatedTotal += $value;
                            @endphp
                            <td class="number data-cell">{{ $fmt($value, true) }}</td>
                        @endforeach
                        @php
                            $rowTotal = $valueFromAliases($row, ['Total']);
                            $rowTotal = $rowTotal !== 0.0 ? $rowTotal : $calculatedTotal;
                            $subTotals['Total'] += $rowTotal;
                        @endphp
                        <td class="number data-cell" style="font-weight: bold">{{ $fmt($rowTotal, true) }}</td>
                    </tr>
                @endforeach
                <tr class="totals-row">
                    <td colspan="2" class="blank" style="text-align:center">Total</td>
                    @foreach ($subSpec as $spec)
                        <td class="number">{{ $fmt($subTotals[$spec['key']], true) }}</td>
                    @endforeach
                    <td class="number">{{ $fmt($subTotals['Total'], true) }}</td>
                </tr>
            </tbody>
        </table>
    @endif
</body>

</html>
