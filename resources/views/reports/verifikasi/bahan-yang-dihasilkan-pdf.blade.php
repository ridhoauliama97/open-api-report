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
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000;
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

        .section-title {
            margin: 14px 0 6px 0;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: calc(100% - 2px);
            line-height: inherit;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px 2px;
        }

        td.center {
            text-align: center;
        }

        td.label {
            white-space: nowrap;
        }

        td.number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .totals-row td {
            font-weight: bold;
        }

        .headers-row th {
            font-weight: bold;
        }

        .summary-table {
            width: 72%;
            margin-top: 0px;
            margin-bottom: 12px;
        }

        .total-row td {
            font-weight: bold;
        }

        .empty-state {
            text-align: center;
            padding: 12px;
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background: #c9d1df;
        }
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
        $fmtInt = static fn($value): string => $value === null ? '' : number_format((float) $value, 0, '.', ',');
        $fmtDim = static fn($value): string => $value === null ? '-' : number_format((float) $value, 0, '.', ',');
    @endphp

    <h1 class="report-title">Laporan Rangkuman Bahan Yang Di Hasilkan</h1>
    <div class="report-subtitle">Per-Tanggal {{ $date }}</div>

    @forelse ($categories as $category)
        <div class="section-title">{{ $category['no'] ?? '' }}. {{ $category['name'] ?? '-' }}</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 36px;">No</th>
                    <th style="width: 120px;">Nama Mesin</th>
                    <th>Jenis</th>
                    <th style="width: 54px;">Tebal</th>
                    <th style="width: 54px;">Lebar</th>
                    <th style="width: 64px;">Panjang</th>
                    <th style="width: 58px;">Pcs</th>
                    <th style="width: 84px;">Kubik</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($category['rows'] ?? [] as $index => $row)
                    <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ ($row['NamaMesin'] ?? '') !== '' ? $row['NamaMesin'] : '-' }}</td>
                        <td>{{ ($row['Jenis'] ?? '') !== '' ? $row['Jenis'] : '-' }}</td>
                        <td class="center">{{ $fmtDim($row['Tebal'] ?? null) }}</td>
                        <td class="center">{{ $fmtDim($row['Lebar'] ?? null) }}</td>
                        <td class="center">{{ $fmtDim($row['Panjang'] ?? null) }}</td>
                        <td class="number">{{ $fmtInt($row['JmlhBatang'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['KubikIN'] ?? null) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="6" class="center">Total {{ $category['name'] ?? '-' }}</td>
                    <td class="number">{{ $fmtInt($category['total_pcs'] ?? null) }}</td>
                    <td class="number">{{ $fmt($category['total_volume'] ?? null) }}</td>
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
                    <th style="width: 90px;">Total Pcs</th>
                    <th style="width: 96px;">Total Kubik</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summaryRows as $index => $row)
                    <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td>{{ $row['Kategori'] ?? '-' }}</td>
                        <td class="number">{{ $fmtInt($row['Jumlah'] ?? null) }}</td>
                        <td class="number">{{ $fmtInt($row['TotalPcs'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['TotalVolume'] ?? null) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td class="center">Grand Total</td>
                    <td class="number">{{ $fmtInt($grandTotals['row_count'] ?? null) }}</td>
                    <td class="number">{{ $fmtInt($grandTotals['total_pcs'] ?? null) }}</td>
                    <td class="number">{{ $fmt($grandTotals['total_volume'] ?? null) }}</td>
                </tr>
            </tbody>
        </table>
    @endif
</body>

</html>
