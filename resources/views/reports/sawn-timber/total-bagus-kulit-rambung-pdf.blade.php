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
            width: 100%;
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 1px 2px;
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

    </body>

</html>
