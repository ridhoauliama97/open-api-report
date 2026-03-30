<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    @include('reports.partials.pdf-reference-style', [
        'pageMargin' => '18mm 10mm 18mm 10mm',
        'subtitleMargin' => '2px 0 14px 0',
        'tableMarginBottom' => '6px',
    ])
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $categories = is_array($data['categories'] ?? null) ? $data['categories'] : [];
        $summary = is_array($data['summary'] ?? null)
            ? $data['summary']
            : ['total_rows' => 0, 'total_categories' => 0, 'total_spk' => 0, 'grand_total' => 0];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $fmtDim = static function (?float $v): string {
            if ($v === null) {
                return '';
            }
            $text = number_format($v, 2, '.', ',');
            return rtrim(rtrim($text, '0'), '.');
        };

        $fmtTotal = static function (float $v): string {
            return $v > 0 ? number_format($v, 4, '.', ',') : '';
        };

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
    @endphp

    <h1 class="report-title">Laporan Stock Hidup Per NoSPK</h1>
    <p class="report-subtitle">Per Tanggal : {{ $tanggalText }}</p>

    @forelse ($categories as $category)
        @php
            $categoryName = (string) ($category['name'] ?? '');
            $displayCategory = $categoryLabels[$categoryName] ?? $categoryName;
            $spks = is_array($category['spks'] ?? null) ? $category['spks'] : [];
            $categoryTotal = (float) ($category['total'] ?? 0);
        @endphp

        <div class="section-title">{{ $displayCategory }}</div>

        @foreach ($spks as $spkGroup)
            @php
                $rows = is_array($spkGroup['rows'] ?? null) ? $spkGroup['rows'] : [];
                $noSpk = (string) ($spkGroup['no_spk'] ?? '-');
                $noContract = trim((string) ($spkGroup['no_contract'] ?? ''));
                $tujuan = trim((string) ($spkGroup['tujuan'] ?? ''));
                $buyer = trim((string) ($spkGroup['buyer'] ?? ''));
                $spkTotal = (float) ($spkGroup['total'] ?? 0);
            @endphp

            <div style="margin: 4px 0 2px 12px; font-weight: bold;">NoSPK : {{ $noSpk }}</div>
            @if ($noContract !== '' || $tujuan !== '' || $buyer !== '')
                <div style="margin: 0 0 4px 12px; font-size: 10px;">
                    @if ($noContract !== '')
                        <span style="margin-right: 12px;">No Contract : {{ $noContract }}</span>
                    @endif
                    @if ($tujuan !== '')
                        <span style="margin-right: 12px;">Tujuan : {{ $tujuan }}</span>
                    @endif
                    @if ($buyer !== '')
                        <span>Buyer : {{ $buyer }}</span>
                    @endif
                </div>
            @endif

            <table class="report-table" style="width: 92%; margin-left: 12px;">
                <colgroup>
                    <col style="width: 34px;">
                    <col>
                    <col style="width: 56px;">
                    <col style="width: 56px;">
                    <col style="width: 64px;">
                    <col style="width: 88px;">
                </colgroup>
                <thead>
                    <tr class="headers-row">
                        <th>No</th>
                        <th>Jenis</th>
                        <th>Tebal</th>
                        <th>Lebar</th>
                        <th>Panjang</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="table-end-line">
                        <td colspan="6"></td>
                    </tr>
                </tfoot>
                <tbody>
                    @forelse ($rows as $idx => $row)
                        <tr class="data-row {{ ($idx + 1) % 2 === 1 ? 'row-odd' : 'row-even' }} {{ $loop->last ? 'row-last' : '' }}">
                            <td class="center data-cell">{{ $idx + 1 }}</td>
                            <td class="data-cell">{{ (string) ($row['Jenis'] ?? '') }}</td>
                            <td class="number data-cell">{{ $fmtDim($row['Tebal'] ?? null) }}</td>
                            <td class="number data-cell">{{ $fmtDim($row['Lebar'] ?? null) }}</td>
                            <td class="number data-cell">{{ $fmtDim($row['Panjang'] ?? null) }}</td>
                            <td class="number data-cell">{{ $fmtTotal((float) ($row['Total'] ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="center">Tidak ada data.</td>
                        </tr>
                    @endforelse
                    <tr class="totals-row">
                        <td colspan="5" class="center" style="text-align: left;">Total NoSPK {{ $noSpk }}</td>
                        <td class="number">{{ $fmtTotal($spkTotal) }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach

        <table class="report-table" style="width: 92%; margin-left: 12px; margin-top: 4px;">
            <tbody>
                <tr class="totals-row">
                    <td class="center" style="text-align: left;">Total Kategori {{ $displayCategory }}</td>
                    <td class="number" style="width: 88px;">{{ $fmtTotal($categoryTotal) }}</td>
                </tr>
            </tbody>
        </table>

        @if (!$loop->last)
            <div style="border-top: 1px solid #000; margin: 8px 0;"></div>
        @endif
    @empty
        <div class="center">Tidak ada data.</div>
    @endforelse

    @if ($summary['total_rows'] > 0)
        <table class="report-table" style="width: 50%; margin-top: 12px;">
            <tbody>
                <tr class="totals-row">
                    <td>Total Baris</td>
                    <td class="number">{{ number_format((float) $summary['total_rows'], 0, '.', ',') }}</td>
                </tr>
                <tr class="totals-row">
                    <td>Total Kategori</td>
                    <td class="number">{{ number_format((float) $summary['total_categories'], 0, '.', ',') }}</td>
                </tr>
                <tr class="totals-row">
                    <td>Total NoSPK</td>
                    <td class="number">{{ number_format((float) $summary['total_spk'], 0, '.', ',') }}</td>
                </tr>
                <tr class="totals-row">
                    <td>Grand Total</td>
                    <td class="number">{{ $fmtTotal((float) ($summary['grand_total'] ?? 0)) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
