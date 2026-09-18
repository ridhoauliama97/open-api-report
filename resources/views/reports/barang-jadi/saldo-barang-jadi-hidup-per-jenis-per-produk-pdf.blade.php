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
        .center {
            text-align: center;
        }
        .report-table th, .report-table td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }
        .report-table th {
            font-weight: bold;
        }
        .report-table-summary {
            border-collapse: collapse;
            width: calc(100% - 2px);
            line-height: inherit;
            margin-top: 4px;
            border-collapse: collapse;
            width: calc(100% - 2px);
            line-height: inherit;
            margin-top: 4px;
            width: calc(100% - 2px);
            border-collapse: collapse;
            border: 1px solid #000;
        }
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
        $generatedDate = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY');
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

    {{-- @if (($summary['total_rows'] ?? 0) > 0)
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
    @endif --}}

</body>

</html>
