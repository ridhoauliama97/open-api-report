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
            border: 1px solid #000;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        .data-table th {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .data-table td {
            font-size: 10px;
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
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($rows) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20%">Nama Pelanggan</th>
                    <th style="width: 11%">0 - 4 Hari</th>
                    <th style="width: 11%">5 - 8 Hari</th>
                    <th style="width: 11%">9 - 12 Hari</th>
                    <th style="width: 11%">13 - 16 Hari</th>
                    <th style="width: 11%">17 - 20 Hari</th>
                    <th style="width: 11%">21 - 24 Hari</th>
                    <th style="width: 11%">25 - 28 Hari</th>
                    <th style="width: 11%">&gt; 28 Hari</th>
                    <th style="width: 12%">Akhir</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $idx => $row)
                    <tr class="{{ $idx % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        <td>{{ $row['customer_name'] }}</td>
                        @foreach ($bucketFields as $bucket)
                            <td class="number nowrap">
                                @php $v = (float) ($row['buckets'][$bucket] ?? 0); @endphp
                                {{ $v != 0 ? number_format($v, 0, '.', ',') : '-' }}
                            </td>
                        @endforeach
                        <td class="number nowrap">{{ number_format((float) ($row['total_akhir'] ?? 0), 0, '.', ',') }}</td>
                    </tr>
                @endforeach

                <tr class="grand-total">
                    <td class="center">Total</td>
                    @foreach ($bucketFields as $bucket)
                        <td class="number nowrap">
                            @php $v = (float) ($grandTotals[$bucket] ?? 0); @endphp
                            {{ $v != 0 ? number_format($v, 0, '.', ',') : '-' }}
                        </td>
                    @endforeach
                    <td class="number nowrap">{{ number_format((float) ($grandTotals['total_akhir'] ?? 0), 0, '.', ',') }}
                    </td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="10">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>