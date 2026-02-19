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
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .totals-row td {
            background: #dde4f2;
            font-weight: 700;
        }

        .totals-row td.blank {
            background: transparent;
            font-weight: 400;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
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

        $totals = [
            'awal' => 0.0,
            'adj_out' => 0.0,
            'bs_out' => 0.0,
            'prod_out' => 0.0,
            'total_masuk' => 0.0,
            'adj_inp' => 0.0,
            'bs_inp' => 0.0,
            'cca_prod_inp' => 0.0,
            'lmt_prod_inp' => 0.0,
            'mld_prod_inp' => 0.0,
            'pack_prod_inp' => 0.0,
            'snd_jual' => 0.0,
            'snd_prod_inp' => 0.0,
            'total_keluar' => 0.0,
            'akhir' => 0.0,
        ];

        $subSpec = [
            ['key' => 'BJ', 'label' => 'BJ'],
            ['key' => 'CCAkhir', 'label' => 'CCAkhir'],
            ['key' => 'FJ', 'label' => 'FJ'],
            ['key' => 'Moulding', 'label' => 'Moulding'],
            ['key' => 'Reproses', 'label' => 'Reproses'],
            ['key' => 'Sanding', 'label' => 'Sanding'],
            ['key' => 'WIP', 'label' => 'WIP'],
        ];
        $subTotals = [
            'BJ' => 0.0,
            'CCAkhir' => 0.0,
            'FJ' => 0.0,
            'Moulding' => 0.0,
            'Reproses' => 0.0,
            'Sanding' => 0.0,
            'WIP' => 0.0,
            'Total' => 0.0,
        ];
    @endphp

    <h1 class="report-title">Laporan Mutasi Sanding (m3)</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width:30px">No</th>
                <th rowspan="2" style="width:230px">Jenis</th>
                <th rowspan="2" style="width:65px">Awal</th>
                <th colspan="3">Masuk</th>
                <th rowspan="2" style="width:70px">Total<br>Masuk</th>
                <th colspan="8">Keluar</th>
                <th rowspan="2" style="width:70px">Total<br>Keluar</th>
                <th rowspan="2" style="width:65px">Akhir</th>
            </tr>
            <tr>
                <th style="width:68px">Adj Outp<br>SAND</th>
                <th style="width:68px">BS Outp<br>SAND</th>
                <th style="width:68px">SAND Prod<br>Outp</th>
                <th style="width:68px">Adj Inpt<br>SAND</th>
                <th style="width:68px">BS Inpt<br>SAND</th>
                <th style="width:68px">SAND<br>Jual</th>
                <th style="width:68px">CCAInpt<br>SAND</th>
                <th style="width:68px">LMT Inpt<br>SAND</th>
                <th style="width:68px">MLD Inpt<br>SAND</th>
                <th style="width:68px">PACKInpt<br>SAND</th>
                <th style="width:68px">SAND Inpt<br>SAND</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                @php
                    $awal = $valueFromAliases($row, ['SANDAwal', 'SNDAwal', 'Awal']);
                    $adjOut = $valueFromAliases($row, ['AdjOutputSAND', 'AdjOutSAND', 'AdjOutputSND', 'AdjOutSND']);
                    $bsOut = $valueFromAliases($row, ['BSOutputSAND', 'BSOutSAND', 'BSOutputSND', 'BSOutSND']);
                    $prodOut = $valueFromAliases($row, [
                        'SANDProdOutput',
                        'SANDProdOuput',
                        'SNDProdOutput',
                        'SNDProdOuput',
                    ]);
                    $totalMasuk = $adjOut + $bsOut + $prodOut;

                    $adjInp = $valueFromAliases($row, ['AdjInptSAND', 'AdjInputSAND', 'AdjInptSND', 'AdjInputSND']);
                    $bsInp = $valueFromAliases($row, ['BSInptSAND', 'BSInputSAND', 'BSInptSND', 'BSInputSND']);
                    $sndJual = $valueFromAliases($row, ['SANDJual', 'SNDJual']);
                    $ccaProdInp = $valueFromAliases($row, [
                        'CCAProdInptSAND',
                        'CCAProdInptSand',
                        'CCAProdInptSND',
                        'CCAInptSAND',
                    ]);
                    $lmtProdInp = $valueFromAliases($row, ['LMTProdInptSAND', 'LMTProdInptSand', 'LMTInptSAND']);
                    $mldProdInp = $valueFromAliases($row, ['MLDProdInptSAND', 'MLDProdInptSand', 'MLDInptSAND']);
                    $packProdInp = $valueFromAliases($row, ['PACKProdInptSAND', 'PACKProdInptSand', 'PACKInptSAND']);
                    $sndProdInp = $valueFromAliases($row, ['SANDProdInptSand', 'SANDProdInptSAND', 'SANDInptSAND']);
                    $totalKeluar =
                        $adjInp +
                        $bsInp +
                        $sndJual +
                        $ccaProdInp +
                        $lmtProdInp +
                        $mldProdInp +
                        $packProdInp +
                        $sndProdInp;

                    $akhir = $valueFromAliases($row, ['SANDAkhir', 'SNDAkhir', 'Akhir']);

                    $totals['awal'] += $awal;
                    $totals['adj_out'] += $adjOut;
                    $totals['bs_out'] += $bsOut;
                    $totals['prod_out'] += $prodOut;
                    $totals['total_masuk'] += $totalMasuk;
                    $totals['adj_inp'] += $adjInp;
                    $totals['bs_inp'] += $bsInp;
                    $totals['cca_prod_inp'] += $ccaProdInp;
                    $totals['lmt_prod_inp'] += $lmtProdInp;
                    $totals['mld_prod_inp'] += $mldProdInp;
                    $totals['pack_prod_inp'] += $packProdInp;
                    $totals['snd_jual'] += $sndJual;
                    $totals['snd_prod_inp'] += $sndProdInp;
                    $totals['total_keluar'] += $totalKeluar;
                    $totals['akhir'] += $akhir;
                @endphp
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="label">{{ $row['Jenis'] ?? '' }}</td>
                    <td class="number">{{ $fmt($awal, true) }}</td>
                    <td class="number">{{ $fmt($adjOut, true) }}</td>
                    <td class="number">{{ $fmt($bsOut, true) }}</td>
                    <td class="number">{{ $fmt($prodOut, true) }}</td>
                    <td class="number">{{ $fmt($totalMasuk, true) }}</td>
                    <td class="number">{{ $fmt($adjInp, true) }}</td>
                    <td class="number">{{ $fmt($bsInp, true) }}</td>
                    <td class="number">{{ $fmt($sndJual, true) }}</td>
                    <td class="number">{{ $fmt($ccaProdInp, true) }}</td>
                    <td class="number">{{ $fmt($lmtProdInp, true) }}</td>
                    <td class="number">{{ $fmt($mldProdInp, true) }}</td>
                    <td class="number">{{ $fmt($packProdInp, true) }}</td>
                    <td class="number">{{ $fmt($sndProdInp, true) }}</td>
                    <td class="number">{{ $fmt($totalKeluar, true) }}</td>
                    <td class="number">{{ $fmt($akhir, true) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="17" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
            <tr class="totals-row">
                <td colspan="2" class="blank" style="text-align: center">Total</td>
                <td class="number">{{ $fmt($totals['awal'], true) }}</td>
                <td class="number">{{ $fmt($totals['adj_out'], true) }}</td>
                <td class="number">{{ $fmt($totals['bs_out'], true) }}</td>
                <td class="number">{{ $fmt($totals['prod_out'], true) }}</td>
                <td class="number">{{ $fmt($totals['total_masuk'], true) }}</td>
                <td class="number">{{ $fmt($totals['adj_inp'], true) }}</td>
                <td class="number">{{ $fmt($totals['bs_inp'], true) }}</td>
                <td class="number">{{ $fmt($totals['snd_jual'], true) }}</td>
                <td class="number">{{ $fmt($totals['cca_prod_inp'], true) }}</td>
                <td class="number">{{ $fmt($totals['lmt_prod_inp'], true) }}</td>
                <td class="number">{{ $fmt($totals['mld_prod_inp'], true) }}</td>
                <td class="number">{{ $fmt($totals['pack_prod_inp'], true) }}</td>
                <td class="number">{{ $fmt($totals['snd_prod_inp'], true) }}</td>
                <td class="number">{{ $fmt($totals['total_keluar'], true) }}</td>
                <td class="number">{{ $fmt($totals['akhir'], true) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($subRowsData !== [])
        <div style="margin: 10px 0 6px 0; font-size: 12px; font-weight: 700;">Input Sanding Produksi</div>
        <table style="width: 78%;">
            <thead>
                <tr>
                    <th style="width:30px">No</th>
                    <th style="width:220px">Jenis</th>
                    @foreach ($subSpec as $spec)
                        <th style="width:78px">{{ $spec['label'] }}</th>
                    @endforeach
                    <th style="width:78px">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subRowsData as $row)
                    @php
                        $rowTotal = 0.0;
                    @endphp
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $loop->iteration }}</td>
                        <td class="label">{{ $row['Jenis'] ?? '' }}</td>
                        @foreach ($subSpec as $spec)
                            @php
                                $value = $valueFromAliases($row, [$spec['key']]);
                                $subTotals[$spec['key']] += $value;
                                $rowTotal += $value;
                            @endphp
                            <td class="number">{{ $fmt($value, true) }}</td>
                        @endforeach
                        @php
                            $subTotals['Total'] += $rowTotal;
                        @endphp
                        <td class="number">{{ $fmt($rowTotal, true) }}</td>
                    </tr>
                @endforeach
                <tr class="totals-row">
                    <td colspan="2" class="blank" style="text-align: center">Total</td>
                    @foreach ($subSpec as $spec)
                        <td class="number">{{ $fmt($subTotals[$spec['key']], true) }}</td>
                    @endforeach
                    <td class="number">{{ $fmt($subTotals['Total'], true) }}</td>
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
