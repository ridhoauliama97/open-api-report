<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    @include('reports.partials.pdf-reference-style')
    <style>
        .data-table th, .data-table td {
            border: 1px solid #000;
        }
        .handover-summary {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            line-height: inherit;
        }
        .handover-summary th, .handover-summary td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }
        .handover-summary th {
            font-weight: bold;
            background-color: #eef2f8;
        }
        .meta-table, .signature-table {
            border-collapse: collapse;
        }
        .meta-table td, .signature-table td {
            padding: 1px 2px;
            vertical-align: top;
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
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');

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
                return \Carbon\Carbon::parse($t)->locale('id')->isoFormat('DD-MMM-YY');
            } catch (\Throwable $e) {
                return $t;
            }
        };
    @endphp

    <h1 class="report-title" style="margin-bottom: 20px;">Laporan Serah Terima ST (Kamar KD)</h1>

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

    </body>

</html>
