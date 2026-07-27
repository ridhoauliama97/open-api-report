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
            font-size: 11px;
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

        .col-no {
            width: 6%;
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        $rows = $reportData['rows'] ?? [];
        $grandTotals = $reportData['grand_totals'] ?? [];
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));

        function fmtAmount($value)
        {
            $v = (float) $value;
            if ($v < 0) {
                return '(' . number_format(abs($v), 2, '.', ',') . ')';
            }
            if ($v == 0.0) {
                return '-';
            }
            return number_format($v, 2, '.', ',');
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($rows) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th style="width: 24%">Nama Supplier</th>
                    <th style="width: 15%">Tanggal</th>
                    <th style="width: 15%">Jatuh Tempo</th>
                    <th style="width: 20%">No. Giro</th>
                    <th style="width: 20%">Nilai</th>
                </tr>
            </thead>
            <tbody>
                @php $rowNum = 0; @endphp
                @foreach ($rows as $row)
                    @php $rowNum++; @endphp
                    <tr class="{{ $rowNum % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        <td class="center">{{ $rowNum }}</td>
                        <td>{{ $row['supplier_name'] }}</td>
                        <td class="center nowrap">
                            {{ $row['date'] ? $row['date']->locale('id')->isoFormat('DD-MMM-YY') : '-' }}
                        </td>
                        <td class="center nowrap">
                            {{ $row['due_date'] ? $row['due_date']->locale('id')->isoFormat('DD-MMM-YY') : '-' }}
                        </td>
                        <td class="center nowrap">{{ $row['check_number'] ?: '-' }}</td>
                        <td class="number nowrap">
                            {{ fmtAmount($row['net_total'] ?? 0) }}
                        </td>
                    </tr>
                @endforeach

                <tr class="grand-total">
                    <td class="center" colspan="5">Total</td>
                    <td class="number nowrap">
                        {{ fmtAmount($grandTotals['net_total'] ?? 0) }}
                    </td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="6">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>