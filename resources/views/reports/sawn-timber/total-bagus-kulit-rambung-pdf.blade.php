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
        }

        @page {
            sheet-size: A4;
            margin: 14mm 10mm 14mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
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

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table {
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px 6px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        .data-row td {
            border-top: 0;
            border-bottom: 0;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .empty-row td {
            background: #c9d1df;
            font-weight: bold;
            font-style: italic;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .dim {
            text-align: center;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .total-row td {
            font-weight: bold;
            font-size: 11px;
        }

        @include('reports.partials.pdf-footer-table-style');
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $date = \Carbon\Carbon::parse((string) ($reportDate ?? ($data['report_date'] ?? now())))
            ->locale('id')
            ->translatedFormat('d-M-y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $fmtInt = static fn($value): string => number_format((int) ($value ?? 0), 0, ',', '.');
        $fmtDim = static function ($value): string {
            if ($value === null || $value === '') {
                return '';
            }

            $number = (float) $value;

            return rtrim(rtrim(number_format($number, 2, ',', '.'), '0'), ',');
        };
    @endphp

    <h1 class="report-title">Laporan Total Bagus/Kulit Rambung</h1>
    <div class="report-subtitle">Per Tanggal : {{ $date }}</div>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 22%;">Jenis</th>
                <th style="width: 20%;">Kategori</th>
                <th style="width: 10%;">Tebal</th>
                <th style="width: 10%;">Lebar</th>
                <th style="width: 10%;">Panjang</th>
                <th style="width: 11%;">Bagus</th>
                <th style="width: 11%;">Kulit</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $row['Jenis'] ?? '' }}</td>
                    <td>{{ $row['Kategori'] ?? '' }}</td>
                    <td class="dim">{{ $fmtDim($row['Tebal'] ?? null) }}</td>
                    <td class="dim">{{ $fmtDim($row['Lebar'] ?? null) }}</td>
                    <td class="dim">{{ $fmtDim($row['Panjang'] ?? null) }}</td>
                    <td class="number">{{ $fmtInt($row['Bagus'] ?? 0) }}</td>
                    <td class="number">{{ $fmtInt($row['Kulit'] ?? 0) }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td class="center" colspan="8">Tidak ada data.</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td class="center" colspan="6">Total</td>
                <td class="number">{{ $fmtInt($summary['total_bagus'] ?? 0) }}</td>
                <td class="number">{{ $fmtInt($summary['total_kulit'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
