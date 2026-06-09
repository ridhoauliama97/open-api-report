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
            font-size: 9px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .data-table td {
            font-size: 9px;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .summary-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 6px;
            margin-bottom: 10px;
        }

        .summary-grid td {
            width: 33.333%;
            padding: 2px 8px 2px 0;
            vertical-align: top;
        }

        .summary-title {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .summary-line {
            margin-bottom: 1px;
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
        $summaryText = static function (array $summary, string $key): string {
            $label = ucfirst($key);

            return $label . ' = ' . (int) ($summary[$key] ?? 0)
                . ' (' . (string) ($summary[$key . '_percent'] ?? '0.0%') . ')';
        };
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => 'Per ' . $perDate])

    @foreach ($yearlyRows as $yearData)
        <div class="year-title">Tahun : {{ (int) ($yearData['year'] ?? 0) }}</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 13%;">Bulan</th>
                    <th rowspan="2" style="width: 10%;">Total<br>Karyawan</th>
                    <th colspan="2" style="width: 16%;">Karyawan Masuk</th>
                    <th colspan="2" style="width: 16%;">Karyawan Keluar</th>
                    <th rowspan="2" style="width: 9%;">%<br>Karyawan</th>
                    <th rowspan="2" style="width: 8%;">MPP</th>
                    <th colspan="2" style="width: 16%;">GAP</th>
                    <th rowspan="2" style="width: 12%;">Remark</th>
                </tr>
                <tr>
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
                        <td class="center">{{ (int) ($row['Total Karyawan'] ?? 0) }}</td>
                        <td class="center">{{ (int) ($row['Karyawan Masuk'] ?? 0) }}</td>
                        <td class="center nowrap">{{ (string) ($row['% Masuk'] ?? '0.0%') }}</td>
                        <td class="center">{{ (int) ($row['Karyawan Keluar'] ?? 0) }}</td>
                        <td class="center nowrap">{{ (string) ($row['% Keluar'] ?? '0.0%') }}</td>
                        <td class="center nowrap">{{ (string) ($row['% Karyawan'] ?? '0.0%') }}</td>
                        <td class="center">{{ (int) ($row['MPP'] ?? 0) }}</td>
                        <td class="center">{{ (int) ($row['GAP'] ?? 0) }}</td>
                        <td class="center nowrap">{{ (string) ($row['% GAP'] ?? '0.0%') }}</td>
                        <td>{{ (string) ($row['Remark'] ?? '') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @php $summary = $yearData['summary'] ?? []; @endphp
        <table class="summary-grid">
            <tr>
                <td>
                    <div class="summary-title">Akumulasi Karyawan Masuk Per Bulan :</div>
                    <div class="summary-line">{{ $summaryText($summary['joined'] ?? [], 'min') }}</div>
                    <div class="summary-line">{{ $summaryText($summary['joined'] ?? [], 'max') }}</div>
                    <div class="summary-line">{{ $summaryText($summary['joined'] ?? [], 'avg') }}</div>
                </td>
                <td>
                    <div class="summary-title">Akumulasi Karyawan Keluar Per Bulan :</div>
                    <div class="summary-line">{{ $summaryText($summary['terminated'] ?? [], 'min') }}</div>
                    <div class="summary-line">{{ $summaryText($summary['terminated'] ?? [], 'max') }}</div>
                    <div class="summary-line">{{ $summaryText($summary['terminated'] ?? [], 'avg') }}</div>
                </td>
                <td>
                    <div class="summary-title">Akumulasi Total Karyawan Akhir Per Bulan :</div>
                    <div class="summary-line">{{ $summaryText($summary['total'] ?? [], 'min') }}</div>
                    <div class="summary-line">{{ $summaryText($summary['total'] ?? [], 'max') }}</div>
                    <div class="summary-line">{{ $summaryText($summary['total'] ?? [], 'avg') }}</div>
                </td>
            </tr>
        </table>
    @endforeach

    @include('reports.partials.pdf-footer-table')
</body>

</html>