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
        $date = \Carbon\Carbon::parse($reportDate)->locale('id')->translatedFormat('d-M-y');
        $fmt = static fn($value): string => $value === null ? '' : number_format((float) $value, 4, '.', ',');
    @endphp

    <h1 class="report-title">Laporan Rangkuman Bongkar Susun</h1>
    <div class="report-subtitle">Tanggal {{ $date }}</div>

    @forelse ($categories as $category)
        <div class="section-title">{{ $category['no'] ?? '' }}. {{ $category['name'] ?? '-' }}</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 36px;">No</th>
                    <th style="width: 110px;">No Bongkar Susun</th>
                    <th style="width: 110px;">Jenis</th>
                    <th style="width: 84px;">In</th>
                    <th style="width: 84px;">Out</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($category['rows'] ?? [] as $index => $row)
                    <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $index + 1 }}</td>
                        <td class="center">{{ ($row['NoBongkarSusun'] ?? '') !== '' ? $row['NoBongkarSusun'] : '-' }}
                        </td>
                        <td>{{ ($row['Jenis'] ?? '') !== '' ? $row['Jenis'] : '-' }}</td>
                        <td class="number">{{ $fmt($row['InA'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['OutA'] ?? null) }}</td>
                        <td>{{ ($row['Keterangan'] ?? '') !== '' ? $row['Keterangan'] : '-' }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" class="center">Total {{ $category['name'] ?? '-' }}</td>
                    <td class="number">{{ $fmt($category['total_in'] ?? null) }}</td>
                    <td class="number">{{ $fmt($category['total_out'] ?? null) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @empty
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="empty-state">Tidak ada data untuk tanggal ini.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @if ($summaryRows !== [])
        <div class="section-title">Rangkuman</div>
        <table class="report-table summary-table">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th style="width: 84px;">Jumlah</th>
                    <th style="width: 96px;">Total In</th>
                    <th style="width: 96px;">Total Out</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summaryRows as $index => $row)
                    <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td>{{ $row['Kategori'] ?? '-' }}</td>
                        <td class="number">{{ number_format((float) ($row['Jumlah'] ?? 0), 0, '.', ',') }}</td>
                        <td class="number">{{ $fmt($row['TotalIn'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['TotalOut'] ?? null) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>Total</td>
                    <td class="number">{{ number_format((float) ($grandTotals['row_count'] ?? 0), 0, '.', ',') }}</td>
                    <td class="number">{{ $fmt($grandTotals['total_in'] ?? null) }}</td>
                    <td class="number">{{ $fmt($grandTotals['total_out'] ?? null) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    </body>

</html>
