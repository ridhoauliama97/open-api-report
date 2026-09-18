<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    @include('reports.partials.pdf-reference-style')
    <style>
        .data-table th, .data-table td {
            border: 1px solid #000;
        }
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
        .report-table th {
            font-weight: bold;
            background-color: #eef2f8;
        }
        .summary-table th {
            font-weight: bold;
            background-color: #eef2f8;
        }
    </style>
    
    
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $categories = is_array($data['categories'] ?? null) ? $data['categories'] : [];
        $summaryRows = is_array($data['summary_rows'] ?? null) ? $data['summary_rows'] : [];
        $grandTotals = is_array($data['grand_totals'] ?? null) ? $data['grand_totals'] : [];
        $date = \Carbon\Carbon::parse($reportDate)->locale('id')->isoFormat('DD-MMM-YY');
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
