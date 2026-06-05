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
            padding: 2px 3px;
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

        .group-row td,
        .grand-row td {
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            font-style: italic;
            padding: 4px 5px;
            color: #9c111d;
            background: #fff;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .summary-row td {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            background: #fff;
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
            background: #fff;
            vertical-align: top;
        }

        .summary-label {
            width: 24%;
        }

        .summary-separator {
            width: 2%;
            text-align: center;
        }

        .summary-value {
            width: 37%;
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
            font-weight: bold;
            font-size: 10px;
        }
    </style>
</head>

<body>
    @php
        $groupedRows = $reportData['grouped_rows'] ?? [];
        $grandSummary = $reportData['grand_summary'] ?? [];
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
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
        $formatPercentageSummary = static function (array $summary): string {
            $percent = $summary['attendance_percentage'] ?? [];

            return 'Min = ' . (int) ($percent['min'] ?? 0) . '%  '
                . 'Max = ' . (int) ($percent['max'] ?? 0) . '%  '
                . 'Avg = ' . (int) ($percent['avg'] ?? 0) . '%';
        };
    @endphp

    <h1 class="report-title">{{ $reportData['title'] }}</h1>
    <p class="report-subtitle">{{ $periodLabel }}</p>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 25%;">Nama</th>
                <th style="width: 6%;">L/P</th>
                <th style="width: 34%;">Jabatan</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 12%;">Level</th>
                <th style="width: 10%;">%</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groupedRows as $group)
                @php
                    $groupRows = $group['rows'] ?? [];
                    $summary = $group['summary'] ?? [];
                @endphp
                <tr class="group-row">
                    <td colspan="7">{{ $group['label'] ?? '' }}</td>
                </tr>
                @foreach ($groupRows as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['L/P'] ?? '') }}</td>
                        <td>{{ (string) ($row['Jabatan'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Status'] ?? '') }}</td>
                        <td class="center nowrap">{{ (string) ($row['Level'] ?? '') }}</td>
                        <td class="center nowrap">{{ (string) ($row['%'] ?? '') }}</td>
                    </tr>
                @endforeach
                <tr class="summary-row">
                    <td colspan="7">
                        <table class="summary-table">
                            <tr>
                                <td class="summary-label">Sub Total</td>
                                <td class="summary-separator">:</td>
                                <td colspan="2">{{ (int) ($summary['subtotal'] ?? 0) }}</td>
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
                            <tr>
                                <td class="summary-label">Akumulasi Persentase Kehadiran</td>
                                <td class="summary-separator">:</td>
                                <td colspan="2">{{ $formatPercentageSummary($summary) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="7">Tidak ada data attendance yang dapat ditampilkan.</td>
                </tr>
            @endforelse
            <tr class="grand-row">
                <td colspan="7">Grand Total : {{ (int) ($grandSummary['subtotal'] ?? 0) }}</td>
            </tr>
            <tr class="summary-row">
                <td colspan="7">
                    <table class="summary-table">
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
                        <tr>
                            <td class="summary-label">Akumulasi Persentase Kehadiran</td>
                            <td class="summary-separator">:</td>
                            <td colspan="2">{{ $formatPercentageSummary($grandSummary) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    <htmlpagefooter name="reportFooter">
        @include('reports.partials.pdf-footer-table', [
            'generatedByName' => $generatedByName,
            'generatedAtText' => $generatedAtText,
        ])
    </htmlpagefooter>
</body>

</html>
