<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    @include('reports.partials.pdf-reference-style')
    <style>
        .data-table th, .data-table td {
            border: 1px solid #000;
        }
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
        .category-table th {
            font-weight: bold;
            background-color: #eef2f8;
        }
        .report-table th {
            font-weight: bold;
            background-color: #eef2f8;
        }
        .summary-table th {
            font-weight: bold;
            background-color: #eef2f8;
        }
    </style>
    
    
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $categories = is_array($data['categories'] ?? null) ? $data['categories'] : [];
        $summary = is_array($data['summary'] ?? null)
            ? $data['summary']
            : ['total_rows' => 0, 'total_categories' => 0, 'total_spk' => 0, 'grand_total' => 0];
        $tanggalText = \Carbon\Carbon::parse($tanggalAkhir)->locale('id')->isoFormat('DD-MMM-YY');

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

    <h1 class="report-title">Laporan Stock Hidup Per No SPK</h1>
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

                    @foreach ($rows as $index => $row)
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
        <div class="summary-title">Rangkuman :</div>
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
