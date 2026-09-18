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

        // Stable order: follow service sorting by keys.
        $jenisKeys = array_keys($groupedByJenis);
        sort($jenisKeys, SORT_STRING);

        $alphaIndexToLabel = static function (int $idx): string {
            // 0 -> A, 1 -> B, ...
            $base = ord('A') + ($idx % 26);
            return chr($base);
        };

        // Build summary: per Jenis -> per NamaGrade totals.
        $summaryByJenis = [];
        foreach ($jenisKeys as $jenisKey) {
            $jenisRows = $groupedByJenis[$jenisKey] ?? [];
            foreach ($jenisRows as $r) {
                $gradeKey = (string) ($r['NamaGrade'] ?? '');
                if ($gradeKey === '') {
                    $gradeKey = '-';
                }
                $summaryByJenis[$jenisKey][$gradeKey]['pcs'] =
                    ($summaryByJenis[$jenisKey][$gradeKey]['pcs'] ?? 0) + (int) ($r['Pcs'] ?? 0);
                $summaryByJenis[$jenisKey][$gradeKey]['kubik'] =
                    ($summaryByJenis[$jenisKey][$gradeKey]['kubik'] ?? 0.0) + (float) ($r['Kubik'] ?? 0.0);
            }
        }
    @endphp

    <h1 class="report-title">Laporan Label S4S (Hidup) Per-Jenis Kayu</h1>
    <p class="report-subtitle">&nbsp;</p>

    @php $jenisIndex = 0; @endphp
    @foreach ($jenisKeys as $jenisKey)
        @php
            $jenisIndex++;
            $jenisRows = $groupedByJenis[$jenisKey] ?? [];
            $jenisLabel = $alphaIndexToLabel($jenisIndex - 1) . '. ' . $jenisKey;

            $groupedByGrade = [];
            foreach ($jenisRows as $r) {
                $gradeKey = (string) ($r['NamaGrade'] ?? '');
                if ($gradeKey === '') {
                    $gradeKey = '-';
                }
                $groupedByGrade[$gradeKey][] = $r;
            }

            $gradeKeys = array_keys($groupedByGrade);
            sort($gradeKeys, SORT_STRING);
        @endphp

        <div class="group-title">{{ $jenisLabel }}</div>

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

            <table class="data-table" style="margin-bottom: 8px;">
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
                            <td class="number" style="font-weight: bold;">
                                {{ $fmtInt(is_numeric($gr['Pcs'] ?? null) ? (int) $gr['Pcs'] : null) }}
                            </td>
                            <td class="number" style="font-weight: bold;">{{ $fmtFloat($gr['Kubik'] ?? null, 4) }}</td>
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

    <div class="group-title" style="margin-top: 14px;">Rangkuman</div>

    @php $sumJenisIndex = 0; @endphp
    @foreach ($jenisKeys as $jenisKey)
        @php
            $sumJenisIndex++;
            $jenisLabel = $alphaIndexToLabel($sumJenisIndex - 1) . '. ' . $jenisKey;
            $gradeSummary = $summaryByJenis[$jenisKey] ?? [];
            $sumGradeKeys = array_keys($gradeSummary);
            sort($sumGradeKeys, SORT_STRING);

            $totalPcsJenis = 0;
            $totalKubikJenis = 0.0;
            foreach ($sumGradeKeys as $gk) {
                $totalPcsJenis += (int) ($gradeSummary[$gk]['pcs'] ?? 0);
                $totalKubikJenis += (float) ($gradeSummary[$gk]['kubik'] ?? 0.0);
            }
        @endphp

        <div class="group-title">{{ $jenisLabel }}</div>

        <table class="data-table" style="margin-bottom: 10px;">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th>Nama Grade</th>
                    <th style="width: 15%;">Jmlh Batang</th>
                    <th style="width: 15%;">Kubik</th>
                </tr>
            </thead>
            <tbody>
                @php $sumRowIndex = 0; @endphp
                @foreach ($sumGradeKeys as $gk)
                    @php
                        $sumRowIndex++;
                        $cls = $sumRowIndex % 2 === 1 ? 'row-odd' : 'row-even';
                        $pcs = (int) ($gradeSummary[$gk]['pcs'] ?? 0);
                        $kubik = (float) ($gradeSummary[$gk]['kubik'] ?? 0.0);
                    @endphp
                    <tr class="{{ $cls }}">
                        <td class="center">{{ $sumRowIndex }}</td>
                        <td>{{ $gk }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtInt($pcs) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtFloat($kubik, 4) }}</td>
                    </tr>
                @endforeach

                <tr class="totals-row">
                    <td colspan="2" class="center">Total {{ $jenisKey }}</td>
                    <td class="number">{{ $fmtInt($totalPcsJenis) }}</td>
                    <td class="number">{{ $fmtFloat($totalKubikJenis, 4) }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach

    </body>

</html>
