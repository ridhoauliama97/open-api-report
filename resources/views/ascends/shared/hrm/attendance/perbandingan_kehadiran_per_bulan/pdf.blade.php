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
            color: #000;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: avoid;
            border-spacing: 0;
            border: 1px solid #000;
            margin-bottom: 14px;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 3px 4px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .section-row td {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            background: #fff;
            color: #990000;
            font-weight: bold;
            font-style: italic;
            text-align: center;
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
        $sections = $reportData['sections'] ?? [];
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $title = trim((string) ($reportData['title'] ?? 'Laporan Perbandingan Kehadiran Per Bulan'));
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => $periodLabel, 'title' => $title])

    @forelse ($sections as $section)
        <table class="data-table">
            <thead>
                <tr class="section-row">
                    <td colspan="6">{{ (string) ($section['title'] ?? '') }}</td>
                </tr>
                <tr>
                    <th style="width: 18%;">Bulan</th>
                    <th style="width: 14%;">Total Karyawan</th>
                    <th style="width: 18%;">Jumlah Ketidakhadiran</th>
                    <th style="width: 16%;">% Ketidakhadiran</th>
                    <th style="width: 17%;">Jumlah Terlambat</th>
                    <th style="width: 17%;">% Terlambat</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($section['rows'] ?? []) as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td>{{ (string) ($row['Bulan'] ?? '') }}</td>
                        <td class="right">{{ (string) ($row['Total Karyawan'] ?? '0') }}</td>
                        <td class="right">{{ (string) ($row['Jumlah Ketidakhadiran'] ?? '0') }}</td>
                        <td class="right">{{ (string) ($row['% Ketidakhadiran'] ?? '0%') }}</td>
                        <td class="right">{{ (string) ($row['Jumlah Terlambat'] ?? '0') }}</td>
                        <td class="right">{{ (string) ($row['% Terlambat'] ?? '0%') }}</td>
                    </tr>
                @empty
                    <tr class="empty-row">
                        <td colspan="6">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
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
</body>

</html>
