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
            table-layout: fixed;
        }

        .report-table {
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #000;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
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

        .totals-row td.blank {
            background: transparent;
            font-weight: 400;
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
        @include('reports.partials.pdf-footer-table-style')
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

        $totals = [
            'awal' => 0.0,
            'adj_out' => 0.0,
            'bs_out' => 0.0,
            'prod_out' => 0.0,
            'total_masuk' => 0.0,
            'adj_inp' => 0.0,
            'bs_inp' => 0.0,
            'cca_prod_inp' => 0.0,
            'lmt_jual' => 0.0,
            'mld_prod_inp' => 0.0,
            'total_keluar' => 0.0,
            'akhir' => 0.0,
        ];

        $subSpec = [
            ['key' => 'Moulding', 'label' => 'Moulding'],
            ['key' => 'Reproses', 'label' => 'Reproses'],
            ['key' => 'Sanding', 'label' => 'Sanding'],
            ['key' => 'WIP', 'label' => 'WIP'],
            ['key' => 'BJ', 'label' => 'BJ'],
            ['key' => 'CCAkhir', 'label' => 'CCAkhir'],
        ];
        $subTotals = [
            'Moulding' => 0.0,
            'Reproses' => 0.0,
            'Sanding' => 0.0,
            'WIP' => 0.0,
            'BJ' => 0.0,
            'CCAkhir' => 0.0,
            'Total' => 0.0,
        ];
    @endphp

    <h1 class="report-title">Laporan Mutasi Laminating (m3)</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th rowspan="2" style="width:30px">No</th>
                <th rowspan="2" style="width:230px">Jenis</th>
                <th rowspan="2" style="width:65px">Awal</th>
                <th colspan="3">Masuk</th>
                <th rowspan="2" style="width:70px">Total<br>Masuk</th>
                <th colspan="5">Keluar</th>
                <th rowspan="2" style="width:70px">Total<br>Keluar</th>
                <th rowspan="2" style="width:65px">Akhir</th>
            </tr>
            <tr class="headers-row">
                <th style="width:68px">Adj Out<br>LMT</th>
                <th style="width:68px">BS Out<br>LMT</th>
                <th style="width:68px">LMT Prod<br>Out</th>
                <th style="width:68px">Adj Inp<br>LMT</th>
                <th style="width:68px">BS Inpt<br>LMT</th>
                <th style="width:68px">CCAProd<br>Inpt</th>
                <th style="width:68px">LMT<br>Jual</th>
                <th style="width:68px">Mld Prod<br>Inpt</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="table-end-line">
                <td colspan="14"></td>
            </tr>
        </tfoot>
        <tbody>
            @forelse ($rowsData as $row)
                @php
                    $awal = $valueFromAliases($row, ['LMTAwal', 'Awal']);
                    $adjOut = $valueFromAliases($row, ['AdjOutputLMT', 'AdjOutLMT']);
                    $bsOut = $valueFromAliases($row, ['BSOutputLMT', 'BSOutLMT']);
                    $prodOut = $valueFromAliases($row, ['LMTProdOuput', 'LMTProdOutput', 'LMTMasuk']);
                    $totalMasuk = $adjOut + $bsOut + $prodOut;

                    $adjInp = $valueFromAliases($row, ['AdjInptLMT', 'AdjInputLMT']);
                    $bsInp = $valueFromAliases($row, ['BSInptLMT', 'BSInputLMT']);
                    $ccaProdInp = $valueFromAliases($row, ['CCAProdInptLMT', 'CCAInptLMT']);
                    $lmtJual = $valueFromAliases($row, ['LMTJual', 'JualLMT', 'Jual']);
                    $mldProdInp = $valueFromAliases($row, ['MldProdInptLMT', 'MLDProdInptLMT', 'MLDInptLMT']);
                    $totalKeluar = $adjInp + $bsInp + $ccaProdInp + $lmtJual + $mldProdInp;

                    $akhir = $valueFromAliases($row, ['LMTAkhir', 'Akhir']);

                    $totals['awal'] += $awal;
                    $totals['adj_out'] += $adjOut;
                    $totals['bs_out'] += $bsOut;
                    $totals['prod_out'] += $prodOut;
                    $totals['total_masuk'] += $totalMasuk;
                    $totals['adj_inp'] += $adjInp;
                    $totals['bs_inp'] += $bsInp;
                    $totals['cca_prod_inp'] += $ccaProdInp;
                    $totals['lmt_jual'] += $lmtJual;
                    $totals['mld_prod_inp'] += $mldProdInp;
                    $totals['total_keluar'] += $totalKeluar;
                    $totals['akhir'] += $akhir;
                @endphp
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center data-cell">{{ $loop->iteration }}</td>
                    <td class="label data-cell">{{ $row['Jenis'] ?? '' }}</td>
                    <td class="number data-cell">{{ $fmt($awal, true) }}</td>
                    <td class="number data-cell">{{ $fmt($adjOut, true) }}</td>
                    <td class="number data-cell">{{ $fmt($bsOut, true) }}</td>
                    <td class="number data-cell">{{ $fmt($prodOut, true) }}</td>
                    <td class="number data-cell">{{ $fmt($totalMasuk, true) }}</td>
                    <td class="number data-cell">{{ $fmt($adjInp, true) }}</td>
                    <td class="number data-cell">{{ $fmt($bsInp, true) }}</td>
                    <td class="number data-cell">{{ $fmt($ccaProdInp, true) }}</td>
                    <td class="number data-cell">{{ $fmt($lmtJual, true) }}</td>
                    <td class="number data-cell">{{ $fmt($mldProdInp, true) }}</td>
                    <td class="number data-cell">{{ $fmt($totalKeluar, true) }}</td>
                    <td class="number data-cell">{{ $fmt($akhir, true) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
            <tr class="totals-row">
                <td colspan="2" style="text-align:center">Total</td>
                <td class="number">{{ $fmt($totals['awal'], true) }}</td>
                <td class="number">{{ $fmt($totals['adj_out'], true) }}</td>
                <td class="number">{{ $fmt($totals['bs_out'], true) }}</td>
                <td class="number">{{ $fmt($totals['prod_out'], true) }}</td>
                <td class="number">{{ $fmt($totals['total_masuk'], true) }}</td>
                <td class="number">{{ $fmt($totals['adj_inp'], true) }}</td>
                <td class="number">{{ $fmt($totals['bs_inp'], true) }}</td>
                <td class="number">{{ $fmt($totals['cca_prod_inp'], true) }}</td>
                <td class="number">{{ $fmt($totals['lmt_jual'], true) }}</td>
                <td class="number">{{ $fmt($totals['mld_prod_inp'], true) }}</td>
                <td class="number">{{ $fmt($totals['total_keluar'], true) }}</td>
                <td class="number">{{ $fmt($totals['akhir'], true) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($subRowsData !== [])
        <div class="section-title">Input Laminating Produksi</div>
        <table class="report-table" style="width: 78%;">
            <thead>
                <tr class="headers-row">
                    <th style="width:30px">No</th>
                    <th style="width:220px">Jenis</th>
                    @foreach ($subSpec as $spec)
                        <th style="width:78px">{{ $spec['label'] }}</th>
                    @endforeach
                    <th style="width:78px">Total</th>
                </tr>
            </thead>
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="9"></td>
                </tr>
            </tfoot>
            <tbody>
                @foreach ($subRowsData as $row)
                    @php
                        $rowTotal = 0.0;
                    @endphp
                    <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center data-cell">{{ $loop->iteration }}</td>
                        <td class="label data-cell">{{ $row['Jenis'] ?? '' }}</td>
                        @foreach ($subSpec as $spec)
                            @php
                                $value = $valueFromAliases($row, [$spec['key']]);
                                $subTotals[$spec['key']] += $value;
                                $rowTotal += $value;
                            @endphp
                            <td class="number data-cell">{{ $fmt($value, true) }}</td>
                        @endforeach
                        @php
                            $subTotals['Total'] += $rowTotal;
                        @endphp
                        <td class="number data-cell">{{ $fmt($rowTotal, true) }}</td>
                    </tr>
                @endforeach
                <tr class="totals-row">
                    <td colspan="2" style="text-align:center">Total</td>
                    @foreach ($subSpec as $spec)
                        <td class="number">{{ $fmt($subTotals[$spec['key']], true) }}</td>
                    @endforeach
                    <td class="number">{{ $fmt($subTotals['Total'], true) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
