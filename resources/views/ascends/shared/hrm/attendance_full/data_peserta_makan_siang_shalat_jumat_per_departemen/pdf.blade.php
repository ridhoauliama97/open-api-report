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
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .meta-table td {
            padding: 1px 2px;
            vertical-align: top;
        }

        .meta-label {
            width: 90px;
        }

        .meta-separator {
            width: 8px;
            text-align: center;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
            border-spacing: 0;
            border: 0.5pt solid #000;
        }

        .data-table th,
        .data-table td {
            border-left: 0.5pt solid #000;
            border-right: 0.5pt solid #000;
            padding: 3px 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 10px;
            text-align: center;
            border-top: 0.5pt solid #000;
            border-bottom: 0.5pt solid #000;
        }

        .number-cell {
            text-align: center;
            width: 6%;
        }

        .name-cell {
            width: 34%;
        }

        .check-cell {
            text-align: center;
            width: 6%;
            height: 18px;
        }

        .signature-cell {
            height: 42px;
            text-align: center;
            border-top: 0.5pt solid #000;
            border-bottom: 0.5pt solid #000;
        }

        .summary-cell {
            text-align: center;
            height: 20px;
            border-top: 0.5pt solid #000;
            border-bottom: 0.5pt solid #000;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .empty-row td {
            height: 16px;
            background: #fff;
        }

        .data-row td {
            border-top: 0;
            border-bottom: 0;
        }

        .empty-message {
            text-align: center;
            font-style: italic;
            font-weight: bold;
            font-size: 11px;
            background: #c9d1df;
        }
    </style>
</head>

<body>
    @php
        $departments = $reportData['departments'] ?? [];
        $dates = $reportData['dates'] ?? [];
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
    @endphp

    @forelse ($departments as $department)
        @php
            $departmentRows = array_values($department['rows'] ?? []);
            $minRows = max((int) ($department['min_rows'] ?? 0), count($departmentRows));
        @endphp

        <div style="page-break-after: {{ $loop->last ? 'auto' : 'always' }};">
            @include('ascends.shared.partials.report-header', ['subtitle' => $periodLabel])

            <table class="meta-table">
                <tr>
                    <td class="meta-label">Departemen</td>
                    <td class="meta-separator">:</td>
                    <td>{{ (string) ($department['department'] ?? '') }}</td>
                </tr>
                <tr>
                    <td class="meta-label">PJ Penerima</td>
                    <td class="meta-separator">:</td>
                    <td>{{ (string) ($department['pj_penerima'] ?? '') }}</td>
                </tr>
            </table>

            <table class="data-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="number-cell">No</th>
                        <th rowspan="2" class="name-cell">Nama</th>
                        @foreach ($dates as $date)
                            <th colspan="2">{{ (string) ($date['label'] ?? '') }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($dates as $date)
                            <th class="check-cell">Cek</th>
                            <th class="check-cell">Terima</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @for ($rowIndex = 0; $rowIndex < $minRows; $rowIndex++)
                        @php
                            $row = $departmentRows[$rowIndex] ?? null;
                        @endphp
                        <tr class="data-row {{ $row === null ? 'empty-row' : ($rowIndex % 2 === 0 ? 'row-odd' : 'row-even') }} {{ $rowIndex === 0 ? 'first-row' : '' }} {{ $rowIndex === $minRows - 1 ? 'last-row' : '' }}">
                            <td class="number-cell">{{ $rowIndex + 1 }}</td>
                            <td>{{ $row['Nama'] ?? '' }}</td>
                            @foreach ($dates as $date)
                                @php
                                    $dateKey = (string) ($date['date'] ?? '');
                                    $dateValue = $row['dates'][$dateKey] ?? ['cek' => '', 'terima' => ''];
                                @endphp
                                <td class="check-cell">{{ (string) ($dateValue['cek'] ?? '') }}</td>
                                <td class="check-cell">{{ (string) ($dateValue['terima'] ?? '') }}</td>
                            @endforeach
                        </tr>
                    @endfor
                    <tr>
                        <td colspan="2" class="signature-cell">Paraf Pj Penerima</td>
                        @foreach ($dates as $date)
                            <td colspan="2" class="signature-cell"></td>
                        @endforeach
                    </tr>
                    <tr>
                        <td colspan="2" class="summary-cell">Sub Total</td>
                        @foreach ($dates as $date)
                            <td colspan="2" class="summary-cell"></td>
                        @endforeach
                    </tr>
                    <tr>
                        <td colspan="2" class="summary-cell">Grand Total / Bulan</td>
                        <td colspan="{{ max(1, count($dates) * 2) }}" class="summary-cell"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @empty
        @include('ascends.shared.partials.report-header', ['subtitle' => $periodLabel])
        <table class="data-table">
            <tbody>
                <tr>
                    <td class="empty-message">Tidak Ada Data</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    <htmlpagefooter name="reportFooter">
        @include('reports.partials.pdf-footer-table', [
            'generatedByName' => $generatedByName,
            'generatedAtText' => $generatedAtText,
        ])
    </htmlpagefooter>
</body>

</html>
