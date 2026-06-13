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
            margin: 12mm 8mm 12mm 8mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.15;
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
            margin-bottom: 10px;
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

        .italic {
            font-style: italic;
        }

        .date-section {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>
    @php
        $dateColumns = array_values($reportData['date_columns'] ?? []);
        $dateColumnChunks = array_chunk($dateColumns, 7);
        $rows = array_values($reportData['rows'] ?? []);
        $dateTotals = $reportData['date_totals'] ?? [];
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
    @endphp

    @include('ascends.shared.partials.report-header', [
        'fallbackTitle' => 'Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut',
        'subtitle' => $periodLabel,
    ])

    @forelse ($dateColumnChunks as $chunkIndex => $dateChunk)
        @php
            $isLastChunk = $loop->last;
            $totalColumns = 5 + (count($dateChunk) * 2) + ($isLastChunk ? 1 : 0);
            $datePairWidth = count($dateChunk) > 0 ? (int) floor(($isLastChunk ? 44 : 49) / count($dateChunk)) : 7;
        @endphp
        <div class="date-section">
            <table class="data-table">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 5%">NIK</th>
                        <th rowspan="2" style="width: 10%">Nama Karyawan</th>
                        <th rowspan="2" style="width: 10%">Tanggal Masuk</th>
                        <th rowspan="2" style="width: 10%">Masa Kerja</th>
                        <th rowspan="2" style="width: 15%">Jabatan</th>
                        @foreach ($dateChunk as $dateColumn)
                            <th colspan="2" style="width: {{ $datePairWidth }}%;">{{ (string) ($dateColumn['label'] ?? '') }}
                            </th>
                        @endforeach
                        @if ($isLastChunk)
                            <th rowspan="2" style="width: 5%;">HK</th>
                        @endif
                    </tr>
                    <tr>
                        @foreach ($dateChunk as $dateColumn)
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
                            @foreach ($dateChunk as $dateColumn)
                                @php
                                    $date = (string) ($dateColumn['date'] ?? '');
                                    $dateAttendance = $attendance[$date] ?? [];
                                @endphp
                                <td class="center nowrap">{{ (string) ($dateAttendance['in'] ?? '') }}</td>
                                <td class="center nowrap">{{ (string) ($dateAttendance['out'] ?? '') }}</td>
                            @endforeach
                            @if ($isLastChunk)
                                <td class="center nowrap">{{ (string) ($row['hk'] ?? '') }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="{{ $totalColumns }}">Tidak ada data.</td>
                        </tr>
                    @endforelse

                    @if (!empty($rows))
                        <tr class="total-row">
                            <td colspan="5" class="center">Total Seluruh Karyawan/Kru :
                                {{ (int) ($reportData['total_employees'] ?? count($rows)) }}
                            </td>
                            @foreach ($dateChunk as $dateColumn)
                                @php
                                    $date = (string) ($dateColumn['date'] ?? '');
                                @endphp
                                <td colspan="2" class="center">{{ (int) ($dateTotals[$date] ?? 0) }}</td>
                            @endforeach
                            @if ($isLastChunk)
                                <td></td>
                            @endif
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    @empty
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td>Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    <htmlpagefooter name="reportFooter">
        @include('reports.partials.pdf-footer-table', [
            'generatedByName' => $generatedByName,
            'generatedAtText' => $generatedAtText,
        ])
    </htmlpagefooter>

</html>
