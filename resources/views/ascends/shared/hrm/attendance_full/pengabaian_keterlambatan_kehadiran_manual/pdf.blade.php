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

        .group-row td {
            font-weight: bold;
            text-align: center;
            font-size: 11px;
            font-style: italic;
            padding: 8px 5px;
            background: #fff;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .summary-row td {
            border-top: 1px solid #000;
            border-bottom: 3px double #000;
            background: #fff;
            font-weight: bold;
            padding: 8px 3px;
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
            text-align: center;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .center {
            text-align: center;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            font-size: 11px;
            font-weight: bold;
            background: #fff;
            padding: 8px 3px;
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
        $footerCenterText = '';
        $formatSummary = static function (array $summary): string {
            if ($summary === []) {
                return '';
            }

            return implode('        ', array_map(
                static fn(array $item): string => (string) ($item['label'] ?? '') . ' : '
                . (int) ($item['count'] ?? 0) . ' (' . (int) ($item['percent'] ?? 0) . '%)',
                $summary,
            ));
        };
    @endphp

    <h1 class="report-title">{{ $reportData['title'] }}</h1>
    <p class="report-subtitle">{{ $periodLabel }}</p>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 8%;">Dibuat<br>Oleh</th>
                <th style="width: 20%;">Nama</th>
                <th style="width: 20%;">Jabatan</th>
                <th style="width: 28%;">Keterangan</th>
                <th style="width: 9%;">Tanggal</th>
                <th style="width: 9%;">Absen<br>Masuk</th>
                <th style="width: 9%;">Absen<br>Keluar</th>
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
                        <td class="center">{{ (string) ($row['Dibuat Oleh'] ?? '') }}</td>
                        <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                        <td>{{ (string) ($row['Jabatan'] ?? '') }}</td>
                        <td>{{ (string) ($row['Keterangan'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Tanggal'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Absen Masuk'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Absen Keluar'] ?? '') }}</td>
                    </tr>
                @endforeach
                <tr class="summary-row">
                    <td colspan="7">
                        <table class="summary-table">
                            <tr>
                                <td>Akumulasi Di Buat Oleh&nbsp;&nbsp;:&nbsp;&nbsp;{{ $formatSummary($summary) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            @empty
                <tr class="group-row">
                    <td colspan="7">Departemen :</td>
                </tr>
                <tr class="empty-row">
                    <td colspan="7">Tidak Ada Data</td>
                </tr>
                <tr class="summary-row">
                    <td colspan="7">
                        <table class="summary-table">
                            <tr>
                                <td>Akumulasi Di Buat Oleh&nbsp;&nbsp;:&nbsp;&nbsp;{{ $formatSummary($grandSummary) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>