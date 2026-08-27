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
        .data-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            table-layout: fixed;
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
        .report-table, .report-table-summary {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            line-height: inherit;
        }
.report-table th, .report-table td, .report-table-summary th, .report-table-summary td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }

        .report-table th { font-weight: bold; background-color: #eef2f8; }

        .report-table-summary th { font-weight: bold; background-color: #eef2f8; }
    </style>
</head>

<body>
    @php
        $reportData = is_array($reportData ?? null) ? $reportData : [];
        $groups = is_array($reportData['groups'] ?? null) ? $reportData['groups'] : [];
        $summary = is_array($reportData['summary'] ?? null) ? $reportData['summary'] : [];
        $fmtPcs = static fn($v): string => is_numeric($v) && (int) round((float) $v) !== 0
            ? number_format((int) round((float) $v), 0, '.', ',')
            : '';
        $fmtM3 = static fn($v): string => is_numeric($v) && abs((float) $v) >= 0.0000001
            ? number_format((float) $v, 4, '.', ',')
            : '';
        $generatedDate = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y');
    @endphp

    <h1 class="report-title">Laporan Saldo Barang Jadi Hidup Per-Jenis Per-Produk</h1>
    <p class="report-subtitle">Per {{ $generatedDate }}</p>

    @forelse ($groups as $jenisIndex => $jenisGroup)
        <div class="section-title">{{ $jenisGroup['name'] ?? 'LAINNYA' }}</div>
        @foreach ($jenisGroup['products'] ?? [] as $productGroup)
            <div style="font-weight:bold; margin: 4px 0 2px 8px;">Produk : {{ $productGroup['name'] ?? '-' }}</div>
            <table class="report-table">
                <thead>
                    <tr class="headers-row">
                        <th style="width:5%;">No</th>
                        <th>Tebal</th>
                        <th>Lebar</th>
                        <th>Panjang</th>
                        <th style="width:15%;">Pcs</th>
                        <th style="width:15%;">M3</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($productGroup['rows'] ?? [] as $row)
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell">{{ $loop->iteration }}</td>
                            <td class="number data-cell">{{ $fmtPcs($row['Tebal'] ?? null) }}</td>
                            <td class="number data-cell">{{ $fmtPcs($row['Lebar'] ?? null) }}</td>
                            <td class="number data-cell">{{ $fmtPcs($row['Panjang'] ?? null) }}</td>
                            <td class="number data-cell">{{ $fmtPcs($row['Pcs'] ?? null) }}</td>
                            <td class="number data-cell">{{ $fmtM3($row['M3'] ?? null) }}</td>
                        </tr>
                    @endforeach
                    <tr class="totals-row">
                        <td colspan="4" class="blank">Subtotal {{ $productGroup['name'] ?? '-' }}</td>
                        <td class="number">{{ $fmtPcs($productGroup['total_pcs'] ?? null) }}</td>
                        <td class="number">{{ $fmtM3($productGroup['total_m3'] ?? null) }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
        <table class="report-table-summary">
            <tbody>
                <tr class="totals-row">
                    <td class="blank">Total (M3) Per-Jenis {{ $jenisGroup['name'] ?? 'LAINNYA' }}</td>
                    <td class="number" style="width: 29.75%"> {{ $fmtM3($jenisGroup['total_m3'] ?? null) }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="center">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @if (($summary['total_rows'] ?? 0) > 0)
        <div class="summary-block">
            <div class="section-title">Grand Total</div>
            <ul class="summary-list">
                <li>Total Jenis:
                    <strong> {{ number_format((int) ($summary['total_jenis'] ?? 0), 0, '.', ',') }} Jenis </strong>
                </li>
                <li>Total Produk:
                    <strong>{{ number_format((int) ($summary['total_produk'] ?? 0), 0, '.', ',') }} Produk </strong>
                </li>
                <li>Total Pcs:
                    <strong>{{ number_format((int) ($summary['total_pcs'] ?? 0), 0, '.', ',') }} Pcs </strong>
                </li>
                <li>Total m3 :
                    <strong>{{ number_format((float) ($summary['total_m3'] ?? 0), 4, '.', ',') }} m3
                    </strong>
                </li>
            </ul>
        </div>
    @endif

    </body>

</html>
