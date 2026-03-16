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
        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $fmtDim = static function (?float $v): string {
            if ($v === null) {
                return '';
            }
            // Render 20.00 -> 20, 20.50 -> 20.5, 20.55 -> 20.55
            $text = number_format($v, 2, '.', ',');
            $text = rtrim(rtrim($text, '0'), '.');

            return $text;
        };

        $fmtTon = static function (float $v, int $dec = 4): string {
            return $v > 0 ? number_format($v, $dec, '.', ',') : '';
        };
    @endphp

    <h1 class="report-title">Laporan Saldo ST Hidup Per-Jenis Per-Tebal (Per-Group Jenis Kayu)</h1>
    <p class="report-subtitle">&nbsp;</p>

    @forelse ($groups as $gidx => $group)
        @php
            $groupName = trim((string) ($group['name'] ?? ''));
            $products = is_array($group['products'] ?? null) ? $group['products'] : [];
            $groupTotals = is_array($group['totals'] ?? null)
                ? $group['totals']
                : ['basah' => 0, 'kd' => 0, 'kering' => 0, 'total' => 0];
            $no = 0;
        @endphp

        <div class="section-title">{{ $gidx + 1 }} {{ $groupName }}</div>

        @foreach ($products as $pidx => $product)
            @php
                $productName = trim((string) ($product['name'] ?? ''));
                $rows = is_array($product['rows'] ?? null) ? $product['rows'] : [];
                $totals = is_array($product['totals'] ?? null)
                    ? $product['totals']
                    : ['basah' => 0, 'kd' => 0, 'kering' => 0, 'total' => 0];
            @endphp

            <div style="margin: 6px 0 2px 12px; font-weight: bold;">Produk : {{ $productName }}</div>

            <table class="report-table" style="width: 92%; margin-left: 12px;">
                <thead>
                    <tr class="headers-row">
                        <th style="width: 34px;">No</th>
                        <th style="width: 56px;">Tebal</th>
                        <th style="width: 56px;">Lebar</th>
                        <th style="width: 46px;">UOM</th>
                        <th>Basah (Ton)</th>
                        <th>KD (Ton)</th>
                        <th>Kering (Ton)</th>
                        <th>Total (Ton)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $ridx => $row)
                        @php
                            $no++;
                            $basah = (float) ($row['BasahTon'] ?? 0);
                            $kd = (float) ($row['KDTon'] ?? 0);
                            $kering = (float) ($row['KeringTon'] ?? 0);
                            $total = (float) ($row['TotalTon'] ?? 0);
                        @endphp
                        <tr
                            class="data-row {{ ($ridx + 1) % 2 === 1 ? 'row-odd' : 'row-even' }} {{ $loop->last ? 'row-last' : '' }}">
                            <td class="center data-cell">{{ $no }}</td>
                            <td class="number data-cell">{{ $fmtDim($row['Tebal'] ?? null) }}</td>
                            <td class="number data-cell">{{ $fmtDim($row['Lebar'] ?? null) }}</td>
                            <td class="center data-cell">{{ (string) ($row['UOM'] ?? '') }}</td>
                            <td class="number data-cell">{{ $fmtTon($basah) }}</td>
                            <td class="number data-cell">{{ $fmtTon($kd) }}</td>
                            <td class="number data-cell">{{ $fmtTon($kering) }}</td>
                            <td class="number data-cell">{{ $fmtTon($total) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="center">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <td class="center" colspan="4">Jumlah :</td>
                        <td class="number">{{ number_format((float) ($totals['basah'] ?? 0), 4, '.', ',') }}</td>
                        <td class="number">{{ number_format((float) ($totals['kd'] ?? 0), 4, '.', ',') }}</td>
                        <td class="number">{{ number_format((float) ($totals['kering'] ?? 0), 4, '.', ',') }}</td>
                        <td class="number">{{ number_format((float) ($totals['total'] ?? 0), 4, '.', ',') }}</td>
                    </tr>
                </tfoot>
            </table>
        @endforeach

        <table class="report-table" style="width: 92%; margin-left: 12px; margin-top: 2px;">
            <tbody>
                <tr class="totals-row">
                    <td class="center" colspan="4" style="text-align: left;">
                        Jumlah (Ton) Per-Jenis {{ $groupName }} :
                    </td>
                    <td class="number">{{ number_format((float) ($groupTotals['basah'] ?? 0), 4, '.', ',') }}</td>
                    <td class="number">{{ number_format((float) ($groupTotals['kd'] ?? 0), 4, '.', ',') }}</td>
                    <td class="number">{{ number_format((float) ($groupTotals['kering'] ?? 0), 4, '.', ',') }}</td>
                    <td class="number">{{ number_format((float) ($groupTotals['total'] ?? 0), 4, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>
        @empty
            <div class="center">Tidak ada data.</div>
        @endforelse

        @include('reports.partials.pdf-footer-table')
    </body>

    </html>
