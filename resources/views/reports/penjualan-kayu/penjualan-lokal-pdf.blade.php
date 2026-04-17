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
            margin: 20mm 10mm 20mm 10mm;
            footer: html_reportFooter;
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

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
            table-layout: fixed;
        }

        .report-table {
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
            word-break: break-word;
            text-align: center;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        td.center {
            text-align: center;
        }

        td.number-right {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border-top: 0;
            border-bottom: 1px solid #000;
        }

        .report-table {
            width: 100%;
            margin-bottom: 0;
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
        }

        .table-end-line td {
            border-top: 1px solid #000 !important;
            border-right: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: #fff !important;
        }

        @include('reports.partials.pdf-footer-table-style')
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

    @include('reports.partials.pdf-footer-table')
</body>

</html>
