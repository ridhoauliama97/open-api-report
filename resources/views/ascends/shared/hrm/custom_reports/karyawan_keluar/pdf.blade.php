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
            font-size: 9px;
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
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .department-row td {
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            font-style: italic;
            padding: 4px 5px;
            color: #000;
            border-bottom: 1px solid #000;
        }

        .grand-row td {
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            font-style: italic;
            padding: 4px 5px;
            color: #000;
        }

        .summary-row td {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 3px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .summary-table td {
            border: 0;
            padding: 1px 2px;
            vertical-align: top;
        }

        .summary-label {
            width: 18%;
        }

        .summary-separator {
            width: 2%;
            text-align: center;
        }

        .summary-value {
            width: 40%;
        }

        .center {
            text-align: center;
        }

        .nowrap {
            white-space: nowrap;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            color: #9c111d;
            font-weight: bold;
            font-size: 11px;
        }

        .hasil-cell {
            white-space: pre-wrap;
            font-size: 9px;
            line-height: 1.25;
        }
    </style>
</head>

<body>
    @php
        $groupedRows = $reportData['grouped_rows'] ?? [];
        $grandSummary = $reportData['grand_summary'] ?? [];
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $summaryItems = static function (array $summary): array {
            return array_values(
                array_map(
                    static fn(array $item): array => [
                        'label' => (string) ($item['label'] ?? ''),
                        'count' => (int) ($item['count'] ?? 0),
                        'percent' => (int) ($item['percent'] ?? 0),
                    ],
                    $summary,
                ),
            );
        };
        $summaryPairs = static function (array $items): array {
            $leftCount = (int) ceil(count($items) / 2);
            $leftItems = array_slice($items, 0, $leftCount);
            $rightItems = array_slice($items, $leftCount);
            $rows = [];
            for ($i = 0; $i < $leftCount; $i++) {
                $rows[] = [$leftItems[$i] ?? null, $rightItems[$i] ?? null];
            }
            return $rows;
        };
        $formatSummaryItem = static function (?array $item): string {
            if ($item === null) {
                return '';
            }
            return '• ' . $item['label'] . ' = ' . $item['count'] . ' (' . $item['percent'] . '%)';
        };
        $headerCompany = $reportData['headerCompany'] ?? '';
        $headerTitle = $reportData['headerTitle'] ?? 'Laporan Karyawan Keluar Per Departemen Per Tanggal Keluar';
        $startDate = trim((string) ($reportData['start_date'] ?? ''));
        $endDate = trim((string) ($reportData['end_date'] ?? ''));
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">Periode : {{ $startDate }} s/d {{ $endDate }}</p>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 16%;">Nama</th>
                <th style="width: 4%;">L/P</th>
                <th style="width: 11%;">Jabatan</th>
                <th style="width: 5%;">Status</th>
                <th style="width: 5%;">Level</th>
                <th style="width: 7%;">Tanggal<br>Masuk</th>
                <th style="width: 7%;">Tanggal<br>Keluar</th>
                <th style="width: 9%;">Masa<br>Kerja</th>
                <th style="width: 14%;">Alasan Keluar</th>
                <th style="width: 18%;">Hasil</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groupedRows as $department)
                @php
                    $summary = $department['summary'] ?? [];
                @endphp
                <tr class="department-row">
                    <td colspan="11">{{ $department['label'] ?? '' }}</td>
                </tr>

                @foreach ($department['rows'] ?? [] as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['L/P'] ?? '') }}</td>
                        <td>{{ (string) ($row['Jabatan'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Status'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Level'] ?? '') }}</td>
                        <td class="center nowrap">{{ (string) ($row['Tanggal Masuk'] ?? '') }}</td>
                        <td class="center nowrap">{{ (string) ($row['Tanggal Keluar'] ?? '') }}</td>
                        <td class="center nowrap">{{ (string) ($row['Masa Kerja'] ?? '') }}</td>
                        <td>{{ (string) ($row['Alasan Keluar'] ?? '') }}</td>
                        <td class="hasil-cell">{{ (string) ($row['Hasil'] ?? '') }}</td>
                    </tr>
                @endforeach

                <tr class="summary-row">
                    <td colspan="11">
                        <table class="summary-table">
                            <tr>
                                <td class="summary-label">Sub Total</td>
                                <td class="summary-separator">:</td>
                                <td colspan="2">{{ (int) ($summary['subtotal'] ?? $department['subtotal'] ?? 0) }}</td>
                            </tr>
                            @foreach ($summaryPairs($summaryItems($summary['gender'] ?? [])) as $pair)
                                <tr>
                                    <td class="summary-label">{{ $loop->first ? 'Akumulasi L/P' : '' }}</td>
                                    <td class="summary-separator">{{ $loop->first ? ':' : '' }}</td>
                                    <td class="summary-value">{{ $formatSummaryItem($pair[0]) }}</td>
                                    <td class="summary-value">{{ $formatSummaryItem($pair[1]) }}</td>
                                </tr>
                            @endforeach
                            @foreach ($summaryPairs($summaryItems($summary['status'] ?? [])) as $pair)
                                <tr>
                                    <td class="summary-label">{{ $loop->first ? 'Akumulasi Status' : '' }}</td>
                                    <td class="summary-separator">{{ $loop->first ? ':' : '' }}</td>
                                    <td class="summary-value">{{ $formatSummaryItem($pair[0]) }}</td>
                                    <td class="summary-value">{{ $formatSummaryItem($pair[1]) }}</td>
                                </tr>
                            @endforeach
                            @foreach ($summaryPairs($summaryItems($summary['level'] ?? [])) as $pair)
                                <tr>
                                    <td class="summary-label">{{ $loop->first ? 'Akumulasi Level' : '' }}</td>
                                    <td class="summary-separator">{{ $loop->first ? ':' : '' }}</td>
                                    <td class="summary-value">{{ $formatSummaryItem($pair[0]) }}</td>
                                    <td class="summary-value">{{ $formatSummaryItem($pair[1]) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="11">Tidak ada data.</td>
                </tr>
            @endforelse

            <tr class="grand-row">
                <td colspan="11">Rangkuman</td>
            </tr>
            <tr class="summary-row">
                <td colspan="11">
                    <table class="summary-table">
                        <tr>
                            <td class="summary-label">Grand Total</td>
                            <td class="summary-separator">:</td>
                            <td colspan="2">{{ (int) ($grandSummary['subtotal'] ?? $reportData['total_rows'] ?? 0) }}
                            </td>
                        </tr>
                        @foreach ($summaryPairs($summaryItems($grandSummary['gender'] ?? [])) as $pair)
                            <tr>
                                <td class="summary-label">{{ $loop->first ? 'Akumulasi L/P' : '' }}</td>
                                <td class="summary-separator">{{ $loop->first ? ':' : '' }}</td>
                                <td class="summary-value">{{ $formatSummaryItem($pair[0]) }}</td>
                                <td class="summary-value">{{ $formatSummaryItem($pair[1]) }}</td>
                            </tr>
                        @endforeach
                        @foreach ($summaryPairs($summaryItems($grandSummary['status'] ?? [])) as $pair)
                            <tr>
                                <td class="summary-label">{{ $loop->first ? 'Akumulasi Status' : '' }}</td>
                                <td class="summary-separator">{{ $loop->first ? ':' : '' }}</td>
                                <td class="summary-value">{{ $formatSummaryItem($pair[0]) }}</td>
                                <td class="summary-value">{{ $formatSummaryItem($pair[1]) }}</td>
                            </tr>
                        @endforeach
                        @foreach ($summaryPairs($summaryItems($grandSummary['level'] ?? [])) as $pair)
                            <tr>
                                <td class="summary-label">{{ $loop->first ? 'Akumulasi Level' : '' }}</td>
                                <td class="summary-separator">{{ $loop->first ? ':' : '' }}</td>
                                <td class="summary-value">{{ $formatSummaryItem($pair[0]) }}</td>
                                <td class="summary-value">{{ $formatSummaryItem($pair[1]) }}</td>
                            </tr>
                        @endforeach
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
