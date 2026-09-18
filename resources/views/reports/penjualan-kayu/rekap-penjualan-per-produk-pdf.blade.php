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
        .product-table, .report-table, .summary-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            line-height: inherit;
        }
        .product-table th, .product-table td, .report-table th, .report-table td, .summary-table th, .summary-table td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }
        .product-table th {
            font-weight: bold;
            background-color: #eef2f8;
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
        $products = is_array($data['products'] ?? null) ? $data['products'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $grandTotalM3 = (float) ($summary['grand_total_m3'] ?? 0.0);
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->isoFormat('DD-MMM-YY');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('DD-MMM-YY');

        $fmtInt = static fn($value): string => $value === null ? '' : number_format((float) $value, 0, '.', ',');
        $fmtM3 = static fn($value): string => $value === null ? '' : number_format((float) $value, 4, '.', ',');
        $fmtPct = static fn($value): string => $value === null ? '' : number_format((float) $value, 2, '.', ',') . ' %';
    @endphp

    <h1 class="report-title">Laporan Rekap Penjualan Per-Produk</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    @forelse ($products as $product)
        <div class="section-title">{{ $product['roman'] ?? '' }} Produk : {{ $product['name'] ?? '-' }}</div>
        <table class="report-table product-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tebal</th>
                    <th>Lebar</th>
                    <th>Panjang</th>
                    <th>Pcs</th>
                    <th>m3</th>
                    <th>Rasio (%)</th>
                    <th style="width: 10%;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($product['rows'] as $index => $row)
                    <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $row['No'] ?? $index + 1 }}</td>
                        <td class="center">{{ $fmtInt($row['Tebal'] ?? null) }}</td>
                        <td class="center">{{ $fmtInt($row['Lebar'] ?? null) }}</td>
                        <td class="center">{{ $fmtInt($row['Panjang'] ?? null) }}</td>
                        <td class="number">{{ $fmtInt($row['JmlhBatang'] ?? null) }}</td>
                        <td class="number">{{ $fmtM3($row['M3'] ?? null) }}</td>
                        <td class="number">
                            {{ $row['Ratio'] !== null ? number_format((float) $row['Ratio'], 2, '.', ',') : '' }}
                        </td>
                        <td class="number">
                            {{ !empty($row['DisplayCumulative']) ? $fmtPct($row['CumulativeRatio'] ?? null) : '' }}
                        </td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td class="center" colspan="5">Total</td>
                    <td class="number">{{ $fmtM3($product['total_m3'] ?? null) }}</td>
                    <td class="number">{{ $fmtPct($product['summary_ratio'] ?? null) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @empty
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="empty-state">Tidak ada data untuk periode ini.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @if ($products !== [])

        <div class="summary-title">Rangkuman</div>
        <table class="summary-table">
            <tbody>
                @foreach ($products as $product)
                    <tr>
                        <td>{{ $product['name'] ?? '-' }}</td>
                        <td class="number" style="width: 90px;">{{ $fmtM3($product['total_m3'] ?? null) }}</td>
                        <td class="number" style="width: 70px;">{{ $fmtPct($product['summary_ratio'] ?? null) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td style="padding-top: 6px;">Total (M3) Per-Produk :</td>
                    <td class="number" style="padding-top: 6px;">{{ $fmtM3($grandTotalM3) }}</td>
                    <td class="number" style="padding-top: 6px;">{{ $fmtPct(100.0) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    </body>

</html>
