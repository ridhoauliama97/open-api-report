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
            margin: 14mm 8mm 14mm 8mm;
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

        .section-supplier {
            margin: 8px 0 2px;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
            page-break-inside: auto;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        th,
        td {
            border: 1px solid #5f5f5f;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        td.center {
            text-align: center;
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

        .summary-inline {
            margin: 2px 0 8px;
            font-size: 10px;
        }

        .summary-inline table {
            border: none;
            width: 100%;
            margin: 0;
        }

        .summary-inline td {
            border: none;
            padding: 0 6px;
        }

        .recap-title {
            margin: 10px 0 4px;
            padding-top: 5pt;
            font-size: 12px;
            font-weight: bold;
        }

        .recap-table th,
        .recap-table td {
            font-size: 9px;
            padding: 2px 2px;
        }

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
        }

        .recap-total td {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
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
        $detailGroups = is_iterable($groupedDetailRows ?? null)
            ? (is_array($groupedDetailRows)
                ? $groupedDetailRows
                : collect($groupedDetailRows)->values()->all())
            : [];
        $detailSummary = is_array($summary['detail'] ?? null) ? $summary['detail'] : [];
        $recapSummary = is_array($summary['recap'] ?? null) ? $summary['recap'] : [];
        $recapRows = is_array($recapSummary['rows'] ?? null) ? $recapSummary['rows'] : [];
        $recapTotals = is_array($recapSummary['totals'] ?? null) ? $recapSummary['totals'] : [];
        $supplierSummaryMap = [];
        foreach ($detailSummary['suppliers'] ?? [] as $supplierSummaryRow) {
            $supplierSummaryMap[(string) ($supplierSummaryRow['supplier'] ?? '')] = is_array($supplierSummaryRow)
                ? $supplierSummaryRow
                : [];
        }

        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d M Y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d M Y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

        $toFloat = static function ($value): ?float {
            if (is_numeric($value)) {
                return (float) $value;
            }
            if (!is_string($value)) {
                return null;
            }
            $normalized = str_replace([' ', ','], ['', '.'], trim($value));

            return is_numeric($normalized) ? (float) $normalized : null;
        };
        $fmt2 = static function ($value) use ($toFloat): string {
            $float = $toFloat($value) ?? 0.0;

            return number_format($float, 2, '.', '');
        };
        $fmt2BlankZero = static function ($value) use ($toFloat): string {
            $float = $toFloat($value) ?? 0.0;
            if (abs($float) < 0.000001) {
                return '';
            }

            return number_format($float, 2, '.', '');
        };
        $fmtInt = static function ($value) use ($toFloat): string {
            $float = $toFloat($value) ?? 0.0;

            return number_format($float, 0, '.', '');
        };
        $fmtIntBlankZero = static function ($value) use ($toFloat): string {
            $float = $toFloat($value) ?? 0.0;
            if (abs($float) < 0.000001) {
                return '';
            }

            return number_format($float, 0, '.', '');
        };
        $fmt2Smart = static function ($value) use ($toFloat): string {
            $float = $toFloat($value) ?? 0.0;
            if (abs($float) < 0.000001) {
                return '';
            }

            return number_format($float, 2, '.', '');
        };
    @endphp

    <h1 class="report-title">Laporan Penerimaan Kayu Bulat Per Supplier / Hari</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @if ($detailGroups === [])
        <table>
            <tbody>
                <tr>
                    <td class="center">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @foreach ($detailGroups as $group)
        @php
            $supplierName = (string) ($group['supplier'] ?? 'Tanpa Supplier');
            $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            $sum = is_array($supplierSummaryMap[$supplierName] ?? null) ? $supplierSummaryMap[$supplierName] : [];
        @endphp
        <div class="section-supplier">Nama Supplier : {{ $supplierName }}</div>
        <table>
            <thead>
                <tr class="headers-row">
                    <th style="width:90px">No Kayu Bulat</th>
                    <th style="width:60px">No Truk</th>
                    <th style="width:90px">Tanggal</th>
                    <th style="width:100px">Jenis Kayu</th>
                    <th>Nama Grade</th>
                    <th style="width:60px">Jmlh Pcs</th>
                    <th style="width:60px">Ton KB</th>
                    <th style="width:60px">Ton KG</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ (string) ($row['NoKayuBulat'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['NoTruk'] ?? '') }}</td>
                        <td class="center">
                            @php
                                $tanggal = $row['Tanggal'] ?? null;
                                $tanggalText = '';
                                if ($tanggal) {
                                    try {
                                        $tanggalText = \Carbon\Carbon::parse((string) $tanggal)->format('d M Y');
                                    } catch (\Throwable $exception) {
                                        $tanggalText = (string) $tanggal;
                                    }
                                }
                            @endphp
                            {{ $tanggalText }}
                        </td>
                        <td>{{ (string) ($row['JenisKayu'] ?? '') }}</td>
                        <td>{{ str_replace(' - ', '-', (string) ($row['NamaGrade'] ?? '')) }}</td>
                        <td class="number">{{ $fmtInt($row['JmlhPcs'] ?? 0) }}</td>
                        <td class="number">{{ $fmt2BlankZero($row['TonKB'] ?? 0) }}</td>
                        <td class="number">{{ $fmt2($row['TonKG'] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- <div class="summary-inline">
            <table>
                <tr>
                    <td style="width: 20%;">Jmlh Truk : {{ (int) ($sum['total_trucks'] ?? 0) }}</td>
                    <td style="width: 20%;">Jmlh Pcs : {{ $fmtInt($sum['total_pcs'] ?? 0) }}</td>
                    <td style="width: 20%;">Jmlh HK : {{ (int) ($sum['total_hk'] ?? 0) }}</td>
                    <td style="width: 20%;">Ton/HK : {{ $fmt2($sum['ton_per_hk'] ?? 0) }}</td>
                    <td class="number" style="width: 20%; font-weight:700;">{{ $fmt2($sum['total_ton_kg'] ?? 0) }}
                    </td>
                </tr>
            </table>
        </div> --}}
    @endforeach

    <div class="recap-title">Rangkuman / Periode : {{ $start }} s/d {{ $end }}</div>
    <table class="recap-table">
        <thead>
            <tr class="headers-row">
                <th rowspan="2" style="font-size: 11px; width: 24px;">Tanggal</th>
                <th colspan="2" style="font-size: 11px">JABON</th>
                <th colspan="2" style="font-size: 11px">JABON TG/TD</th>
                <th colspan="2" style="font-size: 11px">PULAI</th>
                <th colspan="5" style="font-size: 11px">RAMBUNG (Ton)</th>
            </tr>
            <tr class="headers-row">
                <th style="width: 30px;font-size: 11px">Truk</th>
                <th style="width: 54px;font-size: 11px">Ton</th>
                <th style="width: 30px;font-size: 11px">Truk</th>
                <th style="width: 54px;font-size: 11px">Ton</th>
                <th style="width: 30px;font-size: 11px">Truk</th>
                <th style="width: 54px;font-size: 11px">Ton</th>
                <th style="width: 30px;font-size: 11px">Truk</th>
                <th style="width: 54px;font-size: 11px">Super</th>
                <th style="width: 54px;font-size: 11px">Mc</th>
                <th style="width: 54px;font-size: 11px">SamSam</th>
                <th style="width: 54px;font-size: 11px">Afkir</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recapRows as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">
                        @php
                            $tglRecap = (string) ($row['tanggal'] ?? '');
                            $tglRecapText = '';
                            if ($tglRecap !== '') {
                                try {
                                    $tglRecapText = \Carbon\Carbon::parse($tglRecap)->format('d M Y');
                                } catch (\Throwable $exception) {
                                    $tglRecapText = $tglRecap;
                                }
                            }
                        @endphp
                        {{ $tglRecapText }}
                    </td>
                    <td class="number">{{ $fmtIntBlankZero($row['jabon_truk'] ?? 0) }}</td>
                    <td class="number">{{ $fmt2Smart($row['jabon_ton'] ?? 0) }}</td>
                    <td class="number">{{ $fmtIntBlankZero($row['jabon_tgtd_truk'] ?? 0) }}</td>
                    <td class="number">{{ $fmt2Smart($row['jabon_tgtd_ton'] ?? 0) }}</td>
                    <td class="number">{{ $fmtIntBlankZero($row['pulai_truk'] ?? 0) }}</td>
                    <td class="number">{{ $fmt2Smart($row['pulai_ton'] ?? 0) }}</td>
                    <td class="number">{{ $fmtIntBlankZero($row['rambung_truk'] ?? 0) }}</td>
                    <td class="number">{{ $fmt2Smart($row['rambung_super_ton'] ?? 0) }}</td>
                    <td class="number">{{ $fmt2Smart($row['rambung_mc_ton'] ?? 0) }}</td>
                    <td class="number">{{ $fmt2Smart($row['rambung_samsam_ton'] ?? 0) }}</td>
                    <td class="number">{{ $fmt2Smart($row['rambung_afkir_ton'] ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="center">Tidak ada data rangkuman.</td>
                </tr>
            @endforelse
            @if ($recapRows !== [])
                <tr class="recap-total">
                    <td class="center">Total </td>
                    <td class="number">{{ $fmtIntBlankZero($recapTotals['jabon_truk'] ?? 0) }}
                    </td>
                    <td class="number">{{ $fmt2Smart($recapTotals['jabon_ton'] ?? 0) }}</td>
                    <td class="number">
                        {{ $fmtIntBlankZero($recapTotals['jabon_tgtd_truk'] ?? 0) }}</td>
                    <td class="number">{{ $fmt2Smart($recapTotals['jabon_tgtd_ton'] ?? 0) }}
                    </td>
                    <td class="number">{{ $fmtIntBlankZero($recapTotals['pulai_truk'] ?? 0) }}
                    </td>
                    <td class="number">{{ $fmt2Smart($recapTotals['pulai_ton'] ?? 0) }}</td>
                    <td class="number">
                        {{ $fmtIntBlankZero($recapTotals['rambung_truk'] ?? 0) }}</td>
                    <td class="number">{{ $fmt2Smart($recapTotals['rambung_super_ton'] ?? 0) }}
                    </td>
                    <td class="number">{{ $fmt2Smart($recapTotals['rambung_mc_ton'] ?? 0) }}
                    </td>
                    <td class="number">
                        {{ $fmt2Smart($recapTotals['rambung_samsam_ton'] ?? 0) }}</td>
                    <td class="number">{{ $fmt2Smart($recapTotals['rambung_afkir_ton'] ?? 0) }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
