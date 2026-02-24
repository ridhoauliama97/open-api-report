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
            margin: 18mm 10mm 18mm 10mm;
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
            margin: 2px 0 20px 0;
            font-size: 10px;
            color: #555;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .meta-table td {
            border: none;
            padding: 1px 3px;
            background: transparent;
            font-size: 9px;
            vertical-align: top;
        }

        .meta-label {
            width: 12%;
            font-weight: 700;
            white-space: nowrap;
        }

        .meta-sep {
            width: 1%;
            text-align: center;
            font-weight: 700;
        }

        .meta-value {
            width: 20%;
            white-space: pre-wrap;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
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
            border: 1px solid #666;
            padding: 3px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
            background: #eef2f8;
        }

        .zebra-table tbody tr:nth-child(odd) td {
            background: #fff;
        }

        .zebra-table tbody tr:nth-child(even) td {
            background: #eef2f8;
        }

        .zebra-table tbody tr.total-row td {
            background: #e5eaf1 !important;
            font-weight: 700;
        }

        td.center {
            text-align: center;
        }

        td.number {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left,
        .footer-right {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            text-align: right;
        }

        .summary-table {
            width: 60%;
            margin-top: 10px;
        }

        .signatures {
            text-align: center;
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 9px;
        }

        .signatures td {
            width: 33.33%;
            border: none;
            vertical-align: top;
            padding: 0 8px 0 0;
        }

        .sign-title {
            white-space: nowrap;
        }

        .sign-space td {
            height: 60px;
        }

        .signatures td.sign-role {
            padding-top: 2px;
            font-size: 9px;
        }

        .sign-role-line {
            display: inline-block;
            border-top: 1px solid #333;
            min-width: 150px;
            padding-top: 2px;
        }
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $headRow = $rowsData[0] ?? [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

        $findColumn = static function (array $columns, array $candidates): ?string {
            $normalize = static fn(string $name): string => strtolower(preg_replace('/[^a-z0-9]/', '', $name) ?? '');
            $candidateMap = [];
            foreach ($candidates as $candidate) {
                $candidateMap[$normalize($candidate)] = true;
            }

            foreach ($columns as $column) {
                if (isset($candidateMap[$normalize((string) $column)])) {
                    return (string) $column;
                }
            }

            return null;
        };

        $toFloat = static function ($value): ?float {
            if (is_numeric($value)) {
                return (float) $value;
            }
            if (!is_string($value)) {
                return null;
            }
            $normalized = trim(str_replace(' ', '', $value));
            if ($normalized === '') {
                return null;
            }
            if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
                if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                    $normalized = str_replace('.', '', $normalized);
                    $normalized = str_replace(',', '.', $normalized);
                } else {
                    $normalized = str_replace(',', '', $normalized);
                }
            } elseif (str_contains($normalized, ',')) {
                $normalized = str_replace(',', '.');
            }

            return is_numeric($normalized) ? (float) $normalized : null;
        };

        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Carbon\Carbon::parse((string) $value)->format('d/m/Y');
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };

        $truncate4 = static function (?float $value): float {
            if ($value === null) {
                return 0.0;
            }

            // Samakan perilaku Truncate(x*10000)/10000 dari formula lama.
            $scaled = $value * 10000;
            $truncated = $scaled >= 0 ? floor($scaled) : ceil($scaled);

            return $truncated / 10000;
        };

        $availableColumns = array_keys($headRow);
        $noSTSawmillCol = $findColumn($availableColumns, ['NoSTSawmill', 'NoProduksi', 'NomorLembaran', 'NoLembaran']);
        $tglSawmillCol = $findColumn($availableColumns, ['TglSawmill', 'Tanggal', 'Tgl', 'TglProduksi']);
        $supplierCol = $findColumn($availableColumns, ['NmSupplier', 'Supplier', 'NamaSupplier']);
        $noKayuBulatCol = $findColumn($availableColumns, ['NoKayuBulat', 'NoKB', 'NoKb']);
        $suketCol = $findColumn($availableColumns, ['Suket', 'NoSuket', 'NoSuratKeterangan']);
        $noMejaCol = $findColumn($availableColumns, ['NoMeja', 'Meja', 'No Meja']);
        $noPlatCol = $findColumn($availableColumns, ['NoPlat', 'No Plat', 'Plat']);
        $jenisCol = $findColumn($availableColumns, ['Jenis', 'JenisKayu', 'Jenis Kayu']);
        $operatorCol = $findColumn($availableColumns, ['NamaOperator', 'Operator', 'Nama Operator']);
        $statusCol = $findColumn($availableColumns, ['Status', 'StatusData']);
        $beratTimCol = $findColumn($availableColumns, ['BeratTim', 'Berat(Tim)', 'Berat Tim', 'Berat']);
        $beratCol = $findColumn($availableColumns, ['Berat']);
        $tebalCol = $findColumn($availableColumns, ['Tebal']);
        $lebarCol = $findColumn($availableColumns, ['Lebar']);
        $panjangCol = $findColumn($availableColumns, ['Panjang']);
        $jmlhBatangCol = $findColumn($availableColumns, ['JmlhBatang', 'JumlahBatang']);
        $idUOMTblLebarCol = $findColumn($availableColumns, ['IdUOMTblLebar', 'IdUomTblLebar']);
        $idUOMPanjangCol = $findColumn($availableColumns, ['IdUOMPanjang', 'IdUomPanjang']);
        $ketCol = $findColumn($availableColumns, ['Ket', 'Keterangan']);

        $tableColumns = array_values(
            array_filter([$ketCol, $tebalCol, $lebarCol, $panjangCol, $beratCol, $jmlhBatangCol]),
        );
        if ($tableColumns === []) {
            $tableColumns = $availableColumns;
        }

        $computeBeratRow = static function (array $row) use (
            $toFloat,
            $truncate4,
            $beratCol,
            $tebalCol,
            $lebarCol,
            $panjangCol,
            $jmlhBatangCol,
            $idUOMTblLebarCol,
            $idUOMPanjangCol,
        ): float {
            $beratValue = $beratCol !== null ? $toFloat($row[$beratCol] ?? null) : null;
            $idUOMTblLebarValue = $idUOMTblLebarCol !== null ? (int) ($row[$idUOMTblLebarCol] ?? 0) : 0;
            $idUOMPanjangValue = $idUOMPanjangCol !== null ? (int) ($row[$idUOMPanjangCol] ?? 0) : 0;

            $tebalValue = $tebalCol !== null ? $toFloat($row[$tebalCol] ?? null) : null;
            $lebarValue = $lebarCol !== null ? $toFloat($row[$lebarCol] ?? null) : null;
            $panjangValue = $panjangCol !== null ? $toFloat($row[$panjangCol] ?? null) : null;
            $pcsValue = $jmlhBatangCol !== null ? $toFloat($row[$jmlhBatangCol] ?? null) : null;

            if ($tebalValue !== null && $lebarValue !== null && $panjangValue !== null && $pcsValue !== null) {
                if ($idUOMTblLebarValue === 3 && $idUOMPanjangValue === 4) {
                    return $truncate4(($tebalValue * $lebarValue * $panjangValue * $pcsValue) / 7200.8);
                }
                if ($idUOMTblLebarValue === 1 && $idUOMPanjangValue === 4) {
                    return $truncate4(
                        ($tebalValue * $lebarValue * $panjangValue * 304.8 * $pcsValue) / 1000000000 / 1.416,
                    );
                }
            }

            return $truncate4($beratValue);
        };

        $totalBatang = 0.0;
        $summaryByKet = [];
        $totalTon = 0.0;
        foreach ($rowsData as $row) {
            $pcs = $jmlhBatangCol !== null ? $toFloat($row[$jmlhBatangCol] ?? null) ?? 0.0 : 0.0;
            $ton = $computeBeratRow($row);
            $ket = trim((string) ($ketCol !== null ? $row[$ketCol] ?? '' : ''));
            $ket = $ket !== '' ? $ket : '-';

            $totalBatang += $pcs;
            $totalTon += $ton;

            if (!isset($summaryByKet[$ket])) {
                $summaryByKet[$ket] = ['pcs' => 0.0, 'ton' => 0.0];
            }
            $summaryByKet[$ket]['pcs'] += $pcs;
            $summaryByKet[$ket]['ton'] += $ton;
        }
        ksort($summaryByKet);
    @endphp

    <h1 class="report-title">Laporan Lembaran Perhitungan Upah Borongan Sawmill</h1>
    <p class="report-subtitle">Nomor Lembaran : {{ $noProduksi }}</p>

    <table class="meta-table">
        <tbody>
            <tr>
                <td class="meta-label">Nomor Lembaran</td>
                <td class="meta-sep">:</td>
                <td class="meta-value">
                    {{ (string) ($noSTSawmillCol !== null ? $headRow[$noSTSawmillCol] ?? $noProduksi : $noProduksi) }}
                </td>
                <td class="meta-label">No. Meja</td>
                <td class="meta-sep">:</td>
                <td class="meta-value">{{ (string) ($noMejaCol !== null ? $headRow[$noMejaCol] ?? '-' : '-') }}</td>
                <td class="meta-label">Status</td>
                <td class="meta-sep">:</td>
                <td class="meta-value">{{ (string) ($statusCol !== null ? $headRow[$statusCol] ?? '-' : '-') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Tanggal</td>
                <td class="meta-sep">:</td>
                <td class="meta-value">
                    {{ $formatDate($tglSawmillCol !== null ? $headRow[$tglSawmillCol] ?? null : null) }}</td>
                <td class="meta-label">No. Plat</td>
                <td class="meta-sep">:</td>
                <td class="meta-value">{{ (string) ($noPlatCol !== null ? $headRow[$noPlatCol] ?? '-' : '-') }}</td>
                <td class="meta-label">Berat (Tim)</td>
                <td class="meta-sep">:</td>
                <td class="meta-value">
                    {{ number_format((float) ($toFloat($beratTimCol !== null ? $headRow[$beratTimCol] ?? null : null) ?? 0), 2, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td class="meta-label">Supplier</td>
                <td class="meta-sep">:</td>
                <td class="meta-value">{{ (string) ($supplierCol !== null ? $headRow[$supplierCol] ?? '-' : '-') }}
                </td>
                <td class="meta-label">Jenis Kayu</td>
                <td class="meta-sep">:</td>
                <td class="meta-value">{{ (string) ($jenisCol !== null ? $headRow[$jenisCol] ?? '-' : '-') }}</td>
                <td class="meta-label"></td>
                <td class="meta-sep"></td>
                <td class="meta-value"></td>
            </tr>
            <tr>
                <td class="meta-label">No.Kayu Bulat</td>
                <td class="meta-sep">:</td>
                <td class="meta-value">
                    {{ (string) ($noKayuBulatCol !== null ? $headRow[$noKayuBulatCol] ?? '-' : '-') }}</td>
                <td class="meta-label">Operator</td>
                <td class="meta-sep">:</td>
                <td class="meta-value">{{ (string) ($operatorCol !== null ? $headRow[$operatorCol] ?? '-' : '-') }}
                </td>
                <td class="meta-label"></td>
                <td class="meta-sep"></td>
                <td class="meta-value"></td>
            </tr>
            <tr>
                <td class="meta-label">No. Suket</td>
                <td class="meta-sep">:</td>
                <td class="meta-value">{{ (string) ($suketCol !== null ? $headRow[$suketCol] ?? '-' : '-') }}</td>
                <td class="meta-label"></td>
                <td class="meta-sep"></td>
                <td class="meta-value"></td>
                <td class="meta-label"></td>
                <td class="meta-sep"></td>
                <td class="meta-value"></td>
            </tr>
        </tbody>
    </table>

    <table class="zebra-table">
        <thead>
            <tr>
                <th style="width: 34px;">No</th>
                @foreach ($tableColumns as $column)
                    @php
                        $headerLabel = (string) $column;
                        if ($column === $tebalCol) {
                            $headerLabel = 'Tebal (mm)';
                        } elseif ($column === $lebarCol) {
                            $headerLabel = 'Lebar (mm)';
                        } elseif ($column === $panjangCol) {
                            $headerLabel = 'Panjang (ft)';
                        } elseif ($column === $beratCol) {
                            $headerLabel = 'Berat (Ton)';
                        } elseif ($column === $ketCol) {
                            $headerLabel = 'Keterangan';
                        } elseif ($column === $jmlhBatangCol) {
                            $headerLabel = 'Jumlah Batang (Pcs)';
                        }
                    @endphp
                    <th>{{ $headerLabel }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    @foreach ($tableColumns as $column)
                        @php $value = $row[$column] ?? null; @endphp
                        @if ($column === $jmlhBatangCol)
                            <td class="number" style="text-align: center">
                                {{ number_format((float) ($toFloat($value) ?? 0), 0, '.', '') }}
                            </td>
                        @elseif ($column === $beratCol)
                            <td class="number" style="text-align: center">
                                {{ number_format($computeBeratRow($row), 4, '.', '') }}
                            </td>
                        @elseif ($column === $panjangCol)
                            <td class="number" style="text-align: center">
                                {{ number_format((float) ($toFloat($value) ?? 0), 0, '.', '') }}
                            </td>
                        @elseif (in_array($column, [$tebalCol, $lebarCol], true))
                            <td class="number" style="text-align: center">
                                {{ number_format((float) ($toFloat($value) ?? 0), 0, '.', '') }}
                            </td>
                        @else
                            <td style="text-align: center">{{ (string) $value }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="center" colspan="{{ count($tableColumns) + 1 }}">Tidak ada data.</td>
                </tr>
            @endforelse
            {{-- @if ($rowsData !== [] && $jmlhBatangCol !== null)
                <tr class="total-row">
                    <td class="number" colspan="{{ count($tableColumns) }}"
                        style="text-align: center; font-weight: bold;">Total Jumlah Batang</td>
                    <td class="number"><strong>{{ number_format($totalBatang, 0, '.', '') }} Pcs</strong></td>
                </tr>
            @endif --}}
        </tbody>
    </table>

    <p style="margin-top: 20px; font-weight: bold; font-size: 10px">Keterangan :</p>
    @if ($summaryByKet !== [])
        <table class="summary-table zebra-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Jumlah Batang (Pcs)</th>
                    <th>Berat (Ton)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summaryByKet as $ketName => $summary)
                    <tr>
                        <td style="text-align: center">{{ $ketName }}</td>
                        <td class="number" style="text-align: center">{{ number_format($summary['pcs'], 0, '.', '') }}
                        </td>
                        <td class="number" style="text-align: center">{{ number_format($summary['ton'], 4, '.', '') }}
                        </td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td style="text-align: center; font-weight: bold;">Total</td>
                    <td class="number" style="text-align: center; font-weight: bold;">
                        {{ number_format($totalBatang, 0, '.', '') }} Pcs</td>
                    <td class="number" style="text-align: center; font-weight: bold;">
                        {{ number_format($totalTon, 4, '.', '') }} Ton</td>
                </tr>
            </tbody>
        </table>
    @endif

    <table class="signatures">
        <tbody>
            <tr>
                <td class="sign-title">Dibuat Oleh:</td>
                <td class="sign-title">Diperiksa Oleh:</td>
                <td class="sign-title">Operator:</td>
            </tr>
            <tr class="sign-space">
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="sign-role"><span class="sign-role-line">Tally Sawmill</span></td>
                <td class="sign-role"><span class="sign-role-line">Ka.Bag. Sawmill</span></td>
                <td class="sign-role"><span class="sign-role-line">Tukang Sorong</span></td>
            </tr>
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
