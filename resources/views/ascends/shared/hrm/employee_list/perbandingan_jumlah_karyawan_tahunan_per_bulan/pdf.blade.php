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

        .year-title {
            margin: 8px 0 3px 0;
            font-size: 10px;
            font-weight: bold;
        }

        .data-table,
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border-spacing: 0;
            border: 1px solid #000;
        }

        .data-table th,
        .data-table td,
        .summary-table th,
        .summary-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th,
        .summary-table th {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .data-table td {
            font-size: 10px;
        }

        .data-table tr.sub-header th {
            border-top: none;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .summary-section {
            width: 100%;
            margin-top: 6px;
            margin-bottom: 10px;
        }

        .summary-section .summary-line {
            margin-bottom: 1px;
        }

        .summary-section .summary-title {
            font-weight: bold;
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
    </style>
</head>

<body>
    @php
        $yearlyRows = $reportData['yearly_rows'] ?? [];
        $perDate = \Carbon\Carbon::parse($reportData['per_date'] ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $summaryLine = static function (array $s): string {
            return 'Min = ' . (int) ($s['min'] ?? 0)
                . ' (' . (string) ($s['min_percent'] ?? '0.0%') . ')'
                . '   Max = ' . (int) ($s['max'] ?? 0)
                . ' (' . (string) ($s['max_percent'] ?? '0.0%') . ')'
                . '   Avg = ' . (int) ($s['avg'] ?? 0)
                . ' (' . (string) ($s['avg_percent'] ?? '0.0%') . ')';
        };
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => 'Per ' . $perDate])

    @foreach ($yearlyRows as $yearData)
        <div class="year-title">Tahun : {{ (int) ($yearData['year'] ?? 0) }}</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 12%;">Bulan</th>
                    <th rowspan="2" style="width: 8%;">MPP<br>Karyawan</th>
                    <th colspan="2" style="width: 14%;">Karyawan Masuk</th>
                    <th colspan="2" style="width: 14%;">Karyawan Keluar</th>
                    <th colspan="2" style="width: 14%;">Total Karyawan</th>
                    <th colspan="2" style="width: 14%;">GAP</th>
                    <th rowspan="2" style="width: 24%;">Remark</th>
                </tr>
                <tr class="sub-header">
                    <th>Jumlah</th>
                    <th>%</th>
                    <th>Jumlah</th>
                    <th>%</th>
                    <th>Jumlah</th>
                    <th>%</th>
                    <th>Jumlah</th>
                    <th>%</th>
                </tr>
            </thead>
            <tbody>
                @foreach (($yearData['rows'] ?? []) as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td>{{ (string) ($row['Bulan'] ?? '') }}</td>
                        <td class="center">{{ (int) ($row['MPP'] ?? 0) }}</td>
                        <td class="center">{{ (int) ($row['Karyawan Masuk'] ?? 0) }}</td>
                        <td class="center nowrap">{{ (string) ($row['% Masuk'] ?? '0.0%') }}</td>
                        <td class="center">{{ (int) ($row['Karyawan Keluar'] ?? 0) }}</td>
                        <td class="center nowrap">{{ (string) ($row['% Keluar'] ?? '0.0%') }}</td>
                        <td class="center">{{ (int) ($row['Total Karyawan'] ?? 0) }}</td>
                        <td class="center nowrap">{{ (string) ($row['% Total'] ?? '0.0%') }}</td>
                        <td class="center">{{ (int) ($row['GAP'] ?? 0) }}</td>
                        <td class="center nowrap">{{ (string) ($row['% GAP'] ?? '0.0%') }}</td>
                        <td>{{ (string) ($row['Remark'] ?? '') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @php
            $summary = $yearData['summary'] ?? [];
            $summaryLabels = [
                'Akumulasi Karyawan Masuk Per Bulan' => $summary['joined'] ?? [],
                'Akumulasi Karyawan Keluar Per Bulan' => $summary['terminated'] ?? [],
                'Akumulasi Total Karyawan Akhir Per Bulan' => $summary['total'] ?? [],
            ];
        @endphp
        <div class="summary-section">
            @foreach ($summaryLabels as $label => $data)
                <div class="summary-line">
                    <span class="summary-title">{{ $label }} :</span>
                    <span>{{ $summaryLine($data) }}</span>
                </div>
            @endforeach
        </div>
    @endforeach

    @include('reports.partials.pdf-footer-table')
</body>

</html>