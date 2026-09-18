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
        $reportSections =
            isset($sections) && is_iterable($sections)
                ? (is_array($sections)
                    ? $sections
                    : collect($sections)->values()->all())
                : [];
        $hasDateRange = trim((string) $startDate) !== '' && trim((string) $endDate) !== '';
        $fmtTon = static fn($value): string => number_format((float) $value, 4, '.', '');
    @endphp

    <h1 class="report-title" @if (! $hasDateRange) style="margin-bottom: 20px;" @endif>Laporan Penjualan Lokal</h1>
    @if ($hasDateRange)
        <p class="report-subtitle">
            Periode {{ \Carbon\Carbon::parse($startDate)->locale('id')->isoFormat('DD-MMM-YY') }} s/d
            {{ \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('DD-MMM-YY') }}
        </p>
    @endif

    @forelse ($reportSections as $section)
        @php
            $sectionRows = is_array($section['rows'] ?? null) ? $section['rows'] : [];
            usort($sectionRows, static function (array $left, array $right): int {
                return ((float) ($right['ton'] ?? 0)) <=> ((float) ($left['ton'] ?? 0));
            });
        @endphp
        <div style="margin-bottom: 6px; font-weight: bold;">{{ $section['proses'] ?? '' }}</div>

        <table class="report-table">
            <thead>
                <tr class="headers-row">
                    <th>No</th>
                    <th>Jenis</th>
                    <th>Nama Grade</th>
                    <th>Ton</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sectionRows as $row)
                    <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center data-cell" style="width: 8%;">{{ $loop->iteration }}</td>
                        <td class="data-cell" style="text-align: left; width: 44%;">{{ $row['jenis'] ?? '' }}</td>
                        <td class="center data-cell" style="width: 28%;">{{ $row['nama_grade'] ?? '' }}</td>
                        <td class="number-right data-cell" style="width: 20%; font-weight: bold;">
                            {{ $fmtTon($row['ton'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="center">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div
            style="width: 97%; margin-left: 18px; margin-top: 8px; text-align: right; font-weight: bold; margin-bottom: 2px;">
            Jumlah : {{ $fmtTon($section['subtotal_ton'] ?? 0) }}
        </div>
    @empty
        <div style="margin-left: 18px;">Tidak ada data.</div>
    @endforelse

    <div style="width: 97%; margin-left: 18px; margin-top: 2px; text-align: right; font-weight: bold;">
        Grand Total : {{ $fmtTon($grandTotalTon ?? 0) }}
    </div>

    </body>

</html>
