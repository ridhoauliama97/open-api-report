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

        .product-table th { font-weight: bold; background-color: #eef2f8; }

        .report-table th { font-weight: bold; background-color: #eef2f8; }

        .summary-table th { font-weight: bold; background-color: #eef2f8; }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $buyers = is_array($data['buyers'] ?? null) ? $data['buyers'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $grandTotalM3 = (float) ($summary['grand_total_m3'] ?? 0.0);
        $sortedBuyers = $buyers;
        usort($sortedBuyers, static function (array $left, array $right): int {
            return ((float) ($right['summary_ratio'] ?? 0)) <=> ((float) ($left['summary_ratio'] ?? 0));
        });
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $fmtInt = static fn($value): string => $value === null ? '' : number_format((float) $value, 0, '.', ',');
        $fmtM3 = static fn($value): string => $value === null ? '' : number_format((float) $value, 4, '.', ',');
        $fmtPct = static fn($value): string => $value === null ? '' : number_format((float) $value, 2, '.', ',') . ' %';
    @endphp

    <h1 class="report-title">Laporan Rekap Penjualan Ekspor Per-Buyer dan Per-Produk</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    @forelse ($sortedBuyers as $buyer)
        @php
            $buyerProducts = is_array($buyer['products'] ?? null) ? $buyer['products'] : [];
            usort($buyerProducts, static function (array $left, array $right): int {
                return ((float) ($right['total_m3'] ?? 0)) <=> ((float) ($left['total_m3'] ?? 0));
            });
        @endphp
        <div class="section-title">{{ $loop->iteration }}. Buyer : {{ $buyer['name'] ?? '-' }}</div>
        @foreach ($buyerProducts as $product)
            <div class="product-title">Produk : {{ $product['name'] ?? '-' }}</div>
            <table class="report-table product-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th>Tebal</th>
                        <th>Lebar</th>
                        <th>Panjang</th>
                        <th>Pcs</th>
                        <th style="width: 15%">M3</th>
                        <th style="width: 15%;">Rasio (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($product['rows'] ?? [] as $index => $row)
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
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td class="center" colspan="5">Total </td>
                        <td class="number">{{ $fmtM3($product['total_m3'] ?? null) }}</td>
                        <td class="number">{{ $fmtPct($product['summary_ratio'] ?? null) }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    @empty
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="empty-state">Tidak ada data untuk periode ini.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @if ($buyers !== [])
        <div class="summary-title">Rangkuman Hasil :</div>
        <table class="report-table summary-table">
            <thead>
                <tr>
                    <th style="width: 8%;">No</th>
                    <th>Buyer</th>
                    <th>Jumlah Produk</th>
                    <th>Total M3</th>
                    <th>Rasio (%)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sortedBuyers as $index => $buyer)
                    <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $buyer['name'] ?? '-' }}</td>
                        <td class="center">{{ count($buyer['products'] ?? []) }}</td>
                        <td class="number">{{ $fmtM3($buyer['total_m3'] ?? null) }}</td>
                        <td class="number">{{ $fmtPct($buyer['summary_ratio'] ?? null) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" class="center">Grand Total</td>
                    <td class="number">{{ $fmtM3($grandTotalM3) }}</td>
                    <td class="number">{{ $fmtPct(100.0) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    </body>

</html>
