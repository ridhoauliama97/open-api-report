<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
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
    
        /* standardized table borders */
        .category-table, .report-table, .summary-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            line-height: inherit;
        }
.category-table th, .category-table td, .report-table th, .report-table td, .summary-table th, .summary-table td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }

        .category-table th { font-weight: bold; background-color: #eef2f8; }

        .report-table th { font-weight: bold; background-color: #eef2f8; }

        .summary-table th { font-weight: bold; background-color: #eef2f8; }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $categories = is_array($data['categories'] ?? null) ? $data['categories'] : [];
        $summary = is_array($data['summary'] ?? null)
            ? $data['summary']
            : ['total_rows' => 0, 'total_categories' => 0, 'total_spk' => 0, 'grand_total' => 0];
        $tanggalText = \Carbon\Carbon::parse($tanggalAkhir)->locale('id')->translatedFormat('d-M-y');

        $categoryLabels = [
            'ST' => 'ST',
            'BJADI' => 'Barang Jadi',
            'CCAKHIR' => 'CC Akhir',
            'FJ' => 'Finger Joint',
            'LMT' => 'Laminating',
            'S4S' => 'S4S',
            'SAND' => 'Sanding',
            'MLD' => 'Moulding',
        ];

        $fmtNumber = static fn($value): string => $value === null
            ? ''
            : rtrim(rtrim(number_format((float) $value, 2, '.', ','), '0'), '.');
        $fmtTotal = static fn($value): string => $value === null ? '' : number_format((float) $value, 4, '.', ',');
    @endphp

    <h1 class="report-title">Laporan Stock Hidup Per No SPK (Discrepancy)</h1>
    <div class="report-subtitle">Per Tanggal : {{ $tanggalText }}</div>

    @forelse ($categories as $category)
        @php
            $displayCategory =
                $categoryLabels[(string) ($category['name'] ?? '')] ?? (string) ($category['name'] ?? '-');
            $spks = is_array($category['spks'] ?? null) ? $category['spks'] : [];
            $categoryTotal = (float) ($category['total'] ?? 0);
            $rowNo = 1;
        @endphp

        <div class="section-title">Kategori : {{ $displayCategory }}</div>
        <table class="report-table category-table">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Jenis</th>
                    <th style="width: 86px;">No SPK</th>
                    <th style="width: 110px;">Buyer</th>
                    <th style="width: 50px;">Umur</th>
                    <th style="width: 50px;">Tebal</th>
                    <th style="width: 56px;">Lebar</th>
                    <th style="width: 60px;">Panjang</th>
                    <th style="width: 52px;">Pcs</th>
                    <th style="width: 90px;">Total (m3)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($spks as $spk)
                    @php
                        $rows = is_array($spk['rows'] ?? null) ? $spk['rows'] : [];
                        $spkLabel = (string) (($spk['no_spk'] ?? '-') !== '-' ? $spk['no_spk'] ?? '-' : 'Tanpa No SPK');
                        $buyer = trim((string) ($spk['buyer'] ?? ''));
                    @endphp

                    @foreach ($rows as $row)
                        <tr class="{{ $rowNo % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            <td class="center">{{ $rowNo }}</td>
                            <td>{{ (string) ($row['Jenis'] ?? '') }}</td>
                            <td class="center">{{ $spkLabel }}</td>
                            <td class="center">{{ $buyer }}</td>
                            <td class="number">{{ $fmtNumber($row['Umur'] ?? null) }}</td>
                            <td class="number">{{ $fmtNumber($row['Tebal'] ?? null) }}</td>
                            <td class="number">{{ $fmtNumber($row['Lebar'] ?? null) }}</td>
                            <td class="number">{{ $fmtNumber($row['Panjang'] ?? null) }}</td>
                            <td class="number">{{ $fmtNumber($row['Pcs'] ?? null) }}</td>
                            <td class="number">{{ $fmtTotal($row['Total'] ?? null) }}</td>
                        </tr>
                        @php $rowNo++; @endphp
                    @endforeach
                @empty
                    <tr>
                        <td colspan="10" class="empty-state">Tidak ada data untuk kategori ini.</td>
                    </tr>
                @endforelse

                <tr class="total-row">
                    <td colspan="9" class="center">Total Kategori {{ $displayCategory }} : </td>
                    <td class="number">{{ $fmtTotal($categoryTotal) }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="empty-state">Tidak ada data untuk tanggal ini.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @if ($categories !== [])
        <div class="summary-title">Rangkuman</div>
        <table class="summary-table">
            <tbody>
                <tr>
                    <td>Total No SPK</td>
                    <td class="number">{{ number_format((float) ($summary['total_spk'] ?? 0), 0, '.', ',') }}</td>
                </tr>
                <tr>
                    <td>Total Kategori</td>
                    <td class="number">{{ number_format((float) ($summary['total_categories'] ?? 0), 0, '.', ',') }}
                    </td>
                </tr>
                @foreach ($categories as $category)
                    @php
                        $displayCategory =
                            $categoryLabels[(string) ($category['name'] ?? '')] ?? (string) ($category['name'] ?? '-');
                    @endphp
                    <tr>
                        <td>Total {{ $displayCategory }}</td>
                        <td class="number">{{ $fmtTotal($category['total'] ?? null) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>Grand Total (m3)</td>
                    <td class="number">{{ $fmtTotal($summary['grand_total'] ?? null) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    </body>

</html>
