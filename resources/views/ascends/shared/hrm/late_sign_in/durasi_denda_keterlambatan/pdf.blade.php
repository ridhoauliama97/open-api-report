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
            text-align: center;
        }

        .group-row td {
            font-weight: bold;
            text-align: center;
            font-size: 11px;
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

        .employee-summary td {
            font-weight: bold;
        }

        .employee-summary-last td {
            border-bottom: 1px solid #000;
        }

        .summary-table {
            margin-top: 12px;
        }
    </style>
</head>

<body>
    @php
        $groupedRows = $reportData['grouped_rows'] ?? [];
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $type = trim((string) ($reportData['type'] ?? ''));
        $title = trim((string) ($reportData['title'] ?? 'Laporan Durasi & Denda Keterlambatan'));
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => $periodLabel, 'title' => $title])

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Nama</th>
                <th style="width: 31%;">Jabatan</th>
                <th style="width: 8%;">Level</th>
                <th style="width: 22%;">Absen Masuk</th>
                <th style="width: 12%;">Telat (Menit)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groupedRows as $group)
                @php
                    $rows = $group['rows'] ?? [];
                    $summary = $group['summary'] ?? [];
                @endphp
                <tr class="group-row">
                    <td colspan="5">Departemen : {{ (string) ($group['department'] ?? '') }}</td>
                </tr>
                @foreach ($rows as $row)
                    @php
                        $details = $row['details'] ?? [];
                        $detailCount = max(1, count($details));
                    @endphp
                    @forelse ($details as $detail)
                        <tr class="{{ $loop->parent->odd ? 'row-odd' : 'row-even' }}">
                            @if ($loop->first)
                                <td rowspan="{{ $detailCount }}">{{ (string) ($row['Nama'] ?? '') }}</td>
                                <td rowspan="{{ $detailCount }}">{{ (string) ($row['Jabatan'] ?? '') }}</td>
                                <td rowspan="{{ $detailCount }}" class="center">{{ (string) ($row['Level'] ?? '') }}</td>
                            @endif
                            <td class="center">{{ (string) ($detail['sign_in'] ?? '') }}</td>
                            <td class="center">{{ (int) ($detail['late_minutes'] ?? 0) }}</td>
                        </tr>
                    @empty
                        <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                            <td>{{ (string) ($row['Jabatan'] ?? '') }}</td>
                            <td class="center">{{ (string) ($row['Level'] ?? '') }}</td>
                            <td></td>
                            <td class="center">{{ (int) ($row['Total Menit'] ?? 0) }}</td>
                        </tr>
                    @endforelse
                    <tr class="employee-summary">
                        <td colspan="3"></td>
                        <td class="right">Durasi Terlambat =</td>
                        <td class="center">{{ (string) ($row['Durasi'] ?? '') }}</td>
                    </tr>
                    <tr class="employee-summary employee-summary-last">
                        <td colspan="3"></td>
                        <td class="right">Denda Terlambat =</td>
                        <td class="right">{{ (string) ($row['Denda'] ?? '') }}</td>
                    </tr>
                @endforeach
            @empty
                <tr class="empty-row">
                    <td colspan="5">Tidak ada data keterlambatan yang dapat ditampilkan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if (!empty($groupedRows))
        @php
            $grandSummary = $reportData['grand_summary'] ?? [];
            $grandTotalMinutes = (int) ($grandSummary['total_minutes'] ?? 0);
        @endphp
        <table class="data-table summary-table">
            <thead>
                <tr>
                    <th style="width: 42%;">Departemen</th>
                    <th style="width: 34%;">Durasi</th>
                    <th style="width: 24%;">Denda (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groupedRows as $group)
                    @php
                        $summary = $group['summary'] ?? [];
                    @endphp
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td>{{ (string) ($group['department'] ?? '') }}</td>
                        <td class="center">
                            {{ (string) ($summary['duration'] ?? '') }}
                            ({{ (int) ($summary['percent'] ?? 0) }}%)
                        </td>
                        <td class="right">{{ number_format((int) ($summary['total_nominal'] ?? 0), 0, '.', ',') }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td class="center">Total</td>
                    <td class="center">
                        {{ intdiv($grandTotalMinutes, 60) }} Jam {{ $grandTotalMinutes % 60 }} Menit (100%)
                    </td>
                    <td class="right">{{ number_format((int) ($grandSummary['total_nominal'] ?? 0), 0, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <htmlpagefooter name="reportFooter">
        @include('reports.partials.pdf-footer-table', [
            'generatedByName' => $generatedByName,
            'generatedAtText' => $generatedAtText,
        ])
    </htmlpagefooter>
</body>

</html>
