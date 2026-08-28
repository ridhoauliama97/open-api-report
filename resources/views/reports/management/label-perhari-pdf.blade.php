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
        $categories = is_array($data['categories'] ?? null) ? $data['categories'] : [];
        $summaryRows = is_array($data['summary_rows'] ?? null) ? $data['summary_rows'] : [];
        $grandTotals = is_array($data['grand_totals'] ?? null) ? $data['grand_totals'] : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');

        $fmtNumber = static function ($value, int $decimals = 4, bool $blankWhenZero = true): string {
            if ($value === null || !is_numeric($value)) {
                return '';
            }

            $float = (float) $value;
            if ($blankWhenZero && abs($float) < 0.0000001) {
                return '';
            }

            return number_format($float, $decimals, '.', ',');
        };

        $fmtInt = static function ($value): string {
            if ($value === null || !is_numeric($value)) {
                return '';
            }

            return number_format((float) $value, 0, '.', ',');
        };

        $resolveWeightUnit = static function (?string $categoryName): string {
            return str_contains(strtoupper(trim((string) $categoryName)), 'ST') ? 'Ton' : 'm3';
        };

        $fmtWeightWithUnit = static function ($value, ?string $categoryName) use ($fmtNumber, $resolveWeightUnit, ): string {
            $formatted = $fmtNumber($value, 4, true);

            if ($formatted === '') {
                return '';
            }

            return $formatted . ' ' . $resolveWeightUnit($categoryName);
        };
    @endphp

    <h1 class="report-title">Laporan Label Perhari</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    @foreach ($categories as $category)
        @php
            $chunkRowsList = array_chunk($category['rows'] ?? [], 300);
        @endphp
        <div class="section-title">
            {{ $category['no'] ?? '' }}. {{ $category['name'] ?? '-' }}
        </div>
        @foreach ($chunkRowsList as $chunkIndex => $chunkRows)
            @if ($chunkIndex > 0)
                <div class="chunk-page-break"></div>
            @endif
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 36px;">No</th>
                        <th style="width: 88px;">No Label</th>
                        <th style="width: 56px;">Urut</th>
                        <th style="width: 82px;">No SPK</th>
                        <th style="width: 82px;">SPK Asal</th>
                        <th style="width: 96px;">Mesin</th>
                        <th>Jenis</th>
                        <th style="width: 54px;">Tebal</th>
                        <th style="width: 54px;">Lebar</th>
                        <th style="width: 58px;">Panjang</th>
                        <th style="width: 58px;">Pcs</th>
                        <th style="width: 84px;">Berat</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($chunkRows as $index => $row)
                        <tr class="{{ ($chunkIndex * 300 + $index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            <td class="center">{{ $chunkIndex * 300 + $index + 1 }}</td>
                            <td class="center">{{ ($row['NoLabel'] ?? '') !== '' ? $row['NoLabel'] : '-' }}</td>
                            <td class="center">{{ ($row['NoUrut'] ?? '') !== '' ? $row['NoUrut'] : '-' }}</td>
                            <td class="center">{{ $row['NoSPK'] ?: '-' }}</td>
                            <td class="center">{{ $row['NoSPKAsal'] ?: '-' }}</td>
                            <td class="center">{{ $row['Mesin'] ?: '-' }}</td>
                            <td class="center">{{ $row['Jenis'] ?? '-' }}</td>
                            <td class="center">{{ ($row['Tebal'] ?? '') !== '' ? $row['Tebal'] : '-' }}</td>
                            <td class="center">{{ ($row['Lebar'] ?? '') !== '' ? $row['Lebar'] : '-' }}</td>
                            <td class="center">{{ ($row['Panjang'] ?? '') !== '' ? $row['Panjang'] : '-' }}</td>
                            <td class="number">{{ $fmtInt($row['JmlhBatang'] ?? null) }}</td>
                            <td class="number">{!! $fmtWeightWithUnit($row['Berat'] ?? null, $category['name'] ?? null) !!}</td>
                        </tr>
                    @endforeach
                    @if ($chunkIndex === count($chunkRowsList) - 1)
                        <tr class="total-row">
                            <td colspan="10" class="center">Total {{ $category['name'] ?? '-' }}</td>
                            <td class="number">{{ $fmtInt($category['total_pcs'] ?? null) }}</td>
                            <td class="number">
                                {!! $fmtWeightWithUnit($category['total_berat'] ?? null, $category['name'] ?? null) !!}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endforeach
    @endforeach

    <div class="section-title">Rangkuman</div>
    <table class="report-table summary-table">
        <thead>
            <tr>
                <th>Kategori</th>
                <th style="width: 84px;">Jumlah</th>
                <th style="width: 90px;">Total Pcs</th>
                <th style="width: 90px;">Total Berat</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summaryRows as $index => $row)
                <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td>{{ $row['Kategori'] ?? '-' }}</td>
                    <td class="number">{{ $fmtInt($row['LabelCount'] ?? null) }}</td>
                    <td class="number">{{ $fmtInt($row['TotalPcs'] ?? null) }}</td>
                    <td class="number">{!! $fmtWeightWithUnit($row['TotalBerat'] ?? null, $row['Kategori'] ?? null) !!}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td class="center">Grand Total</td>
                <td class="number">{{ $fmtInt($grandTotals['label_count'] ?? null) }}</td>
                <td class="number">{{ $fmtInt($grandTotals['pcs'] ?? null) }}</td>
                <td class="number">{{ $fmtNumber($grandTotals['berat'] ?? null, 2, true) }}</td>
            </tr>
        </tbody>
    </table>

    </body>

</html>
