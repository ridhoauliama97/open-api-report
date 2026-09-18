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
    </style>
    
    
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');

        $fmtInt = static fn(?int $v): string => $v === null ? '' : number_format($v, 0, '.', ',');
        $fmtFloat = static fn(?float $v, int $dec = 4): string => $v === null ? '' : number_format($v, $dec, '.', '');

        $groupedByJenis = [];
        foreach ($rows as $r) {
            $r = is_array($r) ? $r : (array) $r;
            $jenisKey = (string) ($r['Jenis'] ?? '');
            if ($jenisKey === '') {
                $jenisKey = 'JENIS';
            }
            $groupedByJenis[$jenisKey][] = $r;
        }

        $jenisKeys = array_keys($groupedByJenis);
        sort($jenisKeys, SORT_STRING);

        $alphaIndexToLabel = static function (int $idx): string {
            $base = ord('A') + ($idx % 26);
            return chr($base);
        };

        // Build summary: per Jenis -> per Produk totals (Pcs, Kubik), using the same product label/prefix logic.
        $summaryByJenis = [];
        foreach ($jenisKeys as $i => $jenisKey) {
            $jenisRows = $groupedByJenis[$jenisKey] ?? [];
            $jenisLetter = $alphaIndexToLabel($i);

            $groupedByProduk = [];
            foreach ($jenisRows as $r) {
                $produkKey = (string) ($r['Produk'] ?? '');
                if ($produkKey === '') {
                    $produkKey = '-';
                }
                $groupedByProduk[$produkKey][] = $r;
            }

            $produkKeys = array_keys($groupedByProduk);
            sort($produkKeys, SORT_STRING);

            $produkIndex = 0;
            foreach ($produkKeys as $produkKey) {
                $produkIndex++;
                $produkRows = $groupedByProduk[$produkKey] ?? [];

                $produkPrefix = $jenisLetter . $produkIndex . '.';
                $produkLabel =
                    $produkKey !== '-' && trim($produkKey) !== '' ? $produkPrefix . ' ' . $produkKey : $produkPrefix;

                $pcsSum = 0;
                $kubikSum = 0.0;
                foreach ($produkRows as $pr) {
                    $pcsSum += (int) ($pr['Pcs'] ?? 0);
                    $kubikSum += (float) ($pr['Kubik'] ?? 0.0);
                }

                $summaryByJenis[$jenisKey][] = [
                    'produk_label' => $produkLabel,
                    'pcs' => $pcsSum,
                    'kubik' => $kubikSum,
                ];
            }
        }
    @endphp

    <h1 class="report-title">Laporan Label S4S (Hidup) Per-Produk dan Per-Jenis Kayu</h1>
    <p class="report-subtitle">&nbsp;</p>

    @php $jenisIndex = 0; @endphp
    @foreach ($jenisKeys as $jenisKey)
        @php
            $jenisIndex++;
            $jenisRows = $groupedByJenis[$jenisKey] ?? [];
            $jenisLetter = $alphaIndexToLabel($jenisIndex - 1);
            $jenisLabel = $jenisLetter . '. ' . $jenisKey;

            $groupedByProduk = [];
            foreach ($jenisRows as $r) {
                $produkKey = (string) ($r['Produk'] ?? '');
                if ($produkKey === '') {
                    $produkKey = '-';
                }
                $groupedByProduk[$produkKey][] = $r;
            }

            $produkKeys = array_keys($groupedByProduk);
            sort($produkKeys, SORT_STRING);
        @endphp

        <div class="group-title">{{ $jenisLabel }}</div>

        @php $produkIndex = 0; @endphp
        @foreach ($produkKeys as $produkKey)
            @php
                $produkIndex++;
                $produkRows = $groupedByProduk[$produkKey] ?? [];
                // Reference format: "D2. FJLB 22 & 24" (prefix = Jenis letter + running number).
                $produkPrefix = $jenisLetter . $produkIndex . '.';
                $produkLabel =
                    $produkKey !== '-' && trim($produkKey) !== '' ? $produkPrefix . ' ' . $produkKey : $produkPrefix;

                $groupedByGrade = [];
                foreach ($produkRows as $r) {
                    $gradeKey = (string) ($r['NamaGrade'] ?? '');
                    if ($gradeKey === '') {
                        $gradeKey = '-';
                    }
                    $groupedByGrade[$gradeKey][] = $r;
                }

                $gradeKeys = array_keys($groupedByGrade);
                sort($gradeKeys, SORT_STRING);
            @endphp

            <div class="product-title">{{ $produkLabel }}</div>

            @php $gradeNo = 0; @endphp
            @foreach ($gradeKeys as $gradeKey)
                @php
                    $gradeNo++;
                    $gradeRows = $groupedByGrade[$gradeKey] ?? [];
                    $gradeRowCount = count($gradeRows);
                    $pcsSum = 0;
                    $kubikSum = 0.0;

                    foreach ($gradeRows as $gr) {
                        $pcsSum += (int) ($gr['Pcs'] ?? 0);
                        $kubikSum += (float) ($gr['Kubik'] ?? 0.0);
                    }
                @endphp

                <table class="data-table" style="margin-bottom: 10px;">
                    <thead>
                        <tr>
                            <th style="width: 4%;">No</th>
                            <th style="width: 21%;">Nama Grade</th>
                            <th style="width: 15%;">Tebal (mm)</th>
                            <th style="width: 15%;">Lebar (mm)</th>
                            <th style="width: 15%;">Panjang (ft)</th>
                            <th style="width: 15%;">Jmlh Batang (Pcs)</th>
                            <th style="width: 15%;">Kubik (m3)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rowIndex = 0; @endphp
                        @foreach ($gradeRows as $gr)
                            @php
                                $rowIndex++;
                                $cls = $rowIndex % 2 === 1 ? 'row-odd' : 'row-even';
                            @endphp
                            <tr class="{{ $cls }}">
                                @if ($rowIndex === 1)
                                    <td class="center" rowspan="{{ max(1, $gradeRowCount) }}">{{ $gradeNo }}</td>
                                    <td rowspan="{{ max(1, $gradeRowCount) }}" class="center">{{ $gradeKey }}</td>
                                @endif
                                <td class="center">{{ $fmtFloat($gr['Tebal'] ?? null, 0) }}</td>
                                <td class="center">{{ $fmtFloat($gr['Lebar'] ?? null, 0) }}</td>
                                <td class="center">{{ $fmtFloat($gr['Panjang'] ?? null, 0) }}</td>
                                <td class="number" style="font-weight: bold">
                                    {{ $fmtInt(is_numeric($gr['Pcs'] ?? null) ? (int) $gr['Pcs'] : null) }}
                                </td>
                                <td class="number" style="font-weight: bold">{{ $fmtFloat($gr['Kubik'] ?? null, 4) }}
                                </td>

                            </tr>
                        @endforeach

                        <tr class="totals-row">
                            <td colspan="5" class="center">Jumlah {{ $gradeKey }}</td>
                            <td class="number">{{ $fmtInt($pcsSum) }}</td>
                            <td class="number">{{ $fmtFloat($kubikSum, 4) }}</td>
                        </tr>
                    </tbody>
                </table>
            @endforeach
        @endforeach
    @endforeach

    <div class="group-title" style="margin-top: 14px;">Rangkuman</div>

    @php
        $grandPcs = 0;
        $grandKubik = 0.0;
        foreach ($jenisKeys as $jk) {
            foreach ($summaryByJenis[$jk] ?? [] as $sr) {
                $grandPcs += (int) ($sr['pcs'] ?? 0);
                $grandKubik += (float) ($sr['kubik'] ?? 0.0);
            }
        }
    @endphp

    @php $sumJenisIndex = 0; @endphp
    @foreach ($jenisKeys as $jenisKey)
        @php
            $sumJenisIndex++;
            $jenisLetter = $alphaIndexToLabel($sumJenisIndex - 1);
            $jenisLabel = $jenisLetter . '. ' . $jenisKey;

            $produkSummaryRows = $summaryByJenis[$jenisKey] ?? [];
            $totalPcs = 0;
            $totalKubik = 0.0;
            foreach ($produkSummaryRows as $sr) {
                $totalPcs += (int) ($sr['pcs'] ?? 0);
                $totalKubik += (float) ($sr['kubik'] ?? 0.0);
            }
        @endphp

        <div class="group-title">{{ $jenisLabel }}</div>

        <table class="data-table" style="margin-bottom: 10px;">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th>Produk</th>
                    <th style="width: 15%;">Jmlh Batang</th>
                    <th style="width: 15%;">Kubik</th>
                </tr>
            </thead>
            <tbody>
                @php $srIndex = 0; @endphp
                @foreach ($produkSummaryRows as $sr)
                    @php
                        $srIndex++;
                        $cls = $srIndex % 2 === 1 ? 'row-odd' : 'row-even';
                    @endphp
                    <tr class="{{ $cls }}">
                        <td class="center">{{ $srIndex }}</td>
                        <td>{{ (string) ($sr['produk_label'] ?? '') }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtInt((int) ($sr['pcs'] ?? 0)) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtFloat((float) ($sr['kubik'] ?? 0.0), 4) }}
                        </td>
                    </tr>
                @endforeach

                <tr class="totals-row">
                    <td colspan="2" class="center">Total {{ $jenisKey }}</td>
                    <td class="number">{{ $fmtInt($totalPcs) }}</td>
                    <td class="number">{{ $fmtFloat($totalKubik, 4) }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach

    <table class="data-table" style="margin-top: 6px;">
        <thead>

        </thead>
        <tbody>
            <tr class="totals-row">
                <td class="center">Grand Total</td>
                <td class="number" style="width: 15%;">{{ $fmtInt($grandPcs) }}</td>
                <td class="number" style="width: 15%;">{{ $fmtFloat($grandKubik, 4) }}</td>
            </tr>
        </tbody>
    </table>

    </body>

</html>
