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
            margin: 14mm 10mm 14mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.1;
            color: #000;
        }

        .report-title {
            margin: 0 0 20px 0;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }

        table {
            border-collapse: collapse;
            border-spacing: 0;
            width: 100%;
        }

        .meta-layout {
            width: 100%;
            margin: 0 0 6px 0;
            table-layout: fixed;
        }

        .meta-layout td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .meta-block {
            width: 100%;
            table-layout: fixed;
        }

        .meta-block td {
            border: 0;
            padding: 0 0 1px 0;
            font-size: 9.5px;
            vertical-align: top;
        }

        .meta-label {
            width: 94px;
            white-space: nowrap;
        }

        .meta-separator {
            width: 12px;
            text-align: center;
        }

        .meta-value {
            word-break: break-word;
        }

        .split-layout {
            width: 100%;
            table-layout: fixed;
            margin: 0 0 4px 0;
            page-break-inside: avoid;
        }

        .split-layout td {
            border: 0;
            vertical-align: top;
            padding: 0;
        }

        .split-gap {
            width: 1.5%;
        }

        .report-table {
            width: 100%;
            table-layout: fixed;
            margin: 0 0 2px 0;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
            font-size: 8.6px;
        }

        .report-table th,
        .report-table td {
            border: 0;
            border-left: 1px solid #000;
            padding: 1px 2px;
            vertical-align: middle;
            text-align: center;
        }

        .report-table thead th {
            font-weight: bold;
            font-size: 9px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            background: #fff;
            padding-top: 2px;
            padding-bottom: 2px;
        }

        .report-table tbody tr.data-row td {
            border-top: 0;
            border-bottom: 0;
            border-right: 0;
        }

        .row-odd td {
            background: #d8deea;
        }

        .row-even td {
            background: #f3f6fb;
        }

        .report-table .number {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
            white-space: nowrap;
        }

        .report-table .emphasis {
            font-weight: bold;
        }

        .summary-layout {
            width: 100%;
            margin: 2px 0 3px 0;
            table-layout: fixed;
            page-break-inside: avoid;
        }

        .summary-layout td {
            border: 0;
            vertical-align: top;
            padding: 0;
        }

        .summary-column-left {
            padding-right: 10px;
        }

        .summary-column-right {
            padding-left: 10px;
        }

        .summary-block {
            width: 220px;
            table-layout: fixed;
            margin: 0;
        }

        .summary-block-left {
            margin-left: 18px;
        }

        .summary-block-right {
            margin-left: 18px;
        }

        .summary-block td {
            border: 0;
            padding: 0;
            vertical-align: top;
            font-size: 9.5px;
        }

        .summary-title {
            font-style: italic;
            white-space: nowrap;
            padding-bottom: 1px;
        }

        .summary-label {
            width: 62px;
            white-space: nowrap;
        }

        .summary-separator {
            width: 12px;
            text-align: center;
        }

        .summary-value {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
            white-space: nowrap;
            width: 96px;
        }

        .signatures {
            width: 100%;
            margin: 25px auto 0;
            table-layout: fixed;
            page-break-inside: avoid;
        }

        .signatures td {
            width: 25%;
            border: 0;
            padding: 0 14px;
            vertical-align: top;
            text-align: center;
            font-size: 9px;
        }

        .signature-total-cell {
            text-align: left !important;
        }

        .signature-total-block {
            width: 180px;
            margin-left: auto;
            margin-right: 0;
            table-layout: fixed;
        }

        .signature-total-block td {
            border: 0;
            padding: 0;
            font-size: 9.5px;
            vertical-align: top;
        }

        .signature-space td {
            height: 70px;
        }

        .signature-line {
            display: block;
            width: 140px;
            border-top: 1px solid #000;
            margin: 0 auto 2px;
        }

        .signature-role {
            white-space: nowrap;
        }
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $headRow = $rowsData[0] ?? [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

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
                $normalized = str_replace(',', '.', $normalized);
            }

            return is_numeric($normalized) ? (float) $normalized : null;
        };

        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            try {
                return \Carbon\Carbon::parse((string) $value)->copy()->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };

        $formatNumber = static function ($value, int $precision = 4): string {
            return number_format((float) $value, $precision, '.', '');
        };

        $formatSummaryNumber = static function ($value, int $precision = 4): string {
            return rtrim(rtrim(number_format((float) $value, $precision, '.', ''), '0'), '.');
        };

        $formatOptionalSummaryNumber = static function ($value, int $precision = 4): string {
            $number = (float) $value;
            if (abs($number) < 0.0000001) {
                return '';
            }

            return rtrim(rtrim(number_format($number, $precision, '.', ''), '0'), '.');
        };

        $truncate4 = static function (?float $value): float {
            if ($value === null) {
                return 0.0;
            }

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
        $noCol = $findColumn($availableColumns, ['No', 'Nomor', 'Urut']);

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

        $formatOperatorText = static function ($value): string {
            $text = trim((string) $value);
            if ($text === '') {
                return '-';
            }

            return preg_replace('/\s*\(\s*/', ' (', $text) ?? $text;
        };

        $normalizeSummaryKey = static function (string $value): string {
            $normalized = strtoupper(trim($value));
            $normalized = str_replace(['.', '-', '_'], ' ', $normalized);
            $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

            return match ($normalized) {
                'STD' => 'STD',
                'MC 1', 'MC1' => 'MC 1',
                'MC 2', 'MC2' => 'MC 2',
                'MC' => 'MC',
                'LOKAL STD', 'L STD', 'LSTD' => 'LOKAL STD',
                'LOKAL MC', 'L MC', 'LMC' => 'LOKAL MC',
                default => $normalized !== '' ? $normalized : '-',
            };
        };

        $summaryOrder = ['STD', 'MC 1', 'MC 2', 'MC', 'LOKAL STD', 'LOKAL MC'];
        $summaryByKet = [];
        foreach ($summaryOrder as $summaryKey) {
            $summaryByKet[$summaryKey] = ['pcs' => 0.0, 'ton' => 0.0];
        }

        $preparedRows = [];
        $totalBatang = 0.0;
        $totalTon = 0.0;

        foreach ($rowsData as $index => $row) {
            $pcs = $jmlhBatangCol !== null ? $toFloat($row[$jmlhBatangCol] ?? null) ?? 0.0 : 0.0;
            $ton = $computeBeratRow($row);
            $ket = $normalizeSummaryKey((string) ($ketCol !== null ? $row[$ketCol] ?? '' : ''));
            $rowNo =
                $noCol !== null && isset($row[$noCol]) && trim((string) $row[$noCol]) !== ''
                    ? (string) $row[$noCol]
                    : (string) ($index + 1);
            $tebalText =
                $tebalCol !== null ? number_format((float) ($toFloat($row[$tebalCol] ?? null) ?? 0), 0, '.', '') : '';
            $lebarText =
                $lebarCol !== null ? number_format((float) ($toFloat($row[$lebarCol] ?? null) ?? 0), 0, '.', '') : '';
            $panjangValue = $panjangCol !== null ? $toFloat($row[$panjangCol] ?? null) : null;
            $panjangText = $panjangValue !== null ? number_format($panjangValue, 1, '.', '') : '';
            $pcsText = number_format($pcs, 0, '.', '');
            $beratText = $formatNumber($ton, 4);

            if (!isset($summaryByKet[$ket])) {
                $summaryByKet[$ket] = ['pcs' => 0.0, 'ton' => 0.0];
            }

            $summaryByKet[$ket]['pcs'] += $pcs;
            $summaryByKet[$ket]['ton'] += $ton;
            $totalBatang += $pcs;
            $totalTon += $ton;

            $preparedRows[] = [
                'no' => $rowNo,
                'tebal' => $tebalText,
                'lebar' => $lebarText,
                'uom_size' => 'mm',
                'panjang' => $panjangText,
                'uom_length' => 'feet',
                'pcs' => $pcsText,
                'berat' => $beratText,
                'ket' => $ket,
            ];
        }

        $totalPreparedRows = count($preparedRows);
        $leftRowCount = (int) ceil($totalPreparedRows / 2);
        $leftRows = array_slice($preparedRows, 0, $leftRowCount);
        $rightRows = array_slice($preparedRows, $leftRowCount);

        $summaryColumns = [['STD', 'MC 1', 'MC 2'], ['MC', 'LOKAL STD', 'LOKAL MC']];
    @endphp

    <h1 class="report-title">Lembaran Perhitungan Upah Borongan Sawmill</h1>

    <table class="meta-layout">
        <tbody>
            <tr>
                <td style="width: 50%; padding-right: 16px;">
                    <table class="meta-block">
                        <tbody>
                            <tr>
                                <td class="meta-label">Nomor Lembaran</td>
                                <td class="meta-separator">:</td>
                                <td class="meta-value">
                                    {{ (string) ($noSTSawmillCol !== null ? $headRow[$noSTSawmillCol] ?? $noProduksi : $noProduksi) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="meta-label">Tanggal</td>
                                <td class="meta-separator">:</td>
                                <td class="meta-value">
                                    {{ $formatDate($tglSawmillCol !== null ? $headRow[$tglSawmillCol] ?? null : null) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="meta-label">Supplier</td>
                                <td class="meta-separator">:</td>
                                <td class="meta-value">
                                    {{ (string) ($supplierCol !== null ? $headRow[$supplierCol] ?? '-' : '-') }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">No.Kayu Bulat</td>
                                <td class="meta-separator">:</td>
                                <td class="meta-value">
                                    {{ (string) ($noKayuBulatCol !== null ? $headRow[$noKayuBulatCol] ?? '-' : '-') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="meta-label">No. Suket</td>
                                <td class="meta-separator">:</td>
                                <td class="meta-value">
                                    {{ (string) ($suketCol !== null ? $headRow[$suketCol] ?? '-' : '-') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td style="width: 50%; padding-left: 12px;">
                    <table class="meta-block">
                        <tbody>
                            <tr>
                                <td class="meta-label">No. Meja</td>
                                <td class="meta-separator">:</td>
                                <td class="meta-value">
                                    {{ (string) ($noMejaCol !== null ? $headRow[$noMejaCol] ?? '-' : '-') }}</td>
                                <td class="meta-label" style="padding-left: 18px;">Status</td>
                                <td class="meta-separator">:</td>
                                <td class="meta-value">
                                    {{ (string) ($statusCol !== null ? $headRow[$statusCol] ?? '-' : '-') }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">No.Plat</td>
                                <td class="meta-separator">:</td>
                                <td class="meta-value">
                                    {{ (string) ($noPlatCol !== null ? $headRow[$noPlatCol] ?? '-' : '-') }}</td>
                                <td class="meta-label" style="padding-left: 18px;">Berat (Tim)</td>
                                <td class="meta-separator">:</td>
                                <td class="meta-value">
                                    {{ number_format((float) ($toFloat($headRow[$beratTimCol] ?? null) ?? 0), 2, '.', ',') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="meta-label">Jenis Kayu</td>
                                <td class="meta-separator">:</td>
                                <td class="meta-value">
                                    {{ (string) ($jenisCol !== null ? $headRow[$jenisCol] ?? '-' : '-') }}</td>
                                <td colspan="3"></td>
                            </tr>
                            <tr>
                                <td class="meta-label">Operator</td>
                                <td class="meta-separator">:</td>
                                <td class="meta-value" colspan="4">
                                    {{ $formatOperatorText($operatorCol !== null ? $headRow[$operatorCol] ?? '-' : '-') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="split-layout">
        <tbody>
            <tr>
                <td style="width: 49%;">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th style="width: 7%;">No</th>
                                <th style="width: 10%;">Tebal</th>
                                <th style="width: 10%;">Lebar</th>
                                <th style="width: 8%;">@</th>
                                <th style="width: 10%;">Pjg</th>
                                <th style="width: 10%;">@</th>
                                <th style="width: 11%;">Pcs</th>
                                <th style="width: 16%;">Berat</th>
                                <th style="width: 18%;">Ket</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($leftRows as $row)
                                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="number">{{ $row['tebal'] }}</td>
                                    <td class="number">{{ $row['lebar'] }}</td>
                                    <td>{{ $row['uom_size'] }}</td>
                                    <td class="number">{{ $row['panjang'] }}</td>
                                    <td>{{ $row['uom_length'] }}</td>
                                    <td class="number">{{ $row['pcs'] }}</td>
                                    <td class="number emphasis">{{ $row['berat'] }}</td>
                                    <td>{{ $row['ket'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
                <td class="split-gap"></td>
                <td style="width: 49%;">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th style="width: 7%;">No</th>
                                <th style="width: 10%;">Tebal</th>
                                <th style="width: 10%;">Lebar</th>
                                <th style="width: 8%;">@</th>
                                <th style="width: 10%;">Pjg</th>
                                <th style="width: 10%;">@</th>
                                <th style="width: 11%;">Pcs</th>
                                <th style="width: 16%;">Berat</th>
                                <th style="width: 18%;">Ket</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rightRows as $row)
                                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                                    <td>{{ $leftRowCount + $loop->iteration }}</td>
                                    <td class="number">{{ $row['tebal'] }}</td>
                                    <td class="number">{{ $row['lebar'] }}</td>
                                    <td>{{ $row['uom_size'] }}</td>
                                    <td class="number">{{ $row['panjang'] }}</td>
                                    <td>{{ $row['uom_length'] }}</td>
                                    <td class="number">{{ $row['pcs'] }}</td>
                                    <td class="number emphasis">{{ $row['berat'] }}</td>
                                    <td>{{ $row['ket'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="summary-layout">
        <tbody>
            <tr>
                @foreach ($summaryColumns as $columnIndex => $summaryKeys)
                    <td style="width: 50%;"
                        class="{{ $columnIndex === 0 ? 'summary-column-left' : 'summary-column-right' }}">
                        @foreach ($summaryKeys as $summaryKey)
                            <table
                                class="summary-block {{ $columnIndex === 0 ? 'summary-block-left' : 'summary-block-right' }}"
                                style="margin-bottom: 4px;">
                                <tbody>
                                    <tr>
                                        <td class="summary-title" colspan="3">//{{ $summaryKey }}</td>
                                    </tr>
                                    <tr>
                                        <td class="summary-label">JmlhPcs</td>
                                        <td class="summary-separator">:</td>
                                        <td class="summary-value">
                                            {{ ($summaryByKet[$summaryKey]['pcs'] ?? 0) > 0 ? number_format($summaryByKet[$summaryKey]['pcs'] ?? 0, 0, '.', '') : '' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="summary-label">Jmlh Ton</td>
                                        <td class="summary-separator">:</td>
                                        <td class="summary-value">
                                            {{ $formatOptionalSummaryNumber($summaryByKet[$summaryKey]['ton'] ?? 0, 4) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        @endforeach
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tbody>
            <tr>
                <td>Dibuat Oleh :</td>
                <td>Diperiksa Oleh :</td>
                <td>Operator :</td>
                <td class="signature-total-cell">
                    <table class="signature-total-block">
                        <tbody>
                            <tr>
                                <td class="summary-label">Jmlh Pcs</td>
                                <td class="summary-separator">:</td>
                                <td class="summary-value">{{ number_format($totalBatang, 0, '.', '') }}</td>
                            </tr>
                            <tr>
                                <td class="summary-label">Jmlh Ton</td>
                                <td class="summary-separator">:</td>
                                <td class="summary-value">{{ $formatOptionalSummaryNumber($totalTon, 4) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            <tr class="signature-space">
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>
                    <span class="signature-line"></span>
                    <span class="signature-role">TallySawmill</span>
                </td>
                <td>
                    <span class="signature-line"></span>
                    <span class="signature-role">Ka.Bag. Sawmill</span>
                </td>
                <td>
                    <span class="signature-line"></span>
                    <span class="signature-role">Tukang Sorong</span>
                </td>
                <td></td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
