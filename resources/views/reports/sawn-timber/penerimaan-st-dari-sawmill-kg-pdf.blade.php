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
            font-size: 9.5px;
            line-height: 1.2;
            color: #000;
        }

        .report-title {
            text-align: center;
            margin: 0;
            font-size: 13px;
            font-weight: 700;
        }

        .report-subtitle {
            text-align: left;
            margin: 2px 0 10px 0;
            font-size: 9px;
            color: #555;
        }

        .supplier-block {
            margin: 8px 0 12px 0;
            page-break-inside: avoid;
        }

        .supplier-title {
            margin: 0 0 5px 0;
            font-size: 11px;
            font-weight: 700;
            border-bottom: 1px solid #222;
            padding-bottom: 2px;
        }

        .meta-list {
            width: 100%;
            border: 1px solid #9aa4b2;
            background: #f8fafc;
            border-radius: 2px;
            margin-bottom: 6px;
            border-collapse: collapse;
        }

        .meta-list td {
            border: none;
            padding: 3px 5px;
            vertical-align: top;
            font-size: 9px;
        }

        .meta-label {
            color: #4b5563;
            width: 25%;
            white-space: nowrap;
        }

        .meta-value {
            color: #111827;
            font-weight: 600;
            width: 25%;
        }

        .section-label {
            width: 58px;
            text-align: center;
            font-weight: 700;
            background: #eef2f7;
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
            border: 1px solid #4b5563;
            padding: 2.5px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
            background: #e5eaf1;
        }

        td.center {
            text-align: center;
        }

        td.number {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        td.grade-output {
            text-align: right;
            padding-right: 10px;
            white-space: nowrap;
        }

        .subtotal-row td {
            font-weight: 700;
            background: #edf2f8 !important;
        }

        .rendemen {
            text-align: right;
            font-size: 9.5px;
            font-weight: 700;
            margin: 0 0 8px 0;
        }

        .summary-table {
            width: 48%;
            margin-top: 8px;
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
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

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

        $isDateLike = static function (?string $column): bool {
            if ($column === null) {
                return false;
            }

            $normalized = strtolower(str_replace([' ', '_'], '', $column));

            return str_contains($normalized, 'tgl') ||
                str_contains($normalized, 'tanggal') ||
                str_contains($normalized, 'date');
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
                return \Carbon\Carbon::parse((string) $value)->format('d M Y');
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };

        $dateColumn = $findColumn($availableColumns, ['TglLaporan', 'Tanggal', 'DateCreate', 'TanggalST', 'TglPenerimaanST', 'Date']);
        $supplierColumnResolved = $supplierColumn ?? $findColumn($availableColumns, ['Supplier', 'NmSupplier', 'Nama Supplier']);
        $sawmillColumn = $findColumn($availableColumns, ['Sawmill', 'NamaSawmill']);
        $noPenStColumn = $findColumn($availableColumns, ['NoPenST', 'No Pen ST', 'NoPenerimaanST']);
        $noKbColumn = $findColumn($availableColumns, ['NoKB', 'No KB', 'NoKayuBulat']);
        $jenisKayuColumn = $findColumn($availableColumns, ['JenisKayu', 'Jenis Kayu', 'Jenis']);
        $trukColumn = $findColumn($availableColumns, ['NoTruk', 'Truk']);
        $mejaColumn = $findColumn($availableColumns, ['Meja', 'NoMeja']);
        $sectionColumn = $findColumn($availableColumns, ['InOut', 'InputOutput', 'Kelompok', 'Kategori', 'Status', 'Posisi']);
        $gradeColumn = $findColumn($availableColumns, ['Grade', 'NamaGrade']);
        $jmlhTrukColumn = $findColumn($availableColumns, ['JmlhTruk', 'JumlahTruk', 'JmlTruk']);
        $kbTonColumn = $findColumn($availableColumns, ['KB (Ton)', 'KBTon', 'TonKB', 'KBTon']);
        $stTonColumn = $findColumn($availableColumns, ['ST (Ton)', 'STTon', 'TonST', 'TonKG']);
        $summaryData = is_array($summary ?? null) ? $summary : [];
        $summarySuppliers = (int) ($summaryData['total_suppliers'] ?? count($groupsData));
        $summaryRows = (int) ($summaryData['total_rows'] ?? count($rowsData));

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

            // Input grades usually contain source/type prefix (e.g. RAMBUNG - STD-630).
            if (str_contains($upper, '-')) {
                return false;
            }

            if (str_contains($upper, 'STD') && !str_contains($upper, 'RAMBUNG')) {
                return true;
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
        Periode {{ \Carbon\Carbon::parse((string) $startDate)->locale('id')->translatedFormat('d F Y') }} s/d
        {{ \Carbon\Carbon::parse((string) $endDate)->locale('id')->translatedFormat('d F Y') }}
        | Group by Supplier
    </p>

    @forelse ($groupsData as $group)
        @php
            $noPenerimaanSt = (string) ($group['no_penerimaan_st'] ?? ($group['supplier'] ?? 'Tanpa No Penerimaan ST'));
            $supplierName = (string) ($group['supplier'] ?? 'Tanpa Supplier');
            $groupRows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            $headRow = $groupRows[0] ?? [];
            $contextRow = $headRow;
            foreach ($groupRows as $row) {
                $inOutValue = $sectionColumn !== null ? trim((string) ($row[$sectionColumn] ?? '')) : '';
                $hasDate = $dateColumn !== null ? trim((string) ($row[$dateColumn] ?? '')) !== '' : false;
                $hasNoKb = $noKbColumn !== null ? trim((string) ($row[$noKbColumn] ?? '')) !== '' : false;

                if ($inOutValue === '1' && ($hasDate || $hasNoKb)) {
                    $contextRow = $row;
                    break;
                }
            }

            $displayNoPenSt = $noPenStColumn !== null ? ($contextRow[$noPenStColumn] ?? $noPenerimaanSt) : $noPenerimaanSt;
            $displayTglPenerimaan = $dateColumn !== null ? $formatDate($contextRow[$dateColumn] ?? '') : '';
            $displayNoKb = $noKbColumn !== null ? ($contextRow[$noKbColumn] ?? '') : '';
            $displayJenisKayu = $jenisKayuColumn !== null ? ($contextRow[$jenisKayuColumn] ?? '') : '';
            $displayTruk = $trukColumn !== null ? ($contextRow[$trukColumn] ?? '') : '';
            $displayMeja = $mejaColumn !== null ? ($contextRow[$mejaColumn] ?? '') : '';

            $inputRows = [];
            $outputRows = [];
            $inputByGrade = [];
            $outputByGrade = [];

            foreach ($groupRows as $row) {
                $gradeName = trim((string) ($gradeColumn !== null ? ($row[$gradeColumn] ?? '') : ''));
                $kbTonValue = $kbTonColumn !== null ? ($toFloat($row[$kbTonColumn] ?? null) ?? 0.0) : 0.0;
                $stTonValue = $stTonColumn !== null ? ($toFloat($row[$stTonColumn] ?? null) ?? 0.0) : 0.0;
                $jmlhTrukValue = $jmlhTrukColumn !== null ? ($toFloat($row[$jmlhTrukColumn] ?? null) ?? 0.0) : 0.0;

                $rawSection = $sectionColumn !== null ? strtoupper(trim((string) ($row[$sectionColumn] ?? ''))) : '';
                $resolvedSection = '';
                if ($rawSection !== '') {
                    if (is_numeric($rawSection)) {
                        $resolvedSection = ((int) $rawSection) === 0 ? 'OUTPUT' : 'INPUT';
                    } else {
                        $resolvedSection = str_contains($rawSection, 'OUT') ? 'OUTPUT' : 'INPUT';
                    }
                } else {
                    $resolvedSection = $isOutputGrade($gradeName, $kbTonValue, $stTonValue) ? 'OUTPUT' : 'INPUT';
                }

                $normalized = [
                    'grade' => $gradeName !== '' ? $gradeName : '-',
                    'jmlh_truk' => $jmlhTrukValue,
                    'kb_ton' => $kbTonValue,
                    'st_ton' => $stTonValue,
                ];

                if ($resolvedSection === 'OUTPUT') {
                    $gradeKey = $normalizeOutputGradeKey((string) $normalized['grade']);
                    if (!isset($outputByGrade[$gradeKey])) {
                        $outputByGrade[$gradeKey] = [
                            'grade' => $gradeKey,
                            'jmlh_truk' => 0.0,
                            'kb_ton' => 0.0,
                            'st_ton' => 0.0,
                        ];
                    }
                    $outputByGrade[$gradeKey]['st_ton'] += (float) $normalized['st_ton'];
                } else {
                    $gradeKey = strtoupper(trim((string) $normalized['grade']));
                    if (!isset($inputByGrade[$gradeKey])) {
                        $inputByGrade[$gradeKey] = [
                            'grade' => (string) $normalized['grade'],
                            'jmlh_truk' => 0.0,
                            'kb_ton' => 0.0,
                            'st_ton' => 0.0,
                        ];
                    }
                    $inputByGrade[$gradeKey]['jmlh_truk'] += (float) $normalized['jmlh_truk'];
                    $inputByGrade[$gradeKey]['kb_ton'] += (float) $normalized['kb_ton'];
                }
            }

            $inputRows = array_values($inputByGrade);

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

            usort(
                $inputRows,
                function (array $a, array $b) use ($gradeSortRank): int {
                    return $gradeSortRank('INPUT', (string) $a['grade']) <=> $gradeSortRank('INPUT', (string) $b['grade']);
                },
            );
            usort(
                $outputRows,
                function (array $a, array $b) use ($gradeSortRank): int {
                    return $gradeSortRank('OUTPUT', (string) $a['grade']) <=> $gradeSortRank('OUTPUT', (string) $b['grade']);
                },
            );

            $totalInputKb = 0.0;
            foreach ($inputRows as $item) {
                $totalInputKb += (float) $item['kb_ton'];
            }

            $totalOutputSt = 0.0;
            foreach ($outputRows as $item) {
                $totalOutputSt += (float) $item['st_ton'];
            }

            $totalKb = $totalInputKb;
            $totalSt = $totalOutputSt;
            $rendemen = $totalKb > 0 ? ($totalSt / $totalKb) * 100 : 0.0;

            $inputRows = array_map(static function (array $item) use ($totalInputKb): array {
                $item['input_percent'] = $totalInputKb > 0 ? (((float) $item['kb_ton'] / $totalInputKb) * 100) : 0.0;
                $item['output_percent'] = 0.0;
                return $item;
            }, $inputRows);

            $outputRows = array_map(static function (array $item) use ($totalOutputSt): array {
                $item['input_percent'] = 0.0;
                $item['output_percent'] = $totalOutputSt > 0 ? (((float) $item['st_ton'] / $totalOutputSt) * 100) : 0.0;
                return $item;
            }, $outputRows);

            $renderRows = [
                [
                    'section' => 'INPUT',
                    'rows' => count($inputRows) > 0 ? $inputRows : [['grade' => '-', 'jmlh_truk' => 0.0, 'kb_ton' => 0.0, 'st_ton' => 0.0, 'input_percent' => 0.0, 'output_percent' => 0.0]],
                ],
                [
                    'section' => 'OUTPUT',
                    'rows' => count($outputRows) > 0 ? $outputRows : [['grade' => '-', 'jmlh_truk' => 0.0, 'kb_ton' => 0.0, 'st_ton' => 0.0, 'input_percent' => 0.0, 'output_percent' => 0.0]],
                ],
            ];

            $metaPairs = [
                'No Pen ST' => $displayNoPenSt,
                'Supplier' => $supplierColumnResolved !== null ? ($contextRow[$supplierColumnResolved] ?? $supplierName) : $supplierName,
                'No KB' => $displayNoKb,
                'Tgl Penerimaan ST Sawmill' => $displayTglPenerimaan,
                'Jenis Kayu' => $displayJenisKayu,
                'Truk' => $displayTruk,
                'Meja' => $displayMeja,
            ];
        @endphp
        <div class="supplier-block">
            <p class="supplier-title">Supplier : {{ $supplierName }}</p>

            <table class="meta-list">
                <tbody>
                    <tr>
                        <td class="meta-label">Supplier</td>
                        <td class="meta-value">{{ (string) ($metaPairs['Supplier'] ?: '-') }}</td>
                        <td class="meta-label">No KB</td>
                        <td class="meta-value">{{ (string) ($metaPairs['No KB'] ?: '-') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tgl Penerimaan ST Sawmill</td>
                        <td class="meta-value">{{ (string) ($metaPairs['Tgl Penerimaan ST Sawmill'] ?: '-') }}</td>
                        <td class="meta-label">Jenis Kayu</td>
                        <td class="meta-value">{{ (string) ($metaPairs['Jenis Kayu'] ?: '-') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Truk</td>
                        <td class="meta-value">{{ (string) ($metaPairs['Truk'] ?: '-') }}</td>
                        <td class="meta-label">Meja</td>
                        <td class="meta-value">{{ (string) ($metaPairs['Meja'] ?: '-') }}</td>
                    </tr>
                </tbody>
            </table>

            <table>
                <thead>
                    <tr>
                        <th style="width: 58px;"></th>
                        <th>Grade</th>
                        <th style="width: 76px;">Jmlh Truk</th>
                        <th style="width: 84px;">KB (Ton)</th>
                        <th style="width: 84px;">ST (Ton)</th>
                        <th style="width: 86px;">Persentase Input (%)</th>
                        <th style="width: 90px;">Persentase Output (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($renderRows as $sectionBlock)
                        @php
                            $sectionName = $sectionBlock['section'];
                            $sectionRows = $sectionBlock['rows'];
                            $rowCount = count($sectionRows);
                        @endphp
                        @foreach ($sectionRows as $rowIndex => $row)
                            <tr>
                                @if ($rowIndex === 0)
                                    <td class="section-label" rowspan="{{ $rowCount }}">{{ $sectionName }}</td>
                                @endif
                                <td class="{{ $sectionName === 'OUTPUT' ? 'grade-output' : '' }}">
                                    {{ (string) ($row['grade'] ?? '-') }}
                                </td>
                                <td class="center">
                                    {{ $sectionName === 'INPUT' ? number_format((float) ($row['jmlh_truk'] ?? 0), 0, '.', '') : '' }}
                                </td>
                                <td class="number">
                                    {{ $sectionName === 'INPUT' ? number_format((float) ($row['kb_ton'] ?? 0), 2, '.', '') : '' }}
                                </td>
                                <td class="number">
                                    {{ $sectionName === 'OUTPUT' ? number_format((float) ($row['st_ton'] ?? 0), 4, '.', '') : '' }}
                                </td>
                                <td class="number">
                                    {{ $sectionName === 'INPUT' ? number_format((float) ($row['input_percent'] ?? 0), 1, '.', '') . '%' : '' }}
                                </td>
                                <td class="number">
                                    {{ $sectionName === 'OUTPUT' ? number_format((float) ($row['output_percent'] ?? 0), 1, '.', '') . '%' : '' }}
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                    <tr class="subtotal-row">
                        <td class="number" colspan="3">Jumlah:</td>
                        <td class="number">{{ number_format($totalKb, 2, '.', '') }}</td>
                        <td class="number">{{ number_format($totalSt, 4, '.', '') }}</td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            <p class="rendemen">RENDEMEN: {{ number_format($rendemen, 1, '.', '') }}%</p>
        </div>
    @empty
        <table>
            <tbody>
                <tr>
                    <td class="center">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    <table class="summary-table">
        <tbody>
            <tr>
                <th style="width: 70%;">Keterangan</th>
                <th>Nilai</th>
            </tr>
            <tr>
                <td>Total supplier</td>
                <td class="center">{{ number_format($summarySuppliers, 0, '.', '') }}</td>
            </tr>
            <tr>
                <td>Total baris data</td>
                <td class="center">{{ number_format($summaryRows, 0, '.', '') }}</td>
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
