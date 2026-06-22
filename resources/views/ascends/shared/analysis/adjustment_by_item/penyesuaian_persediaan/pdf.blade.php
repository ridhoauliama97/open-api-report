<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        @page {
            margin: 14mm 10mm 14mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000;
        }

        .report-companyTitle {
            text-align: center;
            margin: 0 0 4px 0;
            font-size: 18px;
            font-weight: bold;
        }

        .report-title {
            text-align: center;
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
            border-spacing: 0;
            border: 1px solid #000;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .center {
            text-align: center;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            font-size: 11px;
        }

        .number {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .nowrap {
            white-space: nowrap;
        }

        .total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
        }
    </style>
</head>

<body>
    @php
        $rows = $reportData['rows'] ?? [];
        $printedAt = $reportData['printed_at'] ?? '';
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerTitle = trim((string) ($title ?? $reportData['title'] ?? $fallbackTitle ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>


    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 10%">Tanggal</th>
                <th style="width: 43%">Nama</th>
                <th style="width: 12%">Keterangan</th>
                <th style="width: 10%">Jumlah</th>
                <th style="width: 20%">Nilai yang Disesuaikan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center nowrap">{{ $row['Tanggal'] ?? '' }}</td>
                    <td>{{ $row['Nama'] ?? '' }}</td>
                    <td>{{ $row['Keterangan'] ?? '' }}</td>
                    <td class="number nowrap">{{ $row['Jumlah'] ?? '' }}</td>
                    <td class="number nowrap">{{ $row['Nilai yang Disesuaikan'] ?? '0.00' }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="6">Tidak ada data.</td>
                </tr>
            @endforelse
            @if (!empty($rows))
                @php
                    $totalQty = (float) ($reportData['totals']['quantity'] ?? 0);
                    $totalUoms = $reportData['totals']['uoms'] ?? [];
                    if (count($totalUoms) === 1) {
                        $totalQtyDisplay = number_format($totalQty, 0, '.', ',') . ' ' . array_key_first($totalUoms);
                    } elseif (count($totalUoms) > 1) {
                        $parts = [];
                        foreach ($totalUoms as $uom => $qty) {
                            $parts[] = number_format($qty, 0, '.', ',') . ' ' . $uom;
                        }
                        $totalQtyDisplay = implode(', ', $parts);
                    } else {
                        $totalQtyDisplay = number_format($totalQty, 0, '.', ',');
                    }
                @endphp
                <tr class="total-row">
                    <td colspan="4" class="center">Total</td>
                    <td class="number nowrap">{{ $totalQtyDisplay }}</td>
                    <td class="number nowrap">{{ number_format((float) ($reportData['totals']['adjusted_value'] ?? 0), 2, '.', ',') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @include('ascends.shared.partials.report-footer')
</body>

</html>