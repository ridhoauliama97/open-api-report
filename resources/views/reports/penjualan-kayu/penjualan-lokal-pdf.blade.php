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
        $reportSections =
            isset($sections) && is_iterable($sections)
                ? (is_array($sections)
                    ? $sections
                    : collect($sections)->values()->all())
                : [];
        $hasDateRange = trim((string) $startDate) !== '' && trim((string) $endDate) !== '';
        $fmtTon = static fn($value): string => number_format((float) $value, 4, '.', '');
    @endphp

    <h1 class="report-title">Laporan Penjualan Lokal</h1>
    @if ($hasDateRange)
        <p class="report-subtitle">
            Periode {{ \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y') }} s/d
            {{ \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y') }}
        </p>
    @else
        <p class="report-subtitle">&nbsp;</p>
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
