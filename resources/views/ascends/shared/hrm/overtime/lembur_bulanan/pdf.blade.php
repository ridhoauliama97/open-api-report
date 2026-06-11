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
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .group-row td {
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            font-style: italic;
            padding: 5px 6px;
            color: #9c111d;
            background: #fff;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .summary-row td {
            text-align:right;
            font-weight: bold;
            border-top: 1px solid #000;
        }

        .summary-note td,
        .grand-row td,
        .department-total-title td,
        .department-total-row td {
            background: #fff;
            font-weight: bold;
        }

        .grand-row td,
        .department-total-row:last-child td {
            border-bottom: 1px solid #000;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
        $groupedRows = $reportData['grouped_rows'] ?? [];
        $grandSummary = $reportData['grand_summary'] ?? [];
        $departmentTotals = $grandSummary['department_totals'] ?? [];
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $title = trim((string) ($reportData['title'] ?? 'Laporan Lembur Bulanan'));
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => $periodLabel, 'title' => $title])

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Nama</th>
                <th style="width: 6%;">L/P</th>
                <th style="width: 31%;">Jabatan</th>
                <th style="width: 9%;">Jam</th>
                <th style="width: 11%;">Total Hari</th>
                <th style="width: 12%;">Total Lemburan</th>
                <th style="width: 6%;">%</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groupedRows as $group)
                @php
                    $rows = $group['rows'] ?? [];
                    $summary = $group['summary'] ?? [];
                    $departmentCode = trim((string) ($group['department_code'] ?? ''));
                    $departmentTitle = trim((string) ($group['department'] ?? '') . ($departmentCode !== '' ? ' ' . $departmentCode : ''));
                @endphp
                <tr class="group-row">
                    <td colspan="7">Departemen : {{ $departmentTitle }}</td>
                </tr>
                @foreach ($rows as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['L/P'] ?? '') }}</td>
                        <td>{{ (string) ($row['Jabatan'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Jam'] ?? '') }}</td>
                        <td class="center">{{ (int) ($row['Total Hari'] ?? 0) }}</td>
                        <td class="center">{{ (string) ($row['Total Lemburan'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['%'] ?? '') }}</td>
                    </tr>
                @endforeach
                <tr class="summary-row">
                    <td colspan="7">Sub Total = {{ (int) ($summary['subtotal'] ?? 0) }}</td>
                </tr>
                <tr class="summary-note">
                    {{-- <td colspan="7">Departemen : {{ (string) ($group['department'] ?? '') }}</td> --}}
                </tr>
                <tr class="summary-note">
                    <td colspan="7">
                        Akumulasi L/P :
                        Laki-Laki = {{ (int) ($summary['male_count'] ?? 0) }} ({{ (int) ($summary['male_percent'] ?? 0) }}%)
                        &nbsp;&nbsp;&nbsp;&nbsp;
                        Perempuan = {{ (int) ($summary['female_count'] ?? 0) }} ({{ (int) ($summary['female_percent'] ?? 0) }}%)
                    </td>
                </tr>
                <tr class="summary-note">
                    <td colspan="7">
                        Akumulasi Lembur :
                        Min = {{ (string) ($summary['overtime_min_text'] ?? '0') }}
                        &nbsp;&nbsp;
                        Max = {{ (string) ($summary['overtime_max_text'] ?? '0') }}
                        &nbsp;&nbsp;
                        Avg = {{ (string) ($summary['overtime_avg_text'] ?? '0') }}
                    </td>
                </tr>
                <tr class="summary-note">
                    <td colspan="7">
                        Akumulasi % Lembur:
                        Min = {{ (string) ($summary['percentage_min_text'] ?? '0.0%') }}
                        &nbsp;&nbsp;
                        Max = {{ (string) ($summary['percentage_max_text'] ?? '0.0%') }}
                        &nbsp;&nbsp;
                        Avg = {{ (string) ($summary['percentage_avg_text'] ?? '0.0%') }}
                    </td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="7">Tidak ada data lembur yang dapat ditampilkan.</td>
                </tr>
            @endforelse

            @if (!empty($groupedRows))
                <tr class="grand-row">
                    <td colspan="7">Grand Total = {{ (int) ($grandSummary['subtotal'] ?? 0) }}</td>
                </tr>
                <tr class="group-row">
                    <td colspan="7" class="center">Summary</td>
                </tr>
                @foreach ($departmentTotals as $department)
                    <tr class="department-total-row">
                        <td colspan="7">
                            <span style="display: inline-block; width: 45%;">{{ (string) ($department['department'] ?? '') }}</span>
                            <span style="display: inline-block; width: 12%; text-align: center;">{{ (string) ($department['total_lembur'] ?? '0') }}</span>
                            <span style="display: inline-block; width: 12%; text-align: center;">{{ (string) ($department['percentage'] ?? '0.0%') }}</span>
                        </td>
                    </tr>
                @endforeach
            @endif
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
