<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    <style>
        body {
            font-family: "Noto Serif", serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .report-companyTitle {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 0 0 4px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 0;
        }
        .report-subtitle {
            font-size: 12px;
            color: #636466;
            text-align: center;
            margin: 2px 0 20px;
        }
        .data-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #000;            padding: 1px 2px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .data-table th {
            background-color: #eef2f8;
            font-weight: bold;
        }
        .section-header td {
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .sub-section-header td {
            font-weight: bold;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .item-row td {
            padding-left: 4px;
        }
        .row-odd td {
            background-color: #c9d1df;
        }
        .row-even td {
            background-color: #eef2f8;
        }
        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .empty-row td {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background-color: #c9d1df;
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
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse((string) ($startDate ?? ''))->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) ($endDate ?? ''))->locale('id')->translatedFormat('d-M-y');

        $fmtDate = static function (string $raw): string {
            $t = trim($raw);
            if ($t === '') {
                return '';
            }
            try {
                return \Carbon\Carbon::parse($t)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $e) {
                return $t;
            }
        };

        $fmtTon = static function ($v): string {
            return number_format((float) ($v ?? 0), 4, '.', '');
        };

        $fmtPanjang = static function ($v): string {
            return number_format((float) ($v ?? 0), 2, ',', '');
        };

        $fmtIntNoSep = static function ($v): string {
            return (string) ((int) ($v ?? 0));
        };

        $fmtDimInt = static function ($v): string {
            $n = (float) ($v ?? 0);
            return (string) ((int) round($n));
        };
    @endphp

    <h1 class="report-title">Laporan Rekap ST Penjualan</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @forelse ($groups as $group)
        @php
            $buyer = (string) ($group['pembeli'] ?? '-');
            $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            $totals = is_array($group['totals'] ?? null) ? $group['totals'] : [];
        @endphp

        <div class="buyer-title">Pembeli&nbsp;&nbsp;: {{ $buyer }}</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>NoST</th>
                    <th>Tanggal (ST)</th>
                    <th>Jenis Kayu</th>
                    <th>Tebal</th>
                    <th>Lebar</th>
                    <th>UOM Tbl Lebar</th>
                    <th>Panjang</th>
                    <th>UOMPanjang</th>
                    <th>JmlhBtg</th>
                    <th>Ton</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $r)
                    @php $rowIndex = ($loop->index ?? 0) + 1; @endphp
                    <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center data-cell">{{ (string) ($r['NoST'] ?? '') }}</td>
                        <td class="center data-cell">
                            {{ $fmtDate((string) ($r['TanggalSTRaw'] ?? ($r['Tanggal (ST)'] ?? ''))) }}
                        </td>
                        <td class="data-cell">{{ (string) ($r['Jenis Kayu'] ?? '') }}</td>
                        <td class="center data-cell">{{ $fmtDimInt($r['Tebal'] ?? 0) }}</td>
                        <td class="center data-cell">{{ $fmtDimInt($r['Lebar'] ?? 0) }}</td>
                        <td class="center data-cell">{{ (string) ($r['UOMTblLebar'] ?? '') }}</td>
                        <td class="number data-cell">{{ $fmtPanjang($r['Panjang'] ?? 0) }}</td>
                        <td class="center data-cell">{{ (string) ($r['UOMPanjang'] ?? '') }}</td>
                        <td class="number data-cell">{{ $fmtIntNoSep($r['JmlhBtg'] ?? 0) }}</td>
                        <td class="number data-cell">{{ $fmtTon($r['Ton'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr class="empty-row">
                        <td colspan="10" class="center">Tidak ada data</td>
                    </tr>
                @endforelse
                @if ($rows !== [])
                    <tr class="totals-row">
                        <td colspan="8" class="number">Jmlh Batang / {{ $buyer }} :</td>
                        <td class="number">{{ $fmtIntNoSep($totals['jmlh_btg'] ?? 0) }}</td>
                        <td class="number">{{ $fmtTon($totals['ton'] ?? 0) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @empty
        <table class="data-table">
            <thead>
                <tr>
                    <th>NoST</th>
                    <th>Tanggal (ST)</th>
                    <th>Jenis Kayu</th>
                    <th>Tebal</th>
                    <th>Lebar</th>
                    <th>UOM Tbl Lebar</th>
                    <th>Panjang</th>
                    <th>UOMPanjang</th>
                    <th>JmlhBtg</th>
                    <th>Ton</th>
                </tr>
            </thead>
            <tbody>
                <tr class="empty-row">
                    <td colspan="10" class="center">Tidak ada data</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    </body>

</html>