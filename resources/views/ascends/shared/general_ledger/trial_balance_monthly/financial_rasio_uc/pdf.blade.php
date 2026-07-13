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

        .ratio-section {
            margin-bottom: 2px;
        }

        .ratio-title {
            font-size: 12px;
            font-weight: bold;
            margin: 12px 0 2px 0;
            padding: 0;
            color: #000;
        }

        .ratio-description {
            font-size: 10px;
            font-style: italic;
            margin: 0 0 6px 0;
            line-height: 1.3;
            text-align: justify;
        }

        .ratio-footer-note {
            font-size: 10px;
            font-style: italic;
            margin: 4px 0 0 0;
            line-height: 1.3;
            text-align: justify;
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
            padding: 1px 2px;
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

        .data-table td {
            font-size: 10px;
            border-top: none;
            border-bottom: none;
        }

        .number {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .number-negative {
            color: #9c111d;
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

        .col-no {
            width: 5%;
        }

        .col-bulan {
            width: 15%;
        }

        .col-value {
            width: 30%;
        }

        .col-rasio {
            width: 20%;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            color: #9c111d;
            font-size: 10px;
            padding: 8px 4px;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    @php
        $ratios = $reportData['ratios'] ?? [];
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
                return '- ' . number_format(abs($v), 0, ',', '.');
            }
            if ($v == 0.0) {
                return '-';
            }
            return number_format($v, 0, ',', '.');
        }

        function fmtRasio($value)
        {
            $v = (float) $value;
            return number_format($v, 2, ',', '.') . '%';
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($ratios) > 0)
        @foreach ($ratios as $index => $ratio)
            <div class="ratio-section">
                <p class="ratio-title">{{ $ratio['title'] }}</p>
                <p class="ratio-description">{{ $ratio['description'] }}</p>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-no">{{ $ratio['columns'][0] ?? 'No' }}</th>
                            <th class="col-bulan">{{ $ratio['columns'][1] ?? 'Bulan' }}</th>
                            <th class="col-value">{{ $ratio['columns'][2] ?? '' }}</th>
                            <th class="col-value">{{ $ratio['columns'][3] ?? '' }}</th>
                            <th class="col-rasio">{{ $ratio['columns'][4] ?? 'Rasio %' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ratio['rows'] as $row)
                            @php
                                $rasio = (float) ($row['rasio'] ?? 0);
                                $nilaiX = (float) ($row['nilai_x'] ?? 0);
                                $nilaiY = (float) ($row['nilai_y'] ?? 0);
                            @endphp
                            <tr class="{{ $loop->iteration % 2 === 0 ? 'row-even' : 'row-odd' }}">
                                <td class="center">{{ $row['no'] }}</td>
                                <td>{{ $row['bulan'] }}</td>
                                <td class="number nowrap {{ $nilaiX < 0 ? 'number-negative' : '' }}">
                                    {{ fmtAmount($nilaiX) }}
                                </td>
                                <td class="number nowrap {{ $nilaiY < 0 ? 'number-negative' : '' }}">
                                    {{ fmtAmount($nilaiY) }}
                                </td>
                                <td class="number nowrap {{ $rasio < 0 ? 'number-negative' : '' }}">
                                    {{ fmtRasio($rasio) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if (!empty($ratio['footer_note']))
                    <p class="ratio-footer-note">{!! $ratio['footer_note'] !!}</p>
                @endif
            </div>
        @endforeach
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td>Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
