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
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .group-row td {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 2px;
            text-align: left;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
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

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            color: #9c111d;
            font-weight: bold;
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
        $groupedRows = $reportData['grouped_rows'] ?? [];
        $period = $reportData['period'] ?? [];
        $generatedAtText = $reportData['printed_at'] ?? '';
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $title = $reportData['headerTitle'] ?? 'Laporan Verifikasi Lembur';
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => $reportData['subtitle'] ?? '', 'title' => $title])

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 14%;">Jam Masuk</th>
                <th style="width: 14%;">Jam Keluar</th>
                <th style="width: 18%;">Shift</th>
                <th style="width: 7%;">Lembur Dibayar</th>
                <th style="width: 12%;">Jadwal Jam Kerja</th>
                <th style="width: 12%;">Jam Kerja Aktual</th>
                <th style="width: 7%;">+/- Menit</th>
                <th style="width: 13%;">Tipe Lembur</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groupedRows as $group)
                @php
                    $rows = $group['rows'] ?? [];
                    $no = 0;
                    $employeeName = $group['employee_name'] ?? '';
                @endphp
                <tr class="group-row">
                    <td colspan="9">{{ $employeeName }} - {{ $group['job_title'] ?? '' }}</td>
                </tr>
                @foreach ($rows as $row)
                    @php $no++; @endphp
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $no }}</td>
                        <td class="center nowrap">{{ (string) ($row['sign_in'] ?? '-') }}</td>
                        <td class="center nowrap">{{ (string) ($row['sign_out'] ?? '-') }}</td>
                        <td>{{ (string) ($row['shift_name'] ?? '-') }}</td>
                        <td class="center">{{ (int) ($row['actual_hours'] ?? 0) }}</td>
                        <td class="center">{{ (string) ($row['jam_kerja_formatted'] ?? '-') }}</td>
                        <td class="center">{{ (string) ($row['jam_kerja_aktual'] ?? '-') }}</td>
                        <td class="center">{{ (int) ($row['plus_minus_menit'] ?? 0) }}</td>
                        <td class="center">{{ (string) ($row['tipe_lembur'] ?? '-') }}</td>
                    </tr>
                @endforeach
            @empty
                <tr class="empty-row">
                    <td colspan="9">Tidak ada data verifikasi lembur.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('ascends.shared.partials.report-footer', [
        'generatedAt' => $generatedAtText,
        'generatedBy' => $generatedByName,
    ])
</body>

</html>
