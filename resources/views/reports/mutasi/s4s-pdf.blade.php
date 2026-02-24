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

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
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
            'CCAProd' => 0.0,
            'TotalMasuk' => 0.0,
            'AdjInpt' => 0.0,
            'BSInpt' => 0.0,
            'S4SJual' => 0.0,
            'FJInpt' => 0.0,
            'MLDInpt' => 0.0,
            'S4SInpt' => 0.0,
            'TotalKeluar' => 0.0,
            'Akhir' => 0.0,
        ];

        $subSpec = [
            ['key' => 'CCAkhir', 'label' => 'CCAkhir'],
            ['key' => 'FJ', 'label' => 'FJ'],
            ['key' => 'Moulding', 'label' => 'Moulding'],
            ['key' => 'Reproses', 'label' => 'Reproses'],
            ['key' => 'S4S', 'label' => 'S4S'],
            ['key' => 'ST', 'label' => 'ST'],
        ];
        $subTotals = [
            'CCAkhir' => 0.0,
            'FJ' => 0.0,
            'Moulding' => 0.0,
            'Reproses' => 0.0,
            'S4S' => 0.0,
            'ST' => 0.0,
            'Total' => 0.0,
        ];
    @endphp

    <h1 class="report-title">Laporan Mutasi S4S (m3)</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 30px;">No</th>
                <th rowspan="2" style="width: 210px;">Jenis Kayu</th>
                <th rowspan="2" style="width: 55px;">Awal</th>
                <th colspan="4">Masuk</th>
                <th rowspan="2" style="width: 62px;">Total<br>Masuk</th>
                <th colspan="6">Keluar</th>
                <th rowspan="2" style="width: 62px;">Total<br>Keluar</th>
                <th rowspan="2" style="width: 55px;">Akhir</th>
            </tr>
            <tr>
                <th style="width: 58px;">Adj Out S4S</th>
                <th style="width: 58px;">BS Out S4S</th>
                <th style="width: 58px;">Prod Out S4S</th>
                <th style="width: 58px;">CCAProd S4S</th>
                <th style="width: 58px;">Adj Inpt S4S</th>
                <th style="width: 58px;">BS Inpt S4S</th>
                <th style="width: 58px;">S4S Jual</th>
                <th style="width: 58px;">FJ Inpt S4S</th>
                <th style="width: 58px;">Mld Inpt S4S</th>
                <th style="width: 58px;">S4S Inpt S4S</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                @php
                    $awal = $valueFromAliases($row, ['S4SAwal', 'Awal']);
                    $adjOut = $valueFromAliases($row, ['AdjOutputS4S', 'AdjOutptS4S', 'AdjOutS4S']);
                    $bsOut = $valueFromAliases($row, ['BSOutputS4S', 'BSOutptutS4S', 'BSOutptS4S', 'BSOutS4S']);
                    $prodOut = $valueFromAliases($row, ['ProdOutputS4S', 'S4SProdOutput', 'S4SMasuk', 'Masuk']);
                    $ccaProd = $valueFromAliases($row, ['CCAProdOutputS4S', 'CCAProdS4S', 'CCAProdOutS4S', 'CCAProd']);
                    $totalMasukDirect = $valueFromAliases($row, ['TotalMasuk', 'Total Masuk']);
                    $totalMasuk =
                        $totalMasukDirect !== 0.0 ? $totalMasukDirect : $adjOut + $bsOut + $prodOut + $ccaProd;

                    $adjInpt = $valueFromAliases($row, ['AdjInputS4S', 'AdjInptS4S', 'AdjInpt']);
                    $bsInpt = $valueFromAliases($row, ['BsInputS4S', 'BSInputS4S', 'BSInptS4S', 'BSInpt']);
                    $mldJual = $valueFromAliases($row, ['S4SJual', 'JualS4S', 'Jual']);
                    $fjInpt = $valueFromAliases($row, ['FJinputS4S', 'FJInptS4S', 'FJInputS4S', 'FJInpt']);
                    $mldInpt = $valueFromAliases($row, ['MldInputS4S', 'MLDInptS4S', 'MldInptS4S', 'MLDInputS4S']);
                    $s4sInpt = $valueFromAliases($row, ['S4SInputS4S', 'S4SInptS4S', 'S4SInpt']);
                    $totalKeluarDirect = $valueFromAliases($row, ['TotalKeluar', 'Total Keluar']);
                    $totalKeluar =
                        $totalKeluarDirect !== 0.0
                            ? $totalKeluarDirect
                            : $adjInpt + $bsInpt + $mldJual + $fjInpt + $mldInpt + $s4sInpt;

                    $akhir = $valueFromAliases($row, ['AkhirS4S', 'S4SAkhir', 'Akhir']);

                    $mainTotals['Awal'] += $awal;
                    $mainTotals['AdjOut'] += $adjOut;
                    $mainTotals['BSOut'] += $bsOut;
                    $mainTotals['ProdOut'] += $prodOut;
                    $mainTotals['CCAProd'] += $ccaProd;
                    $mainTotals['TotalMasuk'] += $totalMasuk;
                    $mainTotals['AdjInpt'] += $adjInpt;
                    $mainTotals['BSInpt'] += $bsInpt;
                    $mainTotals['S4SJual'] += $mldJual;
                    $mainTotals['FJInpt'] += $fjInpt;
                    $mainTotals['MLDInpt'] += $mldInpt;
                    $mainTotals['S4SInpt'] += $s4sInpt;
                    $mainTotals['TotalKeluar'] += $totalKeluar;
                    $mainTotals['Akhir'] += $akhir;
                @endphp
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="label">{{ $row['Jenis'] ?? '' }}</td>
                    <td class="number">{{ $fmt($awal, true) }}</td>
                    <td class="number">{{ $fmt($adjOut, true) }}</td>
                    <td class="number">{{ $fmt($bsOut, true) }}</td>
                    <td class="number">{{ $fmt($prodOut, true) }}</td>
                    <td class="number">{{ $fmt($ccaProd, true) }}</td>
                    <td class="number" style="font-weight:700">{{ $fmt($totalMasuk, true) }}</td>
                    <td class="number">{{ $fmt($adjInpt, true) }}</td>
                    <td class="number">{{ $fmt($bsInpt, true) }}</td>
                    <td class="number">{{ $fmt($mldJual, true) }}</td>
                    <td class="number">{{ $fmt($fjInpt, true) }}</td>
                    <td class="number">{{ $fmt($mldInpt, true) }}</td>
                    <td class="number">{{ $fmt($s4sInpt, true) }}</td>
                    <td class="number" style="font-weight:700">{{ $fmt($totalKeluar, true) }}</td>
                    <td class="number" style="font-weight:700">{{ $fmt($akhir, true) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
            <tr class="totals-row">
                <td colspan="2" class="blank" style="text-align:center">Total</td>
                <td class="number">{{ $fmt($mainTotals['Awal'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['AdjOut'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['BSOut'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['ProdOut'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['CCAProd'], true) }}</td>
                <td class="number" style="font-weight:700">{{ $fmt($mainTotals['TotalMasuk'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['AdjInpt'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['BSInpt'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['S4SJual'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['FJInpt'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['MLDInpt'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['S4SInpt'], true) }}</td>
                <td class="number" style="font-weight:700">{{ $fmt($mainTotals['TotalKeluar'], true) }}</td>
                <td class="number" style="font-weight:700">{{ $fmt($mainTotals['Akhir'], true) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($subRowsData !== [])
        <div class="section-title">Input S4S Produksi</div>
        <table style="width: 72%;">
            <thead>
                <tr>
                    <th style="width: 32px;">No</th>
                    <th style="width: 220px;">Jenis</th>
                    @foreach ($subSpec as $spec)
                        <th style="width: 84px;">{{ $spec['label'] }}</th>
                    @endforeach
                    <th style="width: 84px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subRowsData as $row)
                    @php
                        $calculatedTotal = 0.0;
                    @endphp
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $loop->iteration }}</td>
                        <td class="label">{{ $row['Jenis'] ?? '' }}</td>
                        @foreach ($subSpec as $spec)
                            @php
                                $aliases = match ($spec['key']) {
                                    'CCAkhir' => ['CCAkhir', 'WIP'],
                                    'Moulding' => ['Moulding', 'MLD'],
                                    default => [$spec['key']],
                                };
                                $value = $valueFromAliases($row, $aliases);
                                $subTotals[$spec['key']] += $value;
                                $calculatedTotal += $value;
                            @endphp
                            <td class="number">{{ $fmt($value, true) }}</td>
                        @endforeach
                        @php
                            $rowTotal = $valueFromAliases($row, ['Total']);
                            $rowTotal = $rowTotal !== 0.0 ? $rowTotal : $calculatedTotal;
                            $subTotals['Total'] += $rowTotal;
                        @endphp
                        <td class="number" style="font-weight: 700">{{ $fmt($rowTotal, true) }}</td>
                    </tr>
                @endforeach
                <tr class="totals-row">
                    <td colspan="2" class="blank" style="text-align:center">Total</td>
                    @foreach ($subSpec as $spec)
                        <td class="number">{{ $fmt($subTotals[$spec['key']], true) }}</td>
                    @endforeach
                    <td class="number" style="font-weight:700">{{ $fmt($subTotals['Total'], true) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
