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
            margin: 2px 0 16px 0;
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
            vertical-align: top;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
            vertical-align: middle;
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

        .group-row td {
            font-weight: bold;
            text-align: center;
            font-size: 11px;
            font-style: italic;
            padding: 4px 5px;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .summary-row td,
        .summary-note td,
        .grand-row td {
            border-top: 0;
            border-bottom: 0;
        }

        .summary-row td,
        .grand-row td {
            text-align: right;
            border-top: 1px solid #000;
        }

        .grand-row td {
            border-bottom: 1px solid #000;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            font-size: 11px;
            border-bottom: 1px solid #000;
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

        $summaryText = static function (array $items, array $labels): string {
            $parts = [];
            foreach ($labels as $key => $label) {
                $item = $items[$key] ?? ['count' => 0, 'percent' => 0];
                $count = (int) ($item['count'] ?? 0);
                $percent = (int) ($item['percent'] ?? 0);
                $text = e($label) . ' = ' . $count . ' (' . $percent . '%)';
                $parts[] = $count > 0 ? '<strong>' . $text . '</strong>' : $text;
            }

            return implode('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $parts);
        };
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => $periodLabel])

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 18%;">Nama</th>
                <th style="width: 4%;">L/P</th>
                <th style="width: 17%;">Jabatan</th>
                <th style="width: 6%;">Status</th>
                <th style="width: 6%;">SP</th>
                <th style="width: 10%;">Tanggal Aktif</th>
                <th style="width: 10%;">Tanggal Berakhir</th>
                <th style="width: 29%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groupedRows as $group)
                @php
                    $rows = $group['rows'] ?? [];
                    $summary = $group['summary'] ?? [];
                @endphp
                <tr class="group-row">
                    <td colspan="8">Departemen : {{ (string) ($group['department'] ?? '') }}</td>
                </tr>
                @foreach ($rows as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['L/P'] ?? '') }}</td>
                        <td>{{ (string) ($row['Jabatan'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Status'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['SP'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Tanggal Aktif'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Tanggal Berakhir'] ?? '') }}</td>
                        <td>{{ (string) ($row['Keterangan'] ?? '') }}</td>
                    </tr>
                @endforeach

                {{-- Rangkuman Per Departemen --}}
                <tr class="summary-row">
                    <td colspan="8">Sub Total = {{ (int) ($summary['subtotal'] ?? 0) }}</td>
                </tr>
                <tr class="summary-note">
                    <td colspan="8">Akumulasi L/P : {!! $summaryText($summary['sex'] ?? [], ['L' => 'Laki - Laki', 'P' => 'Perempuan']) !!}</td>
                </tr>
                <tr class="summary-note">
                    <td colspan="8">Akumulasi Status : {!! $summaryText($summary['status'] ?? [], ['BR' => 'BR', 'KK' => 'KK', 'KT' => 'KT', 'ST' => 'ST']) !!}</td>
                </tr>
                <tr class="summary-note">
                    <td colspan="8">Akumulasi Level : {!! $summaryText($summary['level'] ?? [], [1 => 'Level 1', 2 => 'Level 2', 3 => 'Level 3', 4 => 'Level 4']) !!}</td>
                </tr>
                <tr class="summary-note">
                    <td colspan="8">{!! $summaryText($summary['level'] ?? [], [5 => 'Level 5', 6 => 'Level 6', 7 => 'Level 7']) !!}</td>
                </tr>
                <tr class="summary-note">
                    <td colspan="8">Akumulasi SP : {!! $summaryText($summary['sp'] ?? [], ['SP 1' => 'SP 1', 'SP 2' => 'SP 2', 'SP 3' => 'SP 3']) !!}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="8">Tidak Ada Data</td>
                </tr>
            @endforelse

            {{-- Rangkuman Keseluruhan --}}
            @if (!empty($groupedRows))
                <tr class="group-row">
                    <td colspan="8">Rangkuman</td>
                </tr>
                <tr class="summary-row">
                    <td colspan="8">Grand Total = {{ (int) ($grandSummary['subtotal'] ?? 0) }}</td>
                </tr>
                <tr class="summary-note">
                    <td colspan="8">Total Departemen : <strong>{{ count($groupedRows) }} Departemen</strong></td>
                </tr>
                <tr class="summary-note">
                    <td colspan="8">Akumulasi L/P : {!! $summaryText($grandSummary['sex'] ?? [], ['L' => 'Laki - Laki', 'P' => 'Perempuan']) !!}</td>
                </tr>
                <tr class="summary-note">
                    <td colspan="8">Akumulasi Status : {!! $summaryText($grandSummary['status'] ?? [], ['BR' => 'BR', 'KK' => 'KK', 'KT' => 'KT', 'ST' => 'ST']) !!}</td>
                </tr>
                <tr class="summary-note">
                    <td colspan="8">Akumulasi Level : {!! $summaryText($grandSummary['level'] ?? [], [1 => 'Level 1', 2 => 'Level 2', 3 => 'Level 3', 4 => 'Level 4']) !!}</td>
                </tr>
                <tr class="summary-note">
                    <td colspan="8">{!! $summaryText($grandSummary['level'] ?? [], [5 => 'Level 5', 6 => 'Level 6', 7 => 'Level 7']) !!}</td>
                </tr>
                <tr class="summary-note">
                    <td colspan="8">Akumulasi SP : {!! $summaryText($grandSummary['sp'] ?? [], ['SP 1' => 'SP 1', 'SP 2' => 'SP 2', 'SP 3' => 'SP 3']) !!}</td>
                </tr>
            @endif
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
