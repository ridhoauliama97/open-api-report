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

            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
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
            font-weight: bold;
            font-size: 10px;
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
        $formatattachmentGender = static function (array $items): string {
            return implode(
                ' ',
                array_map(static function (array $item): string {
                    $part = '• ' . $item['label'] . ' = ' . $item['count'] . ' (' . $item['percent'] . '%)';
                    return $item['count'] > 0 ? '<strong>' . $part . '</strong>' : $part;
                }, $items),
            );
        };
        $formatattachmentPlain = static function (array $items): string {
            return implode(
                ' ',
                array_map(static function (array $item): string {
                    $part = $item['label'] . ' = ' . $item['count'] . ' (' . $item['percent'] . '%)';
                    return $item['count'] > 0 ? '<strong>' . $part . '</strong>' : $part;
                }, $items),
            );
        };
    @endphp

    @include('ascends.shared.partials.report-header')

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 18%;">Nama</th>
                <th style="width: 4%;">L/P</th>
                <th style="width: 22%;">Jabatan</th>
                <th style="width: 7%;">Status</th>
                <th style="width: 6%;">Level</th>
                <th style="width: 11%;">Tanggal<br>Masuk</th>
                <th style="width: 11%;">Tanggal<br>Keluar</th>
                <th style="width: 10%;">Masa<br>Kerja</th>
                <th style="width: 7%;">Alasan<br>Keluar</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groupedRows as $group)
                @php
                    $groupRows = $group['rows'] ?? [];
                    $summary = $group['summary'] ?? [];
                @endphp
                <tr class="group-row">
                    <td colspan="10">{{ $group['label'] ?? '' }}</td>
                </tr>
                @foreach ($groupRows as $row)
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
                        <td class="center">{{ (string) ($row['Alasan Keluar'] ?? '') }}</td>
                    </tr>
                @endforeach
                <tr class="summary-row">
                    <td colspan="10">
                        <table class="summary-table">
                            <tr>
                                <td style="font-weight: bold;">Sub Total :
                                    {{ (int) ($summary['subtotal'] ?? 0) }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3">Akumulasi L/P : {!! $formatattachmentGender($summary['gender'] ?? []) !!}</td>
                            </tr>
                            <tr>
                                <td colspan="3">Akumulasi Status : {!! $formatattachmentPlain($summary['status'] ?? []) !!}</td>
                            </tr>
                            <tr>
                                <td colspan="3">Akumulasi Level : {!! $formatattachmentPlain($summary['level'] ?? []) !!}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="10">Tidak ada data.</td>
                </tr>
            @endforelse

            <tr class="grand-row">
                <td colspan="10">SUMMARY</td>
            </tr>
            <tr class="summary-row">
                <td colspan="10">
                    <table class="summary-table">
                        <tr>
                            <td style="font-weight:bold;">Grand Total :
                                {{ (int) ($grandSummary['subtotal'] ?? 0) }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">Akumulasi L/P : {!! $formatattachmentGender($grandSummary['gender'] ?? []) !!}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">Akumulasi Status : {!! $formatattachmentPlain($grandSummary['status'] ?? []) !!}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">Akumulasi Level : {!! $formatattachmentPlain($grandSummary['level'] ?? []) !!}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
