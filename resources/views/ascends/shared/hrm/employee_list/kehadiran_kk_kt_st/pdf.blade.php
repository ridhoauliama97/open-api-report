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
            line-height: 1.18;
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

        .division-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .division-grid>tbody>tr>td {
            width: 100%;
            padding: 0 0 16px 0;
            vertical-align: top;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
            border: 1px solid #000;
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
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .group-row td {
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
            font-weight: bold;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .data-row td {
            height: 17px;
        }

        .center {
            text-align: center;
        }

        .follow-table {
            margin-top: 4px;
        }
    </style>
</head>

<body>
    @php
        $groupedRows = array_values($reportData['grouped_rows'] ?? []);
        $followUpRows = array_values($reportData['follow_up_rows'] ?? []);
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $printedAt = \Carbon\Carbon::parse($reportData['printed_at'] ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => 'Per : ' . $printedAt])

    <table class="division-grid">
        <tbody>
            @foreach ($groupedRows as $group)
                <tr>
                    @php
                        $groupRows = array_values($group['rows'] ?? []);
                    @endphp
                    <td>
                        <table class="data-table">
                            <tbody>
                                <tr class="group-row">
                                    <td colspan="3">{{ $group['label'] ?? '' }}</td>
                                </tr>
                                <tr class="summary-row">
                                    <td colspan="3">
                                        <table class="summary-table">
                                            <tr>
                                                <td style="width: 35%;">Anggota : ______ / ______ Orang</td>
                                                <td style="width: 30%;"></td>
                                                <td style="width: 35%; text-align: right;">Selisih : _____ Orang</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <th style="width: 7%;">No</th>
                                    <th style="width: 32%;">Nama</th>
                                    <th style="width: 61%;">Keterangan</th>
                                </tr>
                                @foreach ($groupRows as $row)
                                    <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                                        <td class="center">{{ $loop->iteration }}</td>
                                        <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                                        <td>{{ (string) ($row['Keterangan'] ?? '') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="data-table follow-table">
        <tbody>
            <tr class="group-row">
                <td colspan="6">Follow Up KK/KT/ST</td>
            </tr>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 26%;">Nama</th>
                <th style="width: 8%;">Divisi</th>
                <th style="width: 12%;">Penanganan</th>
                <th style="width: 30%;">Keterangan</th>
                <th style="width: 18%;">Follow Up</th>
            </tr>
            @forelse ($followUpRows as $row)
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                    <td>{{ (string) ($row['Divisi'] ?? '') }}</td>
                    <td>{{ (string) ($row['Penanganan'] ?? '') }}</td>
                    <td>{{ (string) ($row['Keterangan'] ?? '') }}</td>
                    <td>{{ (string) ($row['Follow Up'] ?? '') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="center" colspan="6">Tidak ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table', [
        'generatedAtText' => $generatedAtText,
        'generatedByName' => $generatedByName,
    ])
</body>

</html>
