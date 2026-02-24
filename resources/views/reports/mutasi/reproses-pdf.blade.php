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
            font-family:"Noto Serif", serif;
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

        .section-title {
            margin: 10px 0 4px 0;
            font-size: 11px;
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
            font-family:"Calibri","DejaVu Sans", sans-serif;
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
    
        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
        }
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
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
                if (array_key_exists($alias, $row)) {
                    return $toFloat($row[$alias]);
                }
            }

            return 0.0;
        };

        $fmt = static function (float $value, bool $blankWhenZero = true): string {
            if ($blankWhenZero && abs($value) < 0.0000001) {
                return '';
            }

            return number_format($value, 4, '.', '');
        };

        $mainTotals = [
            'awal' => 0.0,
            'adj_out' => 0.0,
            'mld_out' => 0.0,
            'pack_out' => 0.0,
            'repro_keluar' => 0.0,
            'total_masuk' => 0.0,
            'adj_in' => 0.0,
            'bs_in' => 0.0,
            'cca_in' => 0.0,
            'lmt_in' => 0.0,
            'mld_in' => 0.0,
            's4s_in' => 0.0,
            'sand_in' => 0.0,
            'repro_jual' => 0.0,
            'total_keluar' => 0.0,
            'akhir' => 0.0,
        ];

    @endphp

    <h1 class="report-title">Laporan Mutasi Reproses</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    <table>
        <thead>
            <tr class="headers-row">
                <th rowspan="2" style="width:30px">No</th>
                <th rowspan="2" style="width:170px">Jenis</th>
                <th rowspan="2" style="width:58px">Awal</th>
                <th colspan="4">Masuk</th>
                <th rowspan="2" style="width:62px">Total<br>Masuk</th>
                <th colspan="8">Keluar</th>
                <th rowspan="2" style="width:62px">Total<br>Keluar</th>
                <th rowspan="2" style="width:58px">Akhir</th>
            </tr>
            <tr class="headers-row">
                <th style="width:58px">Adj Out<br>REPRO</th>
                <th style="width:58px">MLD Out<br>REPRO</th>
                <th style="width:58px">PACK Out<br>REPRO</th>
                <th style="width:58px">REPRO<br>Keluar</th>
                <th style="width:58px">Adj In<br>REPRO</th>
                <th style="width:58px">BS In<br>REPRO</th>
                <th style="width:58px">CCA In<br>REPRO</th>
                <th style="width:58px">LMT In<br>REPRO</th>
                <th style="width:58px">MLD In<br>REPRO</th>
                <th style="width:58px">S4S In<br>REPRO</th>
                <th style="width:58px">SAND In<br>REPRO</th>
                <th style="width:58px">REPRO<br>Jual</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                @php
                    $awal = $valueFromAliases($row, ['REPROAwal', 'Awal']);
                    $adjOut = $valueFromAliases($row, ['AdjOutput', 'AdjOut']);
                    $mldOut = $valueFromAliases($row, ['MLDOutput', 'MldOutput']);
                    $packOut = $valueFromAliases($row, ['PACKOutput', 'PackOutput']);
                    $reproKeluar = $valueFromAliases($row, ['REPROKeluar']);
                    $totalMasuk = $adjOut + $mldOut + $packOut + $reproKeluar;

                    $adjIn = $valueFromAliases($row, ['AdjInput', 'AdjInpt']);
                    $bsIn = $valueFromAliases($row, ['BSInput', 'BSInpt']);
                    $ccaIn = $valueFromAliases($row, ['CCAInput', 'CCAInpt']);
                    $lmtIn = $valueFromAliases($row, ['LMTInput', 'LMTInpt']);
                    $mldIn = $valueFromAliases($row, ['MLDInput', 'MLDInpt']);
                    $s4sIn = $valueFromAliases($row, ['S4SInput', 'S4SInpt']);
                    $sandIn = $valueFromAliases($row, ['SANDInput', 'SANDInpt']);
                    $reproJual = $valueFromAliases($row, ['REPROJual']);
                    $totalKeluar = $adjIn + $bsIn + $ccaIn + $lmtIn + $mldIn + $s4sIn + $sandIn + $reproJual;
                    $akhir = $valueFromAliases($row, ['ReprosesAkhir', 'Akhir']);

                    $mainTotals['awal'] += $awal;
                    $mainTotals['adj_out'] += $adjOut;
                    $mainTotals['mld_out'] += $mldOut;
                    $mainTotals['pack_out'] += $packOut;
                    $mainTotals['repro_keluar'] += $reproKeluar;
                    $mainTotals['total_masuk'] += $totalMasuk;
                    $mainTotals['adj_in'] += $adjIn;
                    $mainTotals['bs_in'] += $bsIn;
                    $mainTotals['cca_in'] += $ccaIn;
                    $mainTotals['lmt_in'] += $lmtIn;
                    $mainTotals['mld_in'] += $mldIn;
                    $mainTotals['s4s_in'] += $s4sIn;
                    $mainTotals['sand_in'] += $sandIn;
                    $mainTotals['repro_jual'] += $reproJual;
                    $mainTotals['total_keluar'] += $totalKeluar;
                    $mainTotals['akhir'] += $akhir;
                @endphp
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="label">{{ (string) ($row['Jenis'] ?? '') }}</td>
                    <td class="number">{{ $fmt($awal, true) }}</td>
                    <td class="number">{{ $fmt($adjOut, true) }}</td>
                    <td class="number">{{ $fmt($mldOut, true) }}</td>
                    <td class="number">{{ $fmt($packOut, true) }}</td>
                    <td class="number">{{ $fmt($reproKeluar, true) }}</td>
                    <td class="number">{{ $fmt($totalMasuk, true) }}</td>
                    <td class="number">{{ $fmt($adjIn, true) }}</td>
                    <td class="number">{{ $fmt($bsIn, true) }}</td>
                    <td class="number">{{ $fmt($ccaIn, true) }}</td>
                    <td class="number">{{ $fmt($lmtIn, true) }}</td>
                    <td class="number">{{ $fmt($mldIn, true) }}</td>
                    <td class="number">{{ $fmt($s4sIn, true) }}</td>
                    <td class="number">{{ $fmt($sandIn, true) }}</td>
                    <td class="number">{{ $fmt($reproJual, true) }}</td>
                    <td class="number">{{ $fmt($totalKeluar, true) }}</td>
                    <td class="number">{{ $fmt($akhir, true) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="18" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
            <tr class="totals-row">
                <td colspan="2" class="blank">Total</td>
                <td class="number">{{ $fmt($mainTotals['awal'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['adj_out'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['mld_out'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['pack_out'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['repro_keluar'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['total_masuk'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['adj_in'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['bs_in'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['cca_in'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['lmt_in'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['mld_in'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['s4s_in'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['sand_in'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['repro_jual'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['total_keluar'], true) }}</td>
                <td class="number">{{ $fmt($mainTotals['akhir'], true) }}</td>
            </tr>
        </tbody>
    </table>

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
</body>

</html>
