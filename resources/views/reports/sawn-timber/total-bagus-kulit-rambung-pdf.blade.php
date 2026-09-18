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
        .report-table th, .report-table td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }
        .report-table th {
            font-weight: bold;
            background-color: #eef2f8;
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
            ->locale('id')->isoFormat('DD-MMM-YY');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');

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
