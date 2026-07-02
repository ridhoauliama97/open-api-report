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
            text-align: right;
            font-weight: bold;
            border-top: 1px solid #000;
        }

        .summary-note td {
            font-weight: bold;
        }

        .grand-row td {
            text-align: right;
            font-weight: bold;
            border-top: 1px solid #000;
        }

        .department-total-row td {
            font-weight: bold;
        }

        .dept-separator td {
            border-top: 1px solid #000;
        }

        .dept-footer td {
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
        $departmentLegends = $reportData['department_legends'] ?? [];
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
        $generatedAtText = $reportData['printed_at'] ?? '';
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $title = $reportData['headerTitle'] ?? 'Laporan Lembur Bulanan Per Departemen (KK-KT)';
        $numberFormat = static fn (int $n): string => number_format($n, 0, ',', '.');
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => $periodLabel, 'title' => $title])

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 22%;">Nama</th>
                <th style="width: 5%;">L/P</th>
                <th style="width: 28%;">Jabatan</th>
                <th style="width: 9%;">Jam</th>
                <th style="width: 10%;">Total Hari</th>
                <th style="width: 14%;">Total Lemburan</th>
                <th style="width: 8%;">%</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groupedRows as $group)
                @php
                    $rows = $group['rows'] ?? [];
                    $no = 0;
                @endphp
                <tr class="group-row">
                    <td colspan="8">Departemen : {{ (string) ($group['department'] ?? '') }}</td>
                </tr>
                @foreach ($rows as $row)
                    @php $no++; @endphp
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $no }}</td>
                        <td>{{ (string) ($row['nama'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['sex_label'] ?? '') }}</td>
                        <td>{{ (string) ($row['jabatan'] ?? '') }}</td>
                        <td class="center">{{ (int) ($row['jam'] ?? 0) }}</td>
                        <td class="center">{{ (int) ($row['total_hari'] ?? 0) }}</td>
                        <td class="center">{{ $numberFormat((int) ($row['total_lemburan'] ?? 0)) }}</td>
                        <td class="center">{{ number_format((float) ($row['persen'] ?? 0), 1, ',', '.') }}%</td>
                    </tr>
                @endforeach
                <tr class="summary-row">
                    <td colspan="8">Sub Total = {{ (int) ($group['sub_total'] ?? 0) }}</td>
                </tr>
                <tr class="summary-note">
                    <td colspan="8">
                        Akumulasi L/P :
                        Laki-Laki = {{ (int) ($group['akumulasi_lp']['L'] ?? 0) }} ({{ (int) ($group['akumulasi_lp']['L_persen'] ?? 0) }}%)
                        &nbsp;&nbsp;&nbsp;&nbsp;
                        Perempuan = {{ (int) ($group['akumulasi_lp']['P'] ?? 0) }} ({{ (int) ($group['akumulasi_lp']['P_persen'] ?? 0) }}%)
                    </td>
                </tr>
                <tr class="summary-note">
                    <td colspan="8">
                        Akumulasi Lembur :
                        Min = {{ $numberFormat((int) ($group['akumulasi_lembur']['min'] ?? 0)) }}
                        &nbsp;&nbsp;
                        Max = {{ $numberFormat((int) ($group['akumulasi_lembur']['max'] ?? 0)) }}
                        &nbsp;&nbsp;
                        Avg = {{ $numberFormat((int) ($group['akumulasi_lembur']['avg'] ?? 0)) }}
                    </td>
                </tr>
                <tr class="summary-note">
                    <td colspan="8">
                        Akumulasi % Lembur :
                        Min = {{ number_format((float) ($group['akumulasi_persen']['min'] ?? 0), 1, ',', '.') }}%
                        &nbsp;&nbsp;
                        Max = {{ number_format((float) ($group['akumulasi_persen']['max'] ?? 0), 1, ',', '.') }}%
                        &nbsp;&nbsp;
                        Avg = {{ number_format((float) ($group['akumulasi_persen']['avg'] ?? 0), 1, ',', '.') }}%
                    </td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="8">Tidak ada data lembur yang dapat ditampilkan.</td>
                </tr>
            @endforelse

            @if (count($groupedRows) > 0)
                <tr class="grand-row">
                    <td colspan="8">Grand Total = {{ (int) ($grandSummary['grand_total_employees'] ?? 0) }}</td>
                </tr>
                <tr class="group-row">
                    <td colspan="8">Keterangan Departemen</td>
                </tr>
                @foreach ($departmentTotals as $deptTotal)
                    <tr class="department-total-row {{ $loop->first ? 'dept-separator' : '' }}">
                        <td colspan="4">{{ (string) ($deptTotal['department'] ?? '') }}</td>
                        <td class="center" colspan="4">{{ $numberFormat((int) ($deptTotal['total_lembur'] ?? 0)) }}</td>
                    </tr>
                @endforeach
                <tr class="department-total-row dept-separator dept-footer">
                    <td colspan="4">Total</td>
                    <td class="center" colspan="4">{{ $numberFormat((int) ($grandSummary['grand_total_lembur'] ?? 0)) }}</td>
                </tr>
                @foreach ($departmentLegends as $dept => $legend)
                    <tr class="summary-note">
                        <td colspan="8">{{ (string) $dept }} : {{ (string) $legend }}</td>
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
