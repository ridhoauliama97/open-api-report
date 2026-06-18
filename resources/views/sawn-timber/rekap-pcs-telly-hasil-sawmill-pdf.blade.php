<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
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

        .page-break {
            page-break-before: always;
        }

        .report-title {
            margin: 0;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
        }

        .meta-layout {
            width: 100%;
            margin: 10px 0 10px;
            table-layout: fixed;
        }

        .meta-layout td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .meta-block {
            width: 100%;
            table-layout: fixed;
        }

        .meta-block td {
            border: 0;
            padding: 0 0 2px 0;
            font-size: 9.5px;
            vertical-align: top;
        }

        .meta-label {
            width: 88px;
            white-space: nowrap;
        }

        .meta-separator {
            width: 10px;
            text-align: center;
        }

        .grade-title {
            margin: 10px 0 6px;
            font-size: 11px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            table-layout: fixed;
        }

        .split-layout {
            width: 100%;
            table-layout: fixed;
            margin: 0 0 6px 0;
        }

        .split-layout td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .split-gap {
            width: 5%;
        }

        .tebal-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
            margin-bottom: 3px;
        }

        .tebal-table th,
        .tebal-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            text-align: center;
            vertical-align: middle;
        }

        .tebal-table .headers-row th {
            font-size: 10px;
            font-weight: bold;
            border-top: 0;
            border-bottom: 1px solid #000;
            background: #fff;
        }

        .tebal-table tbody tr.data-row td.data-cell {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
        }

        .tebal-table tbody tr.row-last td.data-cell {
            border-bottom: 1px solid #000 !important;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .tebal-total {
            margin: 0 0 10px;
            text-align: right;
            font-size: 10px;
        }

        .grade-total {
            margin: 2px 0 10px;
            font-size: 11px;
        }

        .footer-summary {
            width: 100%;
            margin-top: 10px;
            table-layout: fixed;
        }

        .footer-summary td {
            border: 0;
            padding: 0;
            font-size: 11px;
        }

        .footer-summary .left {
            text-align: left;
        }

        .footer-summary .right {
            text-align: right;
        }

        .bottom-line {
            width: 100%;
            border: 1px solid #000;
            margin-top: 4px;
        }

        .bottom-line td {
            border: 0;
            padding: 2px 6px;
            text-align: right;
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
        $documents = is_array($reportData['documents'] ?? null) ? $reportData['documents'] : [];
        $formatDate = static function (?string $value, string $format = 'd-M-y'): string {
            if ($value === null || trim($value) === '') {
                return '-';
            }
            try {
                return \Carbon\Carbon::parse($value)->copy()->locale('id')->translatedFormat($format);
            } catch (\Throwable $e) {
                return $value;
            }
        };
        $formatNumber = static function ($value): string {
            $text = number_format((float) $value, 1, '.', '');
            return str_ends_with($text, '.0') ? substr($text, 0, -2) : $text;
        };
        $chunkPairs = static function (array $items): array {
            return array_chunk($items, 2);
        };
    @endphp

    <h1 class="report-title">Rekap Jumlah (Pcs) Telly Hasil Sawmill</h1>
    <p class="report-subtitle">Periode {{ $formatDate(isset($startDate) ? (string) $startDate : null, 'd-M-y') }}
        s/d {{ $formatDate(isset($endDate) ? (string) $endDate : null, 'd-M-y') }}</p>
    @foreach ($documents as $document)
        @php
            $header = is_array($document['header'] ?? null) ? $document['header'] : [];
            $grades = is_array($document['grades'] ?? null) ? $document['grades'] : [];
            $summary = is_array($document['summary'] ?? null) ? $document['summary'] : [];
        @endphp

        <hr />
        <table class="meta-layout">
            <tbody>
                <tr>
                    <td style="width: 50%; padding-right: 14px;">
                        <table class="meta-block">
                            <tbody>
                                <tr>
                                    <td class="meta-label">Nama Supplier</td>
                                    <td class="meta-separator">:</td>
                                    <td>{{ $header['supplier'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">Tanggal Masuk</td>
                                    <td class="meta-separator">:</td>
                                    <td>{{ $formatDate($header['tanggal'] ?? null) }}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">No.Kayu Bulat</td>
                                    <td class="meta-separator">:</td>
                                    <td>{{ $header['no_kayu_bulat'] ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td style="width: 50%; padding-left: 14px;">
                        <table class="meta-block">
                            <tbody>
                                <tr>
                                    <td class="meta-label">No.Surat Ket</td>
                                    <td class="meta-separator">:</td>
                                    <td>{{ $header['suket'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">Jenis Kayu</td>
                                    <td class="meta-separator">:</td>
                                    <td>{{ $header['jenis_kayu'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">No Plat</td>
                                    <td class="meta-separator">:</td>
                                    <td>{{ $header['no_plat'] ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

        @foreach ($grades as $grade)
            @php
                $tebalGroups = is_array($grade['tebal_groups'] ?? null) ? $grade['tebal_groups'] : [];
                $pairs = $chunkPairs($tebalGroups);
            @endphp

            <div class="grade-title">{{ $grade['name'] }}</div>

            @foreach ($pairs as $pair)
                <table class="split-layout">
                    <tbody>
                        <tr>
                            @foreach ([0, 1] as $index)
                                @php $tebalGroup = $pair[$index] ?? null; @endphp
                                <td style="width: 47.5%;">
                                    @if (is_array($tebalGroup))
                                        <table class="tebal-table">
                                            <thead>
                                                <tr class="headers-row">
                                                    <th style="width: 33%;">Tebal</th>
                                                    <th style="width: 33%;">Lebar</th>
                                                    <th style="width: 34%;">Pcs</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($tebalGroup['rows'] ?? [] as $row)
                                                    <tr
                                                        class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }} {{ $loop->last ? 'row-last' : '' }}">
                                                        <td class="data-cell">{{ $formatNumber($row['tebal'] ?? 0) }}
                                                        </td>
                                                        <td class="data-cell">{{ $formatNumber($row['lebar'] ?? 0) }}
                                                        </td>
                                                        <td class="number data-cell">
                                                            {{ number_format((int) ($row['pcs'] ?? 0), 0, '.', ',') }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        <div class="tebal-total">Jmlh Tebal
                                            {{ $formatNumber($tebalGroup['tebal'] ?? 0) }} :
                                            <strong>{{ number_format((int) ($tebalGroup['total_pcs'] ?? 0), 0, '.', ',') }}</strong>
                                        </div>
                                    @else
                                        <table class="tebal-table">
                                            <thead>
                                                <tr class="headers-row">
                                                    <th style="width: 33%;">Tebal</th>
                                                    <th style="width: 33%;">Lebar</th>
                                                    <th style="width: 34%;">Pcs</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="data-row row-even row-last">
                                                    <td class="data-cell">&nbsp;</td>
                                                    <td class="data-cell">&nbsp;</td>
                                                    <td class="data-cell">&nbsp;</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div class="tebal-total">&nbsp;</div>
                                    @endif
                                </td>
                                @if ($index === 0)
                                    <td class="split-gap"></td>
                                @endif
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            @endforeach

            <div class="grade-total">Jmlh {{ $grade['name'] }} :
                <strong>{{ number_format((int) ($grade['total_pcs'] ?? 0), 0, '.', ',') }}</strong>
            </div>
        @endforeach

        <table class="footer-summary">
            <tbody>
                <tr>
                    <td class="left">Jmlh Per- Tanggal {{ $formatDate($header['tanggal'] ?? null) }} :
                        <strong>{{ number_format((int) ($summary['total_pcs'] ?? 0), 0, '.', ',') }}</strong>
                    </td>
                    <td class="right">Jmlh Per- {{ $header['supplier'] ?? '-' }} :
                        <strong>{{ number_format((int) ($summary['total_pcs'] ?? 0), 0, '.', ',') }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    @endforeach

    @include('reports.partials.pdf-footer-table')
</body>

</html>
