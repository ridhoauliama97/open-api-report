<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    @php
        $pageMargin = '18mm 10mm 18mm 10mm';
        $bodyFontSize = '10px';
        $titleFontSize = '16px';
        $subtitleMargin = '2px 0 18px 0';
        $tableMarginBottom = '8px';
    @endphp
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: {{ $pageMargin }};
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: {{ $bodyFontSize }};
            line-height: 1.2;
            color: #000;
        }

        .report-title {
            margin: 0;
            text-align: center;
            font-size: {{ $titleFontSize }};
            font-weight: bold;
        }

        .report-subtitle {
            margin: {{ $subtitleMargin }};
            text-align: center;
            font-size: 12px;
            color: #636466;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: {{ $tableMarginBottom }};
            page-break-inside: auto;
            table-layout: fixed;
        }

        .report-table {
            border-collapse: collapse;
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
            padding: 3px 4px;
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

        .headers-row th {
            font-size: 11px;
            border-top: 0;
            border-bottom: 1px solid #000;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: none !important;
            border-bottom: none !important;
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
        }

        .report-table tbody tr.row-last td.data-cell {
            border-bottom: 1px solid #000 !important;
        }

        .totals-row td {
            font-size: 11px;
            font-weight: bold;
            border: 1px solid #000;
        }

        .report-table tbody tr.totals-row:last-child td {
            border-bottom: 0 !important;
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

        .group-title {
            margin: 10px 0 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .meta-block {
            margin: 0 0 6px;
            font-size: 10px;
            line-height: 1.3;
        }

        .meta-row {
            margin: 0 0 2px;
        }

        .meta-label {
            display: inline-block;
            min-width: 120px;
            font-weight: bold;
        }

        .date-separator {
            border-top: 1px solid #000;
            margin: 12px 0 8px;
        }

        .section-cell {
            text-align: center;
            font-weight: bold;
        }

        .grade-output {
            text-align: right;
            padding-right: 10px;
        }

        .rendemen-row {
            margin: 2px 0 10px;
            text-align: right;
            font-size: 10px;
            font-weight: bold;
        }

    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $groupsData =
            isset($groupedRows) && is_iterable($groupedRows)
                ? (is_array($groupedRows)
                    ? $groupedRows
                    : collect($groupedRows)->values()->all())
                : [];
        $availableColumns = array_keys($rowsData[0] ?? []);
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

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

        $normalizeName = static function (?string $name): string {
            $raw = (string) ($name ?? '');
            return strtolower(preg_replace('/[^a-z0-9]/', '', $raw) ?? '');
        };

        $findColumn = static function (array $columns, array $candidates) use ($normalizeName): ?string {
            $candidateSet = [];
            foreach ($candidates as $candidate) {
                $candidateSet[$normalizeName((string) $candidate)] = true;
            }

            foreach ($columns as $column) {
                if (isset($candidateSet[$normalizeName((string) $column)])) {
                    return (string) $column;
                }
            }

            return null;
        };

        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse((string) $value)->locale('id')->translatedFormat('d M Y');
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };

        $formatDateShort = static function ($value): string {
            if ($value === null || $value === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse((string) $value)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };

        $formatTruck = static function ($value): string {
            $raw = trim((string) ($value ?? ''));
            if ($raw === '' || $raw === '0' || $raw === '0.0') {
                return '';
            }

            return $raw;
        };

        $formatDetail = static function ($value, int $decimals): string {
            $number = is_numeric($value) ? (float) $value : 0.0;
            return abs($number) < 0.0000001 ? '' : number_format($number, $decimals, '.', ',');
        };

        $formatPercent = static function ($value, int $decimals = 1): string {
            $number = is_numeric($value) ? (float) $value : 0.0;
            return abs($number) < 0.0000001 ? '' : number_format($number, $decimals, '.', ',') . '%';
        };

        $formatTotal = static function ($value, int $decimals): string {
            return number_format((float) $value, $decimals, '.', ',');
        };

        $dateColumn = $findColumn($availableColumns, [
            'TglLaporan',
            'Tanggal',
            'DateCreate',
            'TanggalST',
            'TglPenerimaanST',
            'Date',
        ]);
        $supplierColumnResolved =
            $supplierColumn ?? $findColumn($availableColumns, ['Supplier', 'NmSupplier', 'Nama Supplier']);
        $noPenStColumn = $findColumn($availableColumns, ['NoPenST', 'No Pen ST', 'NoPenerimaanST']);
        $noKbColumn = $findColumn($availableColumns, ['NoKB', 'No KB', 'NoKayuBulat']);
        $jenisKayuColumn = $findColumn($availableColumns, ['JenisKayu', 'Jenis Kayu', 'Jenis']);
        $trukColumn = $findColumn($availableColumns, ['NoTruk', 'Truk']);
        $mejaColumn = $findColumn($availableColumns, ['Meja', 'NoMeja']);
        $sectionColumn = $findColumn($availableColumns, ['InOut', 'InputOutput', 'Kelompok', 'Kategori']);
        $gradeColumn = $findColumn($availableColumns, ['Grade', 'NamaGrade']);
        $jmlhTrukColumn = $findColumn($availableColumns, ['JmlhTruk', 'JumlahTruk', 'JmlTruk']);
        $kbTonColumn = $findColumn($availableColumns, ['KB (Ton)', 'KBTon', 'TonKB']);
        $stTonColumn = $findColumn($availableColumns, ['ST (Ton)', 'STTon', 'TonST', 'TonKG']);

        $gradeSortRank = static function (string $section, string $grade): int {
            $upper = strtoupper(trim($grade));
            if ($section === 'INPUT') {
                if (str_contains($upper, 'AFKIR')) {
                    return 10;
                }
                if (str_contains($upper, 'MC')) {
                    return 20;
                }
                if (str_contains($upper, 'STD')) {
                    return 30;
                }

                return 99;
            }

            if (str_contains($upper, 'KAYU LAT')) {
                return 10;
            }
            if (preg_match('/\bMC\s*1\b/i', $upper) === 1) {
                return 20;
            }
            if (preg_match('/\bMC\s*2\b/i', $upper) === 1) {
                return 30;
            }
            if (str_contains($upper, 'STD')) {
                return 40;
            }

            return 99;
        };

        $isOutputGrade = static function (string $grade, float $kbTon, float $stTon): bool {
            $upper = strtoupper(trim($grade));

            if ($upper === '') {
                return $stTon > 0 && $kbTon <= 0;
            }

            if (str_contains($upper, 'KAYU LAT')) {
                return true;
            }

            if (preg_match('/^MC\s*[0-9]+$/i', $upper) === 1) {
                return true;
            }

            if ($upper === 'STD') {
                return true;
            }

            if (str_contains($upper, '-')) {
                return false;
            }

            return $stTon > 0 && $kbTon <= 0;
        };

        $normalizeOutputGradeKey = static function (string $grade): string {
            $upper = strtoupper(trim($grade));
            $normalized = preg_replace('/\s+/', ' ', $upper) ?? $upper;

            if (str_contains($normalized, 'KAYU LAT')) {
                return 'KAYU LAT';
            }
            if (preg_match('/\bMC\s*1\b/i', $normalized) === 1) {
                return 'MC 1';
            }
            if (preg_match('/\bMC\s*2\b/i', $normalized) === 1) {
                return 'MC 2';
            }
            if ($normalized === 'STD' || str_contains($normalized, ' STD')) {
                return 'STD';
            }

            return $normalized;
        };
    @endphp

    <h1 class="report-title">Laporan Penerimaan ST Dari Sawmill - Timbang KG</h1>
    <p class="report-subtitle">
        Periode {{ \Carbon\Carbon::parse((string) $startDate)->locale('id')->translatedFormat('d-M-y') }} s/d
        {{ \Carbon\Carbon::parse((string) $endDate)->locale('id')->translatedFormat('d-M-y') }}
    </p>

    @forelse ($groupsData as $group)
        @php
            $noPenerimaanSt = (string) ($group['no_penerimaan_st'] ?? ($group['supplier'] ?? 'Tanpa No Penerimaan ST'));
            $supplierName = (string) ($group['supplier'] ?? 'Tanpa Supplier');
            $groupRows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            $contextRow = $groupRows[0] ?? [];

            foreach ($groupRows as $row) {
                $inOutValue = $sectionColumn !== null ? trim((string) ($row[$sectionColumn] ?? '')) : '';
                $hasDate = $dateColumn !== null ? trim((string) ($row[$dateColumn] ?? '')) !== '' : false;
                $hasNoKb = $noKbColumn !== null ? trim((string) ($row[$noKbColumn] ?? '')) !== '' : false;
                if ($inOutValue === '1' && ($hasDate || $hasNoKb)) {
                    $contextRow = $row;
                    break;
                }
            }

            $cleanSupplier = trim(
                (string) (
                    $contextRow['NmSupplier3'] ??
                    $contextRow['NmSupplier2'] ??
                    $contextRow[$supplierColumnResolved] ??
                    $supplierName
                ),
            );
            if ($cleanSupplier === '') {
                $cleanSupplier = $supplierName;
            }

            $displayNoPenSt =
                $noPenStColumn !== null ? $contextRow[$noPenStColumn] ?? $noPenerimaanSt : $noPenerimaanSt;
            $displayTanggal = $dateColumn !== null ? $formatDate($contextRow[$dateColumn] ?? '') : '';
            $displayNoKb = $noKbColumn !== null ? $contextRow[$noKbColumn] ?? '' : '';
            $displayJenisKayu = $jenisKayuColumn !== null ? $contextRow[$jenisKayuColumn] ?? '' : '';
            $displayTruk = $trukColumn !== null ? $contextRow[$trukColumn] ?? '' : '';
            $displayMeja = $mejaColumn !== null ? $contextRow[$mejaColumn] ?? '' : '';

            $inputByGrade = [];
            $outputByGrade = [];

            foreach ($groupRows as $row) {
                $gradeName = trim((string) ($gradeColumn !== null ? $row[$gradeColumn] ?? '' : ''));
                $kbTonValue = $kbTonColumn !== null ? $toFloat($row[$kbTonColumn] ?? null) ?? 0.0 : 0.0;
                $stTonValue = $stTonColumn !== null ? $toFloat($row[$stTonColumn] ?? null) ?? 0.0 : 0.0;
                $jmlhTrukValue = $jmlhTrukColumn !== null ? $toFloat($row[$jmlhTrukColumn] ?? null) ?? 0.0 : 0.0;

                $rawSection = $sectionColumn !== null ? strtoupper(trim((string) ($row[$sectionColumn] ?? ''))) : '';
                if ($rawSection !== '') {
                    $resolvedSection = is_numeric($rawSection)
                        ? ((int) $rawSection === 0 ? 'OUTPUT' : 'INPUT')
                        : (str_contains($rawSection, 'OUT') ? 'OUTPUT' : 'INPUT');
                } else {
                    $resolvedSection = $isOutputGrade($gradeName, $kbTonValue, $stTonValue) ? 'OUTPUT' : 'INPUT';
                }

                if ($resolvedSection === 'OUTPUT') {
                    $gradeKey = $normalizeOutputGradeKey($gradeName !== '' ? $gradeName : '-');
                    if (!isset($outputByGrade[$gradeKey])) {
                        $outputByGrade[$gradeKey] = [
                            'grade' => $gradeKey,
                            'jmlh_truk' => 0.0,
                            'kb_ton' => 0.0,
                            'st_ton' => 0.0,
                        ];
                    }
                    $outputByGrade[$gradeKey]['st_ton'] += $stTonValue;
                } else {
                    $gradeKey = strtoupper(trim($gradeName !== '' ? $gradeName : '-'));
                    if (!isset($inputByGrade[$gradeKey])) {
                        $inputByGrade[$gradeKey] = [
                            'grade' => $gradeName !== '' ? $gradeName : '-',
                            'jmlh_truk' => 0.0,
                            'kb_ton' => 0.0,
                            'st_ton' => 0.0,
                        ];
                    }
                    $inputByGrade[$gradeKey]['jmlh_truk'] += $jmlhTrukValue;
                    $inputByGrade[$gradeKey]['kb_ton'] += $kbTonValue;
                }
            }

            $inputRows = array_values($inputByGrade);
            usort($inputRows, function (array $a, array $b) use ($gradeSortRank): int {
                return $gradeSortRank('INPUT', (string) $a['grade']) <=> $gradeSortRank('INPUT', (string) $b['grade']);
            });

            $expectedOutputOrder = ['KAYU LAT', 'MC 1', 'MC 2', 'STD'];
            $outputRows = [];
            foreach ($expectedOutputOrder as $outputGrade) {
                $existing = $outputByGrade[$outputGrade] ?? null;
                $outputRows[] = [
                    'grade' => $outputGrade,
                    'jmlh_truk' => 0.0,
                    'kb_ton' => 0.0,
                    'st_ton' => (float) ($existing['st_ton'] ?? 0.0),
                ];
            }
            foreach ($outputByGrade as $key => $existing) {
                if (in_array($key, $expectedOutputOrder, true)) {
                    continue;
                }
                $outputRows[] = [
                    'grade' => (string) ($existing['grade'] ?? $key),
                    'jmlh_truk' => 0.0,
                    'kb_ton' => 0.0,
                    'st_ton' => (float) ($existing['st_ton'] ?? 0.0),
                ];
            }
            usort($outputRows, function (array $a, array $b) use ($gradeSortRank): int {
                return $gradeSortRank('OUTPUT', (string) $a['grade']) <=> $gradeSortRank('OUTPUT', (string) $b['grade']);
            });

            $totalInputKb = array_reduce($inputRows, fn($carry, $item) => $carry + (float) $item['kb_ton'], 0.0);
            $totalOutputSt = array_reduce($outputRows, fn($carry, $item) => $carry + (float) $item['st_ton'], 0.0);
            $rendemen = $totalInputKb > 0 ? ($totalOutputSt / $totalInputKb) * 100 : 0.0;

            $inputRows = array_map(static function (array $item) use ($totalInputKb): array {
                $item['input_percent'] = $totalInputKb > 0 ? ((float) $item['kb_ton'] / $totalInputKb) * 100 : 0.0;
                return $item;
            }, $inputRows);

            $outputRows = array_map(static function (array $item) use ($totalOutputSt): array {
                $item['output_percent'] = $totalOutputSt > 0 ? ((float) $item['st_ton'] / $totalOutputSt) * 100 : 0.0;
                return $item;
            }, $outputRows);
        @endphp

        @if (!$loop->first)
            <div class="date-separator"></div>
        @endif

        <div class="group-title">Supplier : {{ $cleanSupplier }}</div>

        <div class="meta-block">
            <div class="meta-row"><span class="meta-label">No Pen ST</span>: {{ $displayNoPenSt !== '' ? $displayNoPenSt : '-' }}</div>
            <div class="meta-row"><span class="meta-label">No KB</span>: {{ $displayNoKb !== '' ? $displayNoKb : '-' }}</div>
            <div class="meta-row"><span class="meta-label">Tanggal Penerimaan</span>: {{ $displayTanggal !== '' ? $displayTanggal : '-' }}</div>
            <div class="meta-row"><span class="meta-label">Truk</span>: {{ $displayTruk !== '' ? $displayTruk : '-' }}</div>
            <div class="meta-row"><span class="meta-label">Meja</span>: {{ $displayMeja !== '' ? $displayMeja : '-' }}</div>
            <div class="meta-row"><span class="meta-label">Jenis Kayu</span>: {{ $displayJenisKayu !== '' ? $displayJenisKayu : '-' }}</div>
        </div>

        <table class="report-table">
            <colgroup>
                <col style="width: 68px;">
                <col style="width: auto;">
                <col style="width: 72px;">
                <col style="width: 84px;">
                <col style="width: 84px;">
                <col style="width: 88px;">
                <col style="width: 92px;">
            </colgroup>
            <thead>
                <tr class="headers-row">
                    <th>Kategori</th>
                    <th>Grade</th>
                    <th>Jmlh Truk</th>
                    <th>KB (Ton)</th>
                    <th>ST (Ton)</th>
                    <th>Persentase Input (%)</th>
                    <th>Persentase Output (%)</th>
                </tr>
            </thead>
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="7"></td>
                </tr>
            </tfoot>
            <tbody>
                @php $rowIndex = 0; @endphp

                @if ($inputRows !== [])
                    @php $rowspan = count($inputRows); @endphp
                    @foreach ($inputRows as $line)
                        @php $rowIndex++; @endphp
                        <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            @if ($loop->first)
                                <td class="data-cell section-cell" rowspan="{{ $rowspan }}">INPUT</td>
                            @endif
                            <td class="data-cell">{{ (string) ($line['grade'] ?? '') }}</td>
                            <td class="data-cell center">{{ $formatTruck($line['jmlh_truk'] ?? '') }}</td>
                            <td class="data-cell number">{{ $formatDetail((float) ($line['kb_ton'] ?? 0.0), 2) }}</td>
                            <td class="data-cell center"></td>
                            <td class="data-cell number">{{ $formatPercent((float) ($line['input_percent'] ?? 0.0), 1) }}</td>
                            <td class="data-cell center"></td>
                        </tr>
                    @endforeach
                @endif

                @if ($outputRows !== [])
                    @php $rowspan = count($outputRows); @endphp
                    @foreach ($outputRows as $line)
                        @php $rowIndex++; @endphp
                        <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            @if ($loop->first)
                                <td class="data-cell section-cell" rowspan="{{ $rowspan }}">OUTPUT</td>
                            @endif
                            <td class="data-cell grade-output">{{ (string) ($line['grade'] ?? '') }}</td>
                            <td class="data-cell center"></td>
                            <td class="data-cell center"></td>
                            <td class="data-cell number">{{ $formatDetail((float) ($line['st_ton'] ?? 0.0), 4) }}</td>
                            <td class="data-cell center"></td>
                            <td class="data-cell number">{{ $formatPercent((float) ($line['output_percent'] ?? 0.0), 1) }}</td>
                        </tr>
                    @endforeach
                @endif

                @if ($inputRows === [] && $outputRows === [])
                    <tr class="data-row row-odd">
                        <td class="data-cell center" colspan="7">Tidak ada data.</td>
                    </tr>
                @else
                    <tr class="totals-row">
                        <td colspan="3" class="center">Jumlah:</td>
                        <td class="number">{{ $formatTotal($totalInputKb, 2) }}</td>
                        <td class="number">{{ $formatTotal($totalOutputSt, 4) }}</td>
                        <td></td>
                        <td></td>
                    </tr>
                @endif
            </tbody>
        </table>

        @if ($inputRows !== [] || $outputRows !== [])
            <div class="rendemen-row">RENDEMEN : {{ number_format($rendemen, 1, '.', ',') }}%</div>
        @endif
    @empty
        <table class="report-table">
            <thead>
                <tr class="headers-row">
                    <th>Penerimaan ST Dari Sawmill - Timbang KG</th>
                </tr>
            </thead>
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="1"></td>
                </tr>
            </tfoot>
            <tbody>
                <tr class="data-row row-odd">
                    <td class="data-cell center">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @include('reports.partials.pdf-footer-table')
</body>

</html>
