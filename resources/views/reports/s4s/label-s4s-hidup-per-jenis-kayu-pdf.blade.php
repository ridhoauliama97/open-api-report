<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 12mm 10mm 14mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.15;
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

        .group-title {
            margin: 10px 0 4px 0;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
            border-top: 1px solid #000;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            border-bottom: 0;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th:first-child,
        td:first-child {
            border-left: 0;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            border-bottom: 1px solid #000;
            background: #fff;
        }

        tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .center {
            text-align: center;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            background: #fff;
        }

        .table-end-line td {
            border-top: 1px solid #000 !important;
            border-right: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: #fff !important;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

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

            <table style="margin-bottom: 8px;">
                <thead>
                    <tr>
                        <th style="width: 30px;">No</th>
                        <th style="width: 140px;">Nama Grade</th>
                        <th style="width: 40px;">Tebal</th>
                        <th style="width: 40px;">Lebar</th>
                        <th style="width: 52px;">Panjang</th>
                        <th style="width: 54px;">Jmlh Batang</th>
                        <th style="width: 60px;">Kubik</th>
                    </tr>
                </thead>
                {{-- IMPORTANT (mPDF): place tfoot before tbody so the footer-group is repeated on each page break. --}}
                <tfoot>
                    <tr class="table-end-line">
                        <td colspan="7"></td>
                    </tr>
                </tfoot>
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
                            <td class="number">{{ $fmtInt(is_numeric($gr['Pcs'] ?? null) ? (int) $gr['Pcs'] : null) }}
                            </td>
                            <td class="number">{{ $fmtFloat($gr['Kubik'] ?? null, 4) }}</td>
                        </tr>
                    @endforeach

                    <tr class="totals-row">
                        <td colspan="5" class="center">Jmlh Per-{{ $gradeKey }}</td>
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

        <table style="margin-bottom: 10px;">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th>Nama Grade</th>
                    <th style="width: 100px;">Jmlh Batang</th>
                    <th style="width: 70px;">Kubik</th>
                </tr>
            </thead>
            {{-- IMPORTANT (mPDF): place tfoot before tbody so the footer-group is repeated on each page break. --}}
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="4"></td>
                </tr>
            </tfoot>
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
                        <td class="number">{{ $fmtInt($pcs) }}</td>
                        <td class="number">{{ $fmtFloat($kubik, 4) }}</td>
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

    @include('reports.partials.pdf-footer-table')
</body>

</html>
