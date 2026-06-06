<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
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

        @page {
            margin: 10mm 6mm 12mm 6mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 9px;
            line-height: 1.15;
            color: #000;
        }

        .report-title {
            text-align: center;
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 12px 0;
            font-size: 10px;
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
            padding: 2px 2px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 8px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .total-row td {
            background: #fff;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            font-size: 11px;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        htmlpagefooter table.footer-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            color: #000;
        }

        htmlpagefooter table.footer-table td {
            border: 0;
            padding: 0;
            vertical-align: bottom;
        }

        .footer-print {
            text-align: left;
        }

        .footer-page {
            text-align: right;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    @php
        $dateColumns = array_values($reportData['date_columns'] ?? []);
        $rows = array_values($reportData['rows'] ?? []);
        $dateTotals = $reportData['date_totals'] ?? [];
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $totalColumns = 6 + (count($dateColumns) * 2);
    @endphp

    <h1 class="report-title">{{ $reportData['title'] ?? 'Laporan Kehadiran Kru Stick' }}</h1>
    <p class="report-subtitle">{{ $periodLabel }}</p>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 2.8%;">Karyawan</th>
                <th rowspan="2" style="width: 5.6%;">Nama</th>
                <th rowspan="2" style="width: 3.6%;">Tanggal Masuk</th>
                <th rowspan="2" style="width: 3.8%;">Masa Kerja<br>Tahun Bulan Hari</th>
                <th rowspan="2" style="width: 4.8%;">Jabatan</th>
                @foreach ($dateColumns as $dateColumn)
                    <th colspan="2">{{ (string) ($dateColumn['label'] ?? '') }}</th>
                @endforeach
                <th rowspan="2" style="width: 1.6%;">HK</th>
            </tr>
            <tr>
                @foreach ($dateColumns as $dateColumn)
                    <th>Masuk</th>
                    <th>Keluar</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php
                    $employee = $row['employee'] ?? [];
                    $attendance = $row['attendance'] ?? [];
                @endphp
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center nowrap">{{ (string) ($employee['employee_code'] ?? '') }}</td>
                    <td>{{ (string) ($employee['name'] ?? '') }}</td>
                    <td class="center nowrap">{{ (string) ($employee['join_date'] ?? '') }}</td>
                    <td class="center nowrap">{{ (string) ($employee['year_of_service'] ?? '') }}</td>
                    <td>{{ (string) ($employee['job_title'] ?? '') }}</td>
                    @foreach ($dateColumns as $dateColumn)
                        @php
                            $date = (string) ($dateColumn['date'] ?? '');
                            $dateAttendance = $attendance[$date] ?? [];
                        @endphp
                        <td class="center nowrap">{{ (string) ($dateAttendance['in'] ?? '') }}</td>
                        <td class="center nowrap">{{ (string) ($dateAttendance['out'] ?? '') }}</td>
                    @endforeach
                    <td class="center nowrap">{{ (string) ($row['hk'] ?? '') }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="{{ $totalColumns }}">Tidak ada data.</td>
                </tr>
            @endforelse

            @if (!empty($rows))
                <tr class="total-row">
                    <td colspan="5" class="center">Jumlah orang {{ (int) ($reportData['total_employees'] ?? count($rows)) }}</td>
                    @foreach ($dateColumns as $dateColumn)
                        @php
                            $date = (string) ($dateColumn['date'] ?? '');
                        @endphp
                        <td colspan="2" class="center">{{ (int) ($dateTotals[$date] ?? 0) }}</td>
                    @endforeach
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
