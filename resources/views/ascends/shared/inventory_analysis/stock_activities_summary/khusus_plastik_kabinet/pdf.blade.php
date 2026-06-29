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
            line-height: 1.2;
            color: #000;
        }

        .report-companyTitle {
            text-align: center;
            margin: 0 0 2px 0;
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
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 11px;
            text-align: center;
        }

        .header-group th {
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .header-sub th {
            border-bottom: 1px solid #000;
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

        .total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 11px;
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
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
        $rows = $reportData['rows'] ?? [];
        $totals = $reportData['totals'] ?? [];
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerTitle = trim((string) ($title ?? $reportData['title'] ?? $fallbackTitle ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($rows) > 0)
        <table class="data-table">
            <thead>
                <tr class="header-group">
                    <th rowspan="2" style="width: 4%">No</th>
                    <th rowspan="2" style="width: 21%">Nama Barang</th>
                    <th colspan="4" style="width: 25%">Plastik Kabinet</th>
                    <th colspan="4" style="width: 25%">Komp P. Kabinet</th>
                    <th colspan="4" style="width: 25%">Perlengkapan Lemari</th>
                </tr>
                <tr class="header-sub">
                    <th style="width: 6.25%">Saldo Awal</th>
                    <th style="width: 6.25%">Debit</th>
                    <th style="width: 6.25%">Kredit</th>
                    <th style="width: 6.25%">Ending</th>
                    <th style="width: 6.25%">Saldo Awal</th>
                    <th style="width: 6.25%">Debit</th>
                    <th style="width: 6.25%">Kredit</th>
                    <th style="width: 6.25%">Ending</th>
                    <th style="width: 6.25%">Saldo Awal</th>
                    <th style="width: 6.25%">Debit</th>
                    <th style="width: 6.25%">Kredit</th>
                    <th style="width: 6.25%">Ending</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $index => $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $row['item_name'] ?? '' }}</td>
                        @php
                            $pk = $row['family_key'] === 'PLASTIK KABINET' ? $row : ['beginning' => 0, 'debit' => 0, 'credit' => 0, 'ending' => 0];
                            $kp = $row['family_key'] === 'KOMP. PL KABINET' ? $row : ['beginning' => 0, 'debit' => 0, 'credit' => 0, 'ending' => 0];
                            $pl = $row['family_key'] === 'PERLENGKAPAN LEMARI' ? $row : ['beginning' => 0, 'debit' => 0, 'credit' => 0, 'ending' => 0];
                        @endphp
                        <td class="number nowrap">{{ number_format($pk['beginning'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($pk['debit'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($pk['credit'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($pk['ending'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($kp['beginning'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($kp['debit'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($kp['credit'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($kp['ending'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($pl['beginning'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($pl['debit'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($pl['credit'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($pl['ending'], 2, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr class="empty-row">
                        <td colspan="14">Tidak ada data.</td>
                    </tr>
                @endforelse

                <tr class="total-row">
                    <td colspan="2" class="center">Total</td>
                    @php
                        $t = $totals;
                    @endphp
                    <td class="number nowrap">{{ number_format($t['PLASTIK KABINET']['beginning'] ?? 0, 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($t['PLASTIK KABINET']['debit'] ?? 0, 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($t['PLASTIK KABINET']['credit'] ?? 0, 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($t['PLASTIK KABINET']['ending'] ?? 0, 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($t['KOMP. PL KABINET']['beginning'] ?? 0, 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($t['KOMP. PL KABINET']['debit'] ?? 0, 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($t['KOMP. PL KABINET']['credit'] ?? 0, 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($t['KOMP. PL KABINET']['ending'] ?? 0, 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($t['PERLENGKAPAN LEMARI']['beginning'] ?? 0, 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($t['PERLENGKAPAN LEMARI']['debit'] ?? 0, 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($t['PERLENGKAPAN LEMARI']['credit'] ?? 0, 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($t['PERLENGKAPAN LEMARI']['ending'] ?? 0, 2, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="14">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
