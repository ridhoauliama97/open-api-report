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
            $text = number_format($v, 2, '.', ',');
            return rtrim(rtrim($text, '0'), '.');
        };

        $fmtTon = static function (float $v, int $dec = 4): string {
            return $v > 0 ? number_format($v, $dec, '.', ',') : '';
        };

        // Build grand summary: Jenis -> Produk totals, plus total per Jenis.
        $grandSummary = [];
        foreach ($groups as $g) {
            $jenisName = trim((string) ($g['name'] ?? ''));
            $jenisName = $jenisName !== '' ? $jenisName : 'Tanpa Jenis';
            $products = is_array($g['products'] ?? null) ? $g['products'] : [];

            if (!isset($grandSummary[$jenisName])) {
                $grandSummary[$jenisName] = [
                    'name' => $jenisName,
                    'products' => [],
                    'totals' => ['basah' => 0.0, 'kd' => 0.0, 'kering' => 0.0, 'total' => 0.0],
                ];
            }

            foreach ($products as $p) {
                $productName = trim((string) ($p['name'] ?? ''));
                $productName = $productName !== '' ? $productName : 'Tanpa Produk';
                $spks = is_array($p['spks'] ?? null) ? $p['spks'] : [];

                if (!isset($grandSummary[$jenisName]['products'][$productName])) {
                    $grandSummary[$jenisName]['products'][$productName] = [
                        'name' => $productName,
                        'totals' => ['basah' => 0.0, 'kd' => 0.0, 'kering' => 0.0, 'total' => 0.0],
                    ];
                }

                foreach ($spks as $s) {
                    $rows = is_array($s['rows'] ?? null) ? $s['rows'] : [];
                    foreach ($rows as $r) {
                        $b = (float) ($r['BasahTon'] ?? 0);
                        $k = (float) ($r['KDTon'] ?? 0);
                        $kr = (float) ($r['KeringTon'] ?? 0);
                        $t = (float) ($r['TotalTon'] ?? 0);

                        $grandSummary[$jenisName]['products'][$productName]['totals']['basah'] += $b;
                        $grandSummary[$jenisName]['products'][$productName]['totals']['kd'] += $k;
                        $grandSummary[$jenisName]['products'][$productName]['totals']['kering'] += $kr;
                        $grandSummary[$jenisName]['products'][$productName]['totals']['total'] += $t;

                        $grandSummary[$jenisName]['totals']['basah'] += $b;
                        $grandSummary[$jenisName]['totals']['kd'] += $k;
                        $grandSummary[$jenisName]['totals']['kering'] += $kr;
                        $grandSummary[$jenisName]['totals']['total'] += $t;
                    }
                }
            }
        }

        ksort($grandSummary, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($grandSummary as &$gs) {
            ksort($gs['products'], SORT_NATURAL | SORT_FLAG_CASE);
        }
        unset($gs);
    @endphp

    <h1 class="report-title">Laporan ST Hidup per SPK, per Jenis, per Tebal, per Group Jenis Kayu</h1>
    <p class="report-subtitle">&nbsp;</p>

    @forelse ($groups as $jenisGroup)
        @php
            $jenisName = trim((string) ($jenisGroup['name'] ?? ''));
            $products = is_array($jenisGroup['products'] ?? null) ? $jenisGroup['products'] : [];
        @endphp

        <div class="section-title">{{ $jenisName }}</div>

        @foreach ($products as $product)
            @php
                $productName = trim((string) ($product['name'] ?? ''));
                $spks = is_array($product['spks'] ?? null) ? $product['spks'] : [];

                $productSumBasah = 0.0;
                $productSumKd = 0.0;
                $productSumKering = 0.0;
                $productSumTotal = 0.0;
                foreach ($spks as $spkForSum) {
                    $rowsForSum = is_array($spkForSum['rows'] ?? null) ? $spkForSum['rows'] : [];
                    foreach ($rowsForSum as $r) {
                        $productSumBasah += (float) ($r['BasahTon'] ?? 0);
                        $productSumKd += (float) ($r['KDTon'] ?? 0);
                        $productSumKering += (float) ($r['KeringTon'] ?? 0);
                        $productSumTotal += (float) ($r['TotalTon'] ?? 0);
                    }
                }
            @endphp

            <div style="margin: 4px 0 2px 12px; font-weight: bold;">Produk : {{ $productName }}</div>

            @foreach ($spks as $spkGroup)
                @php
                    $noSpk = trim((string) ($spkGroup['no_spk'] ?? ''));
                    $rows = is_array($spkGroup['rows'] ?? null) ? $spkGroup['rows'] : [];

                    $sumBasah = 0.0;
                    $sumKd = 0.0;
                    $sumKering = 0.0;
                    $sumTotal = 0.0;
                    foreach ($rows as $r) {
                        $sumBasah += (float) ($r['BasahTon'] ?? 0);
                        $sumKd += (float) ($r['KDTon'] ?? 0);
                        $sumKering += (float) ($r['KeringTon'] ?? 0);
                        $sumTotal += (float) ($r['TotalTon'] ?? 0);
                    }
                @endphp

                <div style="margin: 0 0 4px 12px; font-weight: bold;">NoSPK : {{ $noSpk }}</div>

                <table class="report-table" style="width: 92%; margin-left: 12px;">
                    <colgroup>
                        <col style="width: 34px;">
                        <col style="width: 56px;">
                        <col style="width: 56px;">
                        <col style="width: 46px;">
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>
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
                        @forelse ($rows as $idx => $row)
                            @php
                                $basah = (float) ($row['BasahTon'] ?? 0);
                                $kd = (float) ($row['KDTon'] ?? 0);
                                $kering = (float) ($row['KeringTon'] ?? 0);
                                $total = (float) ($row['TotalTon'] ?? 0);
                            @endphp
                            <tr class="data-row {{ ($idx + 1) % 2 === 1 ? 'row-odd' : 'row-even' }} {{ $loop->last ? 'row-last' : '' }}">
                                <td class="center data-cell">{{ $idx + 1 }}</td>
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
                            <td class="center" colspan="4">Total</td>
                            <td class="number">{{ $fmtTon($sumBasah) }}</td>
                            <td class="number">{{ $fmtTon($sumKd) }}</td>
                            <td class="number">{{ $fmtTon($sumKering) }}</td>
                            <td class="number">{{ $fmtTon($sumTotal) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endforeach

            <table class="report-table" style="width: 92%; margin-left: 12px; margin-top: 4px;">
                <colgroup>
                    <col style="width: 34px;">
                    <col style="width: 56px;">
                    <col style="width: 56px;">
                    <col style="width: 46px;">
                    <col style="width: 92px;">
                    <col style="width: 92px;">
                    <col style="width: 92px;">
                    <col style="width: 92px;">
                </colgroup>
                <tbody>
                    <tr class="totals-row">
                        <td class="center" colspan="4" style="text-align: left;">Total Produk {{ $productName }}</td>
                        <td class="number">{{ number_format((float) $productSumBasah, 4, '.', ',') }}</td>
                        <td class="number">{{ number_format((float) $productSumKd, 4, '.', ',') }}</td>
                        <td class="number">{{ number_format((float) $productSumKering, 4, '.', ',') }}</td>
                        <td class="number">{{ number_format((float) $productSumTotal, 4, '.', ',') }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach

        @if (!$loop->last)
            <div style="border-top: 1px solid #000; margin: 8px 0;"></div>
        @endif
    @empty
        <div class="center">Tidak ada data.</div>
    @endforelse

    @if (!empty($grandSummary))
        <div style="page-break-before: always;"></div>
        <div class="section-title" style="text-align: center;">Grand Total</div>

        @foreach (array_values($grandSummary) as $idx => $gs)
            @php
                $jenisName = (string) ($gs['name'] ?? '');
                $products = is_array($gs['products'] ?? null) ? array_values($gs['products']) : [];
                $tot = is_array($gs['totals'] ?? null) ? $gs['totals'] : ['basah' => 0, 'kd' => 0, 'kering' => 0, 'total' => 0];
            @endphp

            <div class="section-title" style="margin-top: 10px;">{{ $idx + 1 }} {{ $jenisName }}</div>
            <table class="report-table" style="width: 92%; margin-left: 12px;">
                <colgroup>
                    <col>
                    <col style="width: 92px;">
                    <col style="width: 92px;">
                    <col style="width: 92px;">
                    <col style="width: 92px;">
                </colgroup>
                <thead>
                    <tr class="headers-row">
                        <th>Produk</th>
                        <th>Basah (Ton)</th>
                        <th>KD (Ton)</th>
                        <th>Kering (Ton)</th>
                        <th>Total (Ton)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $p)
                        @php
                            $pt = is_array($p['totals'] ?? null) ? $p['totals'] : ['basah' => 0, 'kd' => 0, 'kering' => 0, 'total' => 0];
                        @endphp
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }} {{ $loop->last ? 'row-last' : '' }}">
                            <td class="data-cell">{{ (string) ($p['name'] ?? '') }}</td>
                            <td class="number data-cell">{{ $fmtTon((float) ($pt['basah'] ?? 0)) }}</td>
                            <td class="number data-cell">{{ $fmtTon((float) ($pt['kd'] ?? 0)) }}</td>
                            <td class="number data-cell">{{ $fmtTon((float) ($pt['kering'] ?? 0)) }}</td>
                            <td class="number data-cell">{{ $fmtTon((float) ($pt['total'] ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="center" colspan="5">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <td class="center">Total</td>
                        <td class="number">{{ $fmtTon((float) ($tot['basah'] ?? 0)) }}</td>
                        <td class="number">{{ $fmtTon((float) ($tot['kd'] ?? 0)) }}</td>
                        <td class="number">{{ $fmtTon((float) ($tot['kering'] ?? 0)) }}</td>
                        <td class="number">{{ $fmtTon((float) ($tot['total'] ?? 0)) }}</td>
                    </tr>
                </tfoot>
            </table>
        @endforeach
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
