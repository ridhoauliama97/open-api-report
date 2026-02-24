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
            margin: 24mm 8mm 20mm 8mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 9px;
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
            padding: 2px 3px;
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
            text-align: center;
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
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d M Y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d M Y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

        $toFloat = static function ($value): float {
            if (is_numeric($value)) {
                return (float) $value;
            }

            if (!is_string($value)) {
                return 0.0;
            }

            $normalized = trim($value);
            if ($normalized === '') {
                return 0.0;
            }

            $normalized = str_replace(' ', '', $normalized);

            if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
                if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                    $normalized = str_replace('.', '', $normalized);
                    $normalized = str_replace(',', '.', $normalized);
                } else {
                    $normalized = str_replace(',', '', $normalized);
                }
            } elseif (str_contains($normalized, ',')) {
                if (preg_match('/^-?\d{1,3}(,\d{3})+$/', $normalized) === 1) {
                    $normalized = str_replace(',', '', $normalized);
                } else {
                    $normalized = str_replace(',', '.', $normalized);
                }
            }

            return is_numeric($normalized) ? (float) $normalized : 0.0;
        };

        $valueFromAliases = static function (array $row, array $aliases) use ($toFloat): float {
            foreach ($aliases as $alias) {
                if (!array_key_exists($alias, $row)) {
                    continue;
                }

                return $toFloat($row[$alias]);
            }

            return 0.0;
        };

        $fmt = static function (float $value, bool $blankWhenZero = true): string {
            if ($blankWhenZero && abs($value) < 0.0000001) {
                return '';
            }

            return number_format($value, 4, '.', '');
        };

        $totals = [
            'awal' => 0.0,
            'adj_out' => 0.0,
            'bs_out' => 0.0,
            'prod_out' => 0.0,
            'cca_masuk' => 0.0,
            'total_masuk' => 0.0,
            'adj_in' => 0.0,
            'bs_in' => 0.0,
            'cca_jual' => 0.0,
            'fj_inpt' => 0.0,
            'lmt_inpt' => 0.0,
            'mld_inpt' => 0.0,
            's4s_inpt' => 0.0,
            'sand_inpt' => 0.0,
            'pack_inpt' => 0.0,
            'cca_prod_inpt' => 0.0,
            'total_keluar' => 0.0,
            'akhir' => 0.0,
        ];

        $subTotals = [
            'BJ' => 0.0,
            'CCAkhir' => 0.0,
            'FJ' => 0.0,
            'Laminating' => 0.0,
            'Moulding' => 0.0,
            'Reproses' => 0.0,
            'Sanding' => 0.0,
            'Total' => 0.0,
        ];
    @endphp

    <h1 class="report-title">Laporan Mutasi CC Akhir</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width:30px">No</th>
                <th rowspan="2" style="width:190px">Jenis</th>
                <th rowspan="2" style="width:58px">Awal</th>
                <th colspan="4">Masuk</th>
                <th rowspan="2" style="width:62px">Total<br>Masuk</th>
                <th colspan="10">Keluar</th>
                <th rowspan="2" style="width:62px">Total<br>Keluar</th>
                <th rowspan="2" style="width:58px">Akhir</th>
            </tr>
            <tr>
                <th style="width:58px">Adj Out<br>CCA</th>
                <th style="width:58px">BS Out<br>CCA</th>
                <th style="width:58px">CCA Prod<br>Out</th>
                <th style="width:58px">CCA<br>Masuk</th>
                <th style="width:58px">Adj In<br>CCA</th>
                <th style="width:58px">BS In<br>CCA</th>
                <th style="width:58px">CCA<br>Jual</th>
                <th style="width:58px">FJ Prod<br>Inpt</th>
                <th style="width:58px">LMT Prod<br>Inpt</th>
                <th style="width:58px">Mld Prod<br>Inpt</th>
                <th style="width:58px">S4S Prod<br>Inpt</th>
                <th style="width:58px">Sand Prod<br>Inpt</th>
                <th style="width:58px">Pack Prod<br>Inpt</th>
                <th style="width:58px">CCA Prod<br>Inpt</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                @php
                    $awal = $valueFromAliases($row, ['CCAkhirAwal', 'Awal']);
                    $adjOut = $valueFromAliases($row, ['AdjOutputCCA', 'AdjOutCCA']);
                    $bsOut = $valueFromAliases($row, ['BSOutputCCA', 'BSOutCCA']);
                    $prodOut = $valueFromAliases($row, ['CCAProdOutput', 'CCAProdOut']);
                    $ccaMasuk = $valueFromAliases($row, ['CCAMasuk']);
                    $totalMasuk = $adjOut + $bsOut + $prodOut + $ccaMasuk;

                    $adjIn = $valueFromAliases($row, ['AdjInptCCA', 'AdjInputCCA']);
                    $bsIn = $valueFromAliases($row, ['BSInputCCA', 'BSInptCCA']);
                    $ccaJual = $valueFromAliases($row, ['CCAJual']);
                    $fjInpt = $valueFromAliases($row, ['FJProdInpt']);
                    $lmtInpt = $valueFromAliases($row, ['LMTProdInpt']);
                    $mldInpt = $valueFromAliases($row, ['MldProdinpt', 'MldProdInpt', 'MLDProdInpt']);
                    $s4sInpt = $valueFromAliases($row, ['S4SProdInpt']);
                    $sandInpt = $valueFromAliases($row, ['SandProdInpt', 'SANDProdInpt']);
                    $packInpt = $valueFromAliases($row, ['PACKProdInpt', 'PackProdInpt']);
                    $ccaProdInpt = $valueFromAliases($row, ['CCAInputCCA', 'CCAProdInpt']);
                    $totalKeluar =
                        $adjIn +
                        $bsIn +
                        $ccaJual +
                        $fjInpt +
                        $lmtInpt +
                        $mldInpt +
                        $s4sInpt +
                        $sandInpt +
                        $packInpt +
                        $ccaProdInpt;

                    $akhir = $valueFromAliases($row, ['CCAAkhir', 'Akhir']);

                    $totals['awal'] += $awal;
                    $totals['adj_out'] += $adjOut;
                    $totals['bs_out'] += $bsOut;
                    $totals['prod_out'] += $prodOut;
                    $totals['cca_masuk'] += $ccaMasuk;
                    $totals['total_masuk'] += $totalMasuk;
                    $totals['adj_in'] += $adjIn;
                    $totals['bs_in'] += $bsIn;
                    $totals['cca_jual'] += $ccaJual;
                    $totals['fj_inpt'] += $fjInpt;
                    $totals['lmt_inpt'] += $lmtInpt;
                    $totals['mld_inpt'] += $mldInpt;
                    $totals['s4s_inpt'] += $s4sInpt;
                    $totals['sand_inpt'] += $sandInpt;
                    $totals['pack_inpt'] += $packInpt;
                    $totals['cca_prod_inpt'] += $ccaProdInpt;
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
                    <td class="number">{{ $fmt($ccaMasuk, true) }}</td>
                    <td class="number">{{ $fmt($totalMasuk, true) }}</td>
                    <td class="number">{{ $fmt($adjIn, true) }}</td>
                    <td class="number">{{ $fmt($bsIn, true) }}</td>
                    <td class="number">{{ $fmt($ccaJual, true) }}</td>
                    <td class="number">{{ $fmt($fjInpt, true) }}</td>
                    <td class="number">{{ $fmt($lmtInpt, true) }}</td>
                    <td class="number">{{ $fmt($mldInpt, true) }}</td>
                    <td class="number">{{ $fmt($s4sInpt, true) }}</td>
                    <td class="number">{{ $fmt($sandInpt, true) }}</td>
                    <td class="number">{{ $fmt($packInpt, true) }}</td>
                    <td class="number">{{ $fmt($ccaProdInpt, true) }}</td>
                    <td class="number">{{ $fmt($totalKeluar, true) }}</td>
                    <td class="number">{{ $fmt($akhir, true) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="20" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
            <tr class="totals-row">
                <td colspan="2" class="blank">Total</td>
                <td class="number">{{ $fmt($totals['awal'], true) }}</td>
                <td class="number">{{ $fmt($totals['adj_out'], true) }}</td>
                <td class="number">{{ $fmt($totals['bs_out'], true) }}</td>
                <td class="number">{{ $fmt($totals['prod_out'], true) }}</td>
                <td class="number">{{ $fmt($totals['cca_masuk'], true) }}</td>
                <td class="number">{{ $fmt($totals['total_masuk'], true) }}</td>
                <td class="number">{{ $fmt($totals['adj_in'], true) }}</td>
                <td class="number">{{ $fmt($totals['bs_in'], true) }}</td>
                <td class="number">{{ $fmt($totals['cca_jual'], true) }}</td>
                <td class="number">{{ $fmt($totals['fj_inpt'], true) }}</td>
                <td class="number">{{ $fmt($totals['lmt_inpt'], true) }}</td>
                <td class="number">{{ $fmt($totals['mld_inpt'], true) }}</td>
                <td class="number">{{ $fmt($totals['s4s_inpt'], true) }}</td>
                <td class="number">{{ $fmt($totals['sand_inpt'], true) }}</td>
                <td class="number">{{ $fmt($totals['pack_inpt'], true) }}</td>
                <td class="number">{{ $fmt($totals['cca_prod_inpt'], true) }}</td>
                <td class="number">{{ $fmt($totals['total_keluar'], true) }}</td>
                <td class="number">{{ $fmt($totals['akhir'], true) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($subRowsData !== [])
        <div style="font-size: 11px; font-weight: 700; margin: 10px 0 4px 0;">Input Cross Cut Akhir Produksi</div>
        <table style="width: 92%;">
            <thead>
                <tr>
                    <th style="width:30px">No</th>
                    <th style="width:210px">Jenis</th>
                    <th style="width:70px">BJ</th>
                    <th style="width:70px">CCAkhir</th>
                    <th style="width:70px">FJ</th>
                    <th style="width:70px">Laminating</th>
                    <th style="width:70px">Moulding</th>
                    <th style="width:70px">Reproses</th>
                    <th style="width:70px">Sanding</th>
                    <th style="width:78px">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subRowsData as $row)
                    @php
                        $subBj = $valueFromAliases($row, ['BJ']);
                        $subCCAkhir = $valueFromAliases($row, ['CCAkhir']);
                        $subFj = $valueFromAliases($row, ['FJ']);
                        $subLaminating = $valueFromAliases($row, ['Laminating']);
                        $subMoulding = $valueFromAliases($row, ['Moulding', 'WIP']);
                        $subReproses = $valueFromAliases($row, ['Reproses']);
                        $subSanding = $valueFromAliases($row, ['Sanding']);
                        $subTotal =
                            $subBj + $subCCAkhir + $subFj + $subLaminating + $subMoulding + $subReproses + $subSanding;

                        $subTotals['BJ'] += $subBj;
                        $subTotals['CCAkhir'] += $subCCAkhir;
                        $subTotals['FJ'] += $subFj;
                        $subTotals['Laminating'] += $subLaminating;
                        $subTotals['Moulding'] += $subMoulding;
                        $subTotals['Reproses'] += $subReproses;
                        $subTotals['Sanding'] += $subSanding;
                        $subTotals['Total'] += $subTotal;
                    @endphp
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $loop->iteration }}</td>
                        <td class="label">{{ (string) ($row['Jenis'] ?? '') }}</td>
                        <td class="number">{{ $fmt($subBj, true) }}</td>
                        <td class="number">{{ $fmt($subCCAkhir, true) }}</td>
                        <td class="number">{{ $fmt($subFj, true) }}</td>
                        <td class="number">{{ $fmt($subLaminating, true) }}</td>
                        <td class="number">{{ $fmt($subMoulding, true) }}</td>
                        <td class="number">{{ $fmt($subReproses, true) }}</td>
                        <td class="number">{{ $fmt($subSanding, true) }}</td>
                        <td class="number">{{ $fmt($subTotal, true) }}</td>
                    </tr>
                @endforeach
                <tr class="totals-row">
                    <td colspan="2" class="blank">Total</td>
                    <td class="number">{{ $fmt($subTotals['BJ'], true) }}</td>
                    <td class="number">{{ $fmt($subTotals['CCAkhir'], true) }}</td>
                    <td class="number">{{ $fmt($subTotals['FJ'], true) }}</td>
                    <td class="number">{{ $fmt($subTotals['Laminating'], true) }}</td>
                    <td class="number">{{ $fmt($subTotals['Moulding'], true) }}</td>
                    <td class="number">{{ $fmt($subTotals['Reproses'], true) }}</td>
                    <td class="number">{{ $fmt($subTotals['Sanding'], true) }}</td>
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
</body>

</html>
