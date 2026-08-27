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
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.2;
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

        .section-title {
            margin: 14px 0 6px 0;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: calc(100% - 2px);
            line-height: inherit;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px 2px;
        }

        td.center {
            text-align: center;
        }

        td.label {
            white-space: nowrap;
        }

        td.number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .totals-row td {
            font-weight: bold;
        }

        .headers-row th {
            font-weight: bold;
        }

        .meta-table {
            width: 100%;
            table-layout: fixed;
            margin-bottom: 8px;
        }

        .meta-table td {
            border: 0;
            padding: 0 4px 4px 0;
            vertical-align: top;
        }

        .meta-inner {
            margin-bottom: 0;
        }

        .meta-inner td {
            border: 0;
        }

        .meta-label {
            width: 86px;
            white-space: nowrap;
        }

        .meta-sep {
            width: 14px;
            text-align: center;
        }

        .size-table {
            width: 220px;
            margin-top: 4px;
            margin-bottom: 8px;
        }

        .size-table th,
        .size-table td {
            padding: 2px 6px;
        }

        .request-row {
            margin: 8px 0 18px;
            font-size: 11px;
        }

        .request-value {
            margin-left: 8px;
            font-size: 18px;
            font-weight: bold;
            line-height: 1;
        }

        .detail-layout {
            table-layout: fixed;
            margin-bottom: 0;
        }

        .detail-layout td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .detail-layout.single-detail-layout {
            width: 48%;
        }

        .detail-gap {
            width: 10%;
        }

        .racip-table {
            table-layout: fixed;
            margin-bottom: 0;
        }

        .racip-table th {
            font-size: 8px;
            line-height: 1.05;
        }

        .racip-table th,
        .racip-table td {
            padding: 1px 4px;
            font-size: 7.5px;
            line-height: 1.05;
        }

        .racip-table tbody tr.data-row td {
            height: 11px;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $header = is_array($data['header'] ?? null) ? $data['header'] : [];
        $headerRows = is_array($data['header_rows'] ?? null) ? $data['header_rows'] : [];
        $detailRows = is_array($data['detail_rows'] ?? null) ? $data['detail_rows'] : [];
        $maxRowsPerDetailTable = max(1, (int) config('reports.spk_sawmill.max_detail_rows_per_table', 52));
        $shouldRenderRightTable = count($detailRows) > $maxRowsPerDetailTable;
        $leftRows = array_slice($detailRows, 0, $maxRowsPerDetailTable);
        $rightRows = $shouldRenderRightTable
            ? array_slice($detailRows, $maxRowsPerDetailTable, $maxRowsPerDetailTable)
            : [];

        $formatDate = static function ($value): string {
            if ($value === null || trim((string) $value) === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($value)->format('d-M-y');
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };
        $formatDetailDate = static function ($value): string {
            if ($value === null || trim((string) $value) === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($value)->format('d-M-y');
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };
        $formatDimension = static function ($value): string {
            $text = number_format((float) $value, 0, ',', '.');

            return $text;
        };
        $formatTon = static function ($value, int $decimals = 2): string {
            $text = number_format((float) $value, $decimals, ',', '.');

            return $decimals === 0 ? $text : $text;
        };
        $formatRequestTon = static function ($value): string {
            $number = (float) $value;
            $text = number_format($number, floor($number) === $number ? 0 : 2, ',', '.');

            return $text . ' TON';
        };
    @endphp

    <h1 class="report-title">Laporan SPK Sawmill</h1>
    <p class="report-subtitle"></p>

    <table class="meta-table">
        <tr>
            <td style="width: 47%;">
                <table class="meta-inner">
                    <tr>
                        <td class="meta-label">Jenis Kayu</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $header['jenis_kayu'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tanggal</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $formatDate($header['tanggal'] ?? null) }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 53%;">
                <table class="meta-inner">
                    <tr>
                        <td class="meta-label">No SPK</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $header['no_spk'] ?? ($noSpk ?? '') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Produk</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $header['produk'] ?? '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="report-table size-table">
        <thead>
            <tr class="headers-row">
                <th style="width: 50%;">Tebal</th>
                <th style="width: 50%;">Lebar</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($headerRows as $row)
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }} {{ $loop->last ? 'row-last' : '' }}">
                    <td class="number data-cell">{{ $formatDimension($row['Tebal'] ?? 0) }}</td>
                    <td class="number data-cell">{{ $formatDimension($row['Lebar'] ?? 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="request-row">
        Permintaan Racip :
        <span class="request-value">{{ $formatRequestTon($header['permintaan_racip'] ?? 0) }}</span>
    </div>

    <table class="detail-layout {{ $shouldRenderRightTable ? '' : 'single-detail-layout' }}">
        <tr>
            <td style="width: {{ $shouldRenderRightTable ? '45%' : '100%' }};">
                <table class="report-table racip-table">
                    <thead>
                        <tr class="headers-row">
                            <th style="width: 42%;">Tanggal</th>
                            <th style="width: 29%;">Racip</th>
                            <th style="width: 29%;">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leftRows as $row)
                            <tr
                                class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }} {{ $loop->last ? 'row-last' : '' }}">
                                <td class="data-cell">{{ $formatDetailDate($row['TglSawmill'] ?? null) }}</td>
                                <td class="number data-cell">{{ $formatTon($row['Ton'] ?? 0) }}</td>
                                <td class="number data-cell">{{ $formatTon($row['SaldoTerakhir'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr class="data-row row-odd row-last">
                                <td colspan="3" class="center data-cell">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            @if ($shouldRenderRightTable)
                <td class="detail-gap"></td>
                <td style="width: 45%;">
                    <table class="report-table racip-table">
                        <thead>
                            <tr class="headers-row">
                                <th style="width: 42%;">Tanggal</th>
                                <th style="width: 29%;">Racip</th>
                                <th style="width: 29%;">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rightRows as $row)
                                <tr
                                    class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }} {{ $loop->last ? 'row-last' : '' }}">
                                    <td class="data-cell">{{ $formatDetailDate($row['TglSawmill'] ?? null) }}</td>
                                    <td class="number data-cell">{{ $formatTon($row['Ton'] ?? 0) }}</td>
                                    <td class="number data-cell">{{ $formatTon($row['SaldoTerakhir'] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            @endif
        </tr>
    </table>
</body>

</html>
