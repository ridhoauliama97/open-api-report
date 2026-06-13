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
        }

        .group-row td {
            font-weight: bold;
            font-size: 11px;
            font-style: italic;
            padding: 5px 6px;
            color: #9c111d;

            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .group-info {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .group-info td {
            border: 0;
            padding: 0 4px;

            vertical-align: top;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .summary-block-row td {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;

            font-weight: bold;
            padding: 4px;
        }

        .summary-block {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .summary-block td {
            border: 0;
            font-weight: bold;
            padding: 2px 4px;
            vertical-align: middle;
        }

        .summary-gap td {
            height: 4px;
            padding: 0;
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

        .nowrap {
            white-space: nowrap;
        }

        .right {
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $employees = array_values($reportData['employees'] ?? []);
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
    @endphp

    @include('ascends.shared.partials.report-header', [
        'fallbackTitle' => 'Laporan Absensi Individu',
        'subtitle' => $periodLabel,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;">Hari</th>
                <th style="width: 30%;">Absen Masuk</th>
                <th style="width: 30%;">Absen Keluar</th>
                <th style="width: 28%;">Waktu Bekerja</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($employees as $employeeReport)
                @php
                    $employee = $employeeReport['employee'] ?? [];
                    $rows = array_values($employeeReport['rows'] ?? []);
                    $summary = $employeeReport['summary'] ?? [];
                @endphp

                <tr class="group-row">
                    <td colspan="4">
                        <table class="group-info">
                            <tr>
                                <td style="width: 50%;">Nama : {{ (string) ($employee['name'] ?? '') }}</td>
                                <td style="width: 50%;">Jabatan : {{ (string) ($employee['job_title'] ?? '') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                @forelse ($rows as $row)
                    <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td>{{ (string) ($row['Hari'] ?? '') }}</td>
                        <td>{{ (string) ($row['Absen Masuk'] ?? '') }}</td>
                        <td>{{ (string) ($row['Absen Keluar'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Waktu Bekerja'] ?? '') }}</td>
                    </tr>
                @empty
                    <tr class="empty-row">
                        <td colspan="4" class="center">Tidak ada data.</td>
                    </tr>
                @endforelse

                <tr class="summary-block-row">
                    <td colspan="4">
                        <table class="summary-block">
                            <tr>
                                <td style="width: 12%;"></td>
                                <td style="width: 30%;"></td>
                                <td style="width: 30%;"></td>
                                <td style="width: 28%;" class="center">{{ (string) ($summary['total'] ?? '0 Jam 0 Menit') }}
                                </td>
                            </tr>
                            <tr class="summary-gap">
                                <td colspan="4"></td>
                            </tr>
                            <tr>
                                <td style="white-space: nowrap;">Akumulasi Min</td>
                                <td class="center">=</td>
                                <td>{{ (string) ($summary['min'] ?? '') }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td style="white-space: nowrap;">Akumulasi Avg</td>
                                <td class="center">=</td>
                                <td>{{ (string) ($summary['avg'] ?? '') }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td style="white-space: nowrap;">Akumulasi Max</td>
                                <td class="center">=</td>
                                <td>{{ (string) ($summary['max'] ?? '') }}</td>
                                <td></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="4">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <htmlpagefooter name="reportFooter">
        @include('reports.partials.pdf-footer-table', [
            'generatedByName' => $generatedByName,
            'generatedAtText' => $generatedAtText,
        ])
    </htmlpagefooter>
</body>

</html>
