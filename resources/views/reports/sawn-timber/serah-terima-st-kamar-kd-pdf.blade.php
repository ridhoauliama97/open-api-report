<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    <style>
        * {
            margin: 0;
            padding: 0;
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

        .report-title {
            text-align: center;
            margin: 2px 0 20px 0;
            font-size: 16px;
            font-weight: bold;
        }

        .meta-table {
            width: 100%;
            margin: 14px 0 10px 0;
            border-collapse: collapse;
        }

        .meta-table td {
            border: 0;
            padding: 1px 4px;
            vertical-align: top;
        }

        .meta-label {
            width: 16%;
            white-space: nowrap;
        }

        .meta-separator {
            width: 2%;
            text-align: center;
        }

        .meta-value {
            width: 32%;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
        }

        table.data-table th,
        table.data-table td {
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        table.data-table th:first-child,
        table.data-table td:first-child {
            border-left: 0;
        }

        table.data-table th {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            border-bottom: 1px solid #000;
            background: #fff;
        }

        table.data-table tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        table.data-table tbody tr.no-st-start td {
            border-top: 1px solid #000;
        }

        table.data-table tfoot td {
            border-top: 1px solid #000;
            font-weight: bold;
            font-size: 11px;
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

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .summary-table {
            width: 46%;
            margin-top: 14px;
            border-collapse: collapse;
        }

        .summary-table td {
            border: 0;
            padding: 2px 4px;
        }

        .handover-summary {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
            font-size: 10px;
        }

        .handover-summary td {
            border: 0;
            padding: 0 4px;
            vertical-align: top;
        }

        .signature-table {
            width: 100%;
            margin-top: 26px;
            border-collapse: collapse;
            font-size: 10px;
        }

        .signature-table td {
            border: 0;
            padding: 0 4px;
            text-align: center;
            vertical-align: top;
        }

        .signature-space {
            height: 60px;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $groups = is_array($data['no_st_groups'] ?? null) ? $data['no_st_groups'] : [];
        $header = is_array($data['header'] ?? null) ? $data['header'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];

        if ($groups === [] && $rows !== []) {
            $groupedRows = [];

            foreach ($rows as $row) {
                $noSt = trim((string) ($row['NoST'] ?? ''));
                $groupKey = $noSt !== '' ? $noSt : 'Tanpa No ST';

                if (!isset($groupedRows[$groupKey])) {
                    $groupedRows[$groupKey] = [
                        'no_st' => $groupKey,
                        'rows' => [],
                    ];
                }

                $groupedRows[$groupKey]['rows'][] = $row;
            }

            $groups = array_values($groupedRows);
        }

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $fmtDecimal = static function ($v, int $decimals = 4): string {
            $n = (float) ($v ?? 0.0);
            return number_format($n, $decimals, '.', ',');
        };

        $fmtSize = static function ($v): string {
            $n = (float) ($v ?? 0.0);
            return rtrim(rtrim(number_format($n, 3, '.', ','), '0'), '.');
        };

        $fmtDate = static function ($v): string {
            $t = is_string($v) ? trim($v) : '';
            if ($t === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($t)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $e) {
                return $t;
            }
        };
    @endphp

    <h1 class="report-title">Laporan Serah Terima ST (Kamar KD)</h1>

    <table class="meta-table">
        <tbody>
            <tr>
                <td class="meta-label">No.Proses KD</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ $header['NoProcKD'] ?? '-' }}</td>
                <td class="meta-label">No.Ruang KD</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ $header['NoRuangKD'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Tanggal Masuk</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ $fmtDate($header['TglMasuk'] ?? '') }}</td>
                <td class="meta-label">Tanggal Keluar</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ $fmtDate($header['TglKeluar'] ?? '') }}</td>
            </tr>
        </tbody>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">Cek</th>
                <th style="width: 18%;">No ST</th>
                <th style="width: 5%;">No</th>
                <th style="width: 11%;">Lokasi</th>
                <th style="width: 9%;">Tebal</th>
                <th style="width: 9%;">Lebar</th>
                <th style="width: 9%;">Panjang</th>
                <th style="width: 10%;">Pcs</th>
                <th style="width: 12%;">Ton</th>
                <th style="width: 12%;">Kubik</th>
            </tr>
        </thead>
        <tbody>
            @php
                $globalRowNumber = 0;
            @endphp
            @if ($groups === [])
                <tr>
                    <td colspan="10" class="center">Tidak ada data.</td>
                </tr>
            @endif

            @foreach ($groups as $group)
                @php
                    $groupRows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
                    $rowspan = max(count($groupRows), 1);
                @endphp

                @foreach ($groupRows as $row)
                    @php
                        $globalRowNumber++;
                    @endphp
                    <tr
                        class="{{ $globalRowNumber % 2 === 1 ? 'row-odd' : 'row-even' }}{{ $loop->first ? ' no-st-start' : '' }}">
                        @if ($loop->first)
                            <td rowspan="{{ $rowspan }}" class="center">&#9633;</td>
                            <td rowspan="{{ $rowspan }}">{{ $group['no_st'] ?? ($row['NoST'] ?? '') }}</td>
                        @endif
                        <td class="center">{{ $loop->iteration }}</td>
                        <td></td>
                        <td class="number">{{ $fmtSize($row['Tebal'] ?? 0) }}</td>
                        <td class="number">{{ $fmtSize($row['Lebar'] ?? 0) }}</td>
                        <td class="number">{{ $fmtSize($row['Panjang'] ?? 0) }}</td>
                        <td class="number">{{ number_format((int) ($row['JmlhBatang'] ?? 0), 0, '.', ',') }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtDecimal($row['Ton'] ?? 0) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtDecimal($row['Kubik'] ?? 0) }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8" class="center">Total Dari Ruang KD {{ $header['NoRuangKD'] ?? '-' }}</td>
                <td class="number">{{ $fmtDecimal($summary['total_ton'] ?? 0) }}</td>
                <td class="number">{{ $fmtDecimal($summary['total_kubik'] ?? 0) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- <table class="handover-summary">
        <tbody>
            <tr>
                <td style="width: 25%; font-weight: bold;">
                    Jmlh Label Dari No.KD : {{ number_format((int) ($summary['total_no_st'] ?? 0), 0, '.', ',') }}
                </td>
                <td style="width: 38%; font-weight: bold;">
                    Jmlh Dari Proses KD : {{ $header['NoProcKD'] ?? '-' }}
                </td>
                <td class="number" style="width: 18%; font-weight: bold;">{{ $fmtDecimal($summary['total_ton'] ?? 0) }}
                </td>
                <td class="number" style="width: 19%; font-weight: bold;">
                    {{ $fmtDecimal($summary['total_kubik'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table> --}}

    <table class="signature-table">
        <tbody>
            <tr>
                <td style="width: 40%;">Yang Menyerahkan</td>
                <td style="width: 20%;"></td>
                <td style="width: 40%;">Yang Menerima</td>
            </tr>
            <tr>
                <td class="signature-space"></td>
                <td></td>
                <td class="signature-space"></td>
            </tr>
            <tr>
                <td>( ................................ )</td>
                <td></td>
                <td>( ................................ )</td>
            </tr>
            <tr>
                <td></td>
                <td style="padding-top: 14px;">Diketahui Oleh</td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td class="signature-space"></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td>(Ka.Div Stock)</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
