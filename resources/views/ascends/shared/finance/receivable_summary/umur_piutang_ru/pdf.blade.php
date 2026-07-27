<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
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
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            font-size: 10px;
            padding: 2px 2px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .center {
            text-align: center;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .number {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            color: #9c111d;
            font-size: 10px;
        }

        .grand-total td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
        $rows = $reportData['rows'] ?? [];
        $grandTotals = $reportData['grand_totals'] ?? [];
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerTitle = trim((string) ($title ?? $reportData['title'] ?? $fallbackTitle ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $bucketFields = [
            '00-04 days',
            '05-08 days',
            '09-12 days',
            '13-16 days',
            '17-20 days',
            '21-24 days',
            '25-28 days',
            'Over 28 days',
        ];

        function fmtAmount($value)
        {
            $v = (float) $value;
            if ($v < 0) {
                return '(' . number_format(abs($v), 0, '.', ',') . ')';
            }
            if ($v == 0.0) {
                return '-';
            }
            return number_format($v, 0, '.', ',');
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($rows) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th style="width: 15%">Nama Pelanggan</th>
                    <th style="width: 10%">0 - 4 Hari</th>
                    <th style="width: 10%">5 - 8 Hari</th>
                    <th style="width: 10%">9 - 12 Hari</th>
                    <th style="width: 10%">13 - 16 Hari</th>
                    <th style="width: 10%">17 - 20 Hari</th>
                    <th style="width: 10%">21 - 24 Hari</th>
                    <th style="width: 10%">25 - 28 Hari</th>
                    <th style="width: 10%">&gt; 28 Hari</th>
                    <th style="width: 10%">Akhir</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $idx => $row)
                    <tr class="{{ $idx % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        <td class="center">{{ $idx + 1 }}</td>
                        <td>{{ $row['customer_name'] }}</td>
                        @foreach ($bucketFields as $bucket)
                            <td class="number nowrap">
                                {{ fmtAmount($row['buckets'][$bucket] ?? 0) }}
                            </td>
                        @endforeach
                        <td class="number nowrap">{{ fmtAmount($row['total_akhir'] ?? 0) }}</td>
                    </tr>
                @endforeach

                <tr class="grand-total">
                    <td class="center" colspan="2">GRAND TOTAL</td>
                    @foreach ($bucketFields as $bucket)
                        <td class="number nowrap">
                            {{ fmtAmount($grandTotals[$bucket] ?? 0) }}
                        </td>
                    @endforeach
                    <td class="number nowrap">{{ fmtAmount($grandTotals['total_akhir'] ?? 0) }}
                    </td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="11">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
