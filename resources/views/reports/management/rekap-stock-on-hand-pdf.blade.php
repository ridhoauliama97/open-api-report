<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: "Noto Serif", serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .report-companyTitle {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 0 0 4px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 0;
        }
        .report-subtitle {
            font-size: 12px;
            color: #636466;
            text-align: center;
            margin: 2px 0 20px;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #000;            padding: 1px 2px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .data-table th {
            background-color: #eef2f8;
            font-weight: bold;
        }
        .section-header td {
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .sub-section-header td {
            font-weight: bold;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .item-row td {
            padding-left: 4px;
        }
        .row-odd td {
            background-color: #c9d1df;
        }
        .row-even td {
            background-color: #eef2f8;
        }
        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .empty-row td {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background-color: #c9d1df;
        }
        .number {
            text-align: right;
        }
        .nowrap {
            white-space: nowrap;
        }
        .number-negative {
            color: #9c111d;
        }
    
        /* standardized table borders */
        .report-table, .summary-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            line-height: inherit;
        }
.report-table th, .report-table td, .summary-table th, .summary-table td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }

        .report-table th { font-weight: bold; background-color: #eef2f8; }

        .summary-table th { font-weight: bold; background-color: #eef2f8; }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];
        $summaryRows = is_array($data['summary_rows'] ?? null) ? $data['summary_rows'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $fmt4 = static fn($value): string => $value === null || !is_numeric($value)
            ? ''
            : number_format((float) $value, 4, '.', ',');
        $fmt0 = static fn($value): string => $value === null || !is_numeric($value)
            ? ''
            : number_format((float) $value, 0, '.', ',');
        $headerMap = [
            'NoKayuBulat' => 'No KB',
            'NoST' => 'No ST',
            'NoS4S' => 'No S4S',
            'NoFJ' => 'No FJ',
            'NoMoulding' => 'No Moulding',
            'NoLaminating' => 'No Laminating',
            'NoCCAkhir' => 'No CCA',
            'NoSanding' => 'No Sanding',
            'NoBJ' => 'No BJ',
            'NoReproses' => 'No Reproses',
            'NamaBarangJadi' => 'Nama Barang Jadi',
            'NamaGrade' => 'Nama Grade',
            'JmlhBatang' => 'Pcs',
            'Kubik' => 'm3',
            'Ton' => 'Ton',
        ];
    @endphp

    <h1 class="report-title">Laporan Rekap Stock On Hand</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    @foreach ($sections as $sectionIndex => $section)
        <div class="section-title">{{ $sectionIndex + 1 }}. {{ $section['label'] ?? '-' }}</div>
        @if (!empty($section['compact_note']))
            <div style="margin: 0 0 4px 0; font-size: 10px; color: #444;">
                {{ $section['compact_note'] }}
                @if (($section['displayed_row_count'] ?? 0) !== ($section['row_count'] ?? 0))
                    ({{ $fmt0($section['row_count'] ?? null) }} baris asli menjadi
                    {{ $fmt0($section['displayed_row_count'] ?? null) }} baris rekap)
                @endif
            </div>
        @endif
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 36px;">No</th>
                    @foreach ($section['columns'] ?? [] as $column)
                        @php
                            $header = $headerMap[$column] ?? $column;
                            $small = in_array(
                                $column,
                                ['Tebal', 'Lebar', 'Panjang', 'JmlhBatang', 'Ton', 'Kubik'],
                                true,
                            );
                        @endphp
                        <th style="{{ $small ? 'width: 64px;' : '' }}">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse (($section['rows'] ?? []) as $rowIndex => $row)
                    <tr class="{{ ($rowIndex + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $rowIndex + 1 }}</td>
                        @foreach ($section['columns'] ?? [] as $column)
                            @php $value = $row[$column] ?? null; @endphp
                            @if (in_array($column, ['Tebal', 'Lebar', 'Panjang'], true))
                                <td class="center">{{ $fmt0($value) }}</td>
                            @elseif (in_array($column, ['JmlhBatang'], true))
                                <td class="number">{{ $fmt0($value) }}</td>
                            @elseif (in_array($column, ['Ton', 'Kubik'], true))
                                <td class="number">{{ $fmt4($value) }}</td>
                            @else
                                <td>{{ (string) $value }}</td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($section['columns'] ?? []) + 1 }}" class="empty-state">Tidak ada data.
                        </td>
                    </tr>
                @endforelse
                @php
                    $sectionColumns = is_array($section['columns'] ?? null) ? $section['columns'] : [];
                    $pcsColumnIndex = array_search('JmlhBatang', $sectionColumns, true);
                    $valueColumnIndex = array_search('Ton', $sectionColumns, true);
                    if ($valueColumnIndex === false) {
                        $valueColumnIndex = array_search('Kubik', $sectionColumns, true);
                    }
                    $labelColspan = 1;
                    if ($pcsColumnIndex !== false) {
                        $labelColspan = $pcsColumnIndex + 1;
                    } elseif ($valueColumnIndex !== false) {
                        $labelColspan = $valueColumnIndex + 1;
                    } elseif ($sectionColumns !== []) {
                        $labelColspan = count($sectionColumns);
                    }
                @endphp
                <tr class="total-row">
                    <td colspan="{{ $labelColspan }}" class="center">Total {{ $section['label'] ?? '-' }}</td>
                    @foreach ($sectionColumns as $columnIndex => $column)
                        @if ($columnIndex + 2 <= $labelColspan)
                            @continue
                        @endif
                        @if ($column === 'JmlhBatang')
                            <td class="number">{{ $fmt0($section['total_pcs'] ?? null) }}</td>
                        @elseif (in_array($column, ['Ton', 'Kubik'], true))
                            <td class="number">{{ $fmt4($section['total_value'] ?? null) }}</td>
                        @else
                            <td></td>
                        @endif
                    @endforeach
                </tr>
            </tbody>
        </table>
    @endforeach

    @if ($summaryRows !== [])
        <div class="section-title">Rangkuman</div>
        <table class="report-table summary-table">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th style="width: 70px;">Dokumen</th>
                    <th style="width: 70px;">Baris</th>
                    <th style="width: 80px;">Total Pcs</th>
                    <th style="width: 90px;">Total</th>
                    <th style="width: 50px;">Unit</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summaryRows as $index => $row)
                    <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td>{{ $row['Kategori'] ?? '-' }}</td>
                        <td class="number">{{ $fmt0($row['Dokumen'] ?? null) }}</td>
                        <td class="number">{{ $fmt0($row['Baris'] ?? null) }}</td>
                        <td class="number">{{ $fmt0($row['TotalPcs'] ?? null) }}</td>
                        <td class="number">{{ $fmt4($row['TotalValue'] ?? null) }}</td>
                        <td class="center">{{ $row['Unit'] ?? '-' }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" class="center">Grand Total</td>
                    <td class="number">{{ $fmt0($summary['total_pcs'] ?? null) }}</td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @endif

    </body>

</html>