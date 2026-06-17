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
                <th style="width: 8%;">NIK</th>
                <th style="width: 20%;">Nama Karyawan</th>
                <th style="width: 24%;">Jabatan</th>
                <th style="width: 10%;">Tanggal <br> Izin</th>
                <th style="width: 7%;">Total <br> Jam</th>
                <th style="width: 7%;">Total <br> Menit</th>
                <th style="width: 24%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groupedRows as $group)
                @php
                    $rows = $group['rows'] ?? [];
                    $summary = $group['summary'] ?? [];
                @endphp
                <tr class="group-row">
                    <td colspan="7">Departemen : {{ (string) ($group['department'] ?? '') }}</td>
                </tr>
                @foreach ($rows as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ (string) ($row['NIK'] ?? '') }}</td>
                        <td>{{ (string) ($row['Nama Karyawan'] ?? '') }}</td>
                        <td>{{ (string) ($row['Jabatan'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Tanggal Izin'] ?? '') }}</td>
                        <td class="center">{{ (float) ($row['Total Jam'] ?? 0) }}</td>
                        <td class="center">{{ (int) ($row['Total Menit'] ?? 0) }}</td>
                        <td>{{ (string) ($row['Keterangan'] ?? '') }}</td>
                    </tr>
                @endforeach

                {{-- Rangkuman Per Departemen --}}
                <tr class="summary-row">
                    <td colspan="7">Sub Total = {{ (int) ($summary['subtotal'] ?? 0) }}</td>
                </tr>
                <tr class="summary-note">
                    <td colspan="7">Akumulasi Status : {!! $summaryText($summary['status'] ?? [], ['KK' => 'KK', 'KT' => 'KT', 'ST' => 'ST', 'BR' => 'BR']) !!}</td>
                </tr>
                <tr class="summary-note">
                    <td colspan="7">Total Jam = {{ (float) ($summary['total_jam'] ?? 0) }} &nbsp;&nbsp; Total Menit = {{ (int) ($summary['total_menit'] ?? 0) }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="7">Tidak Ada Data</td>
                </tr>
            @endforelse

            {{-- Rangkuman Keseluruhan --}}
            @if (!empty($groupedRows))
                <tr class="group-row">
                    <td colspan="7">Rangkuman</td>
                </tr>
                <tr class="summary-row">
                    <td colspan="7">Grand Total = {{ (int) ($grandSummary['subtotal'] ?? 0) }}</td>
                </tr>
                <tr class="summary-note">
                    <td colspan="7">Total Departemen : {{ count($groupedRows) }} Departemen</td>
                </tr>
                <tr class="summary-note">
                    <td colspan="7">Akumulasi Status : {!! $summaryText($grandSummary['status'] ?? [], ['KK' => 'KK', 'KT' => 'KT', 'ST' => 'ST', 'BR' => 'BR']) !!}</td>
                </tr>
                <tr class="summary-note">
                    <td colspan="7">Total Jam = {{ (float) ($grandSummary['total_jam'] ?? 0) }} &nbsp;&nbsp; Total Menit = {{ (int) ($grandSummary['total_menit'] ?? 0) }}</td>
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
