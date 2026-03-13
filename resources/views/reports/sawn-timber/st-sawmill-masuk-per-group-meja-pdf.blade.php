<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 18mm 10mm 18mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.2;
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

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
            border: 1px solid #000;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 0;
            border-left: 1px solid #000;
            padding: 3px 4px;
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

        /* Hilangkan garis horizontal antar baris data (kecuali baris total). */
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

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
            white-space: nowrap;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
        }

        /* Footer line to "close" the table on each page fragment when table is split across pages. */
        .tfoot-line td {
            border-top: 1px solid #000;
            padding: 0;
            height: 0;
            line-height: 0;
            font-size: 0;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left,
        .footer-right {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $meja = is_array($data['meja'] ?? null) ? $data['meja'] : [];
        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $grandTotals = is_array($summary['grand_totals'] ?? null) ? $summary['grand_totals'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse((string) $startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) $endDate)->locale('id')->translatedFormat('d-M-y');

        $eps = 0.0000001;
        $fmt = static fn(float $v): string => abs($v) < 0.0000001 ? '' : number_format($v, 4, '.', ',');
        $fmtTebal = static fn(?float $v): string => $v === null ? '' : number_format($v, 2, ',', '.');
    @endphp

    <h1 class="report-title">Laporan ST (Sawmill) Masuk Per-Group</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 110px;">Group Jenis</th>
                <th rowspan="2" style="width: 120px;">Jenis Kayu</th>
                <th rowspan="2" style="width: 60px;">Tebal</th>
                <th colspan="{{ max(1, count($meja)) }}">Meja ke :</th>
                <th rowspan="2" style="width: 72px;">Total</th>
            </tr>
            <tr>
                @forelse ($meja as $m)
                    <th style="width: 62px;">{{ $m }}</th>
                @empty
                    <th style="width: 62px;">-</th>
                @endforelse
            </tr>
        </thead>
        {{-- NOTE: mPDF recognizes repeating <tfoot> more reliably when it appears before <tbody>. --}}
        <tfoot>
            <tr class="tfoot-line">
                <td colspan="{{ 4 + max(1, count($meja)) }}">&nbsp;</td>
            </tr>
        </tfoot>
        <tbody>
            @php $rowIndex = 0; @endphp

            @forelse ($groups as $group)
                @php
                    $groupName = trim((string) ($group['name'] ?? ''));
                    $groupName = $groupName !== '' ? $groupName : 'Tanpa Group';
                    $jenisList = is_array($group['jenis'] ?? null) ? $group['jenis'] : [];

                    $groupRowspan = 1; // group total row
                    foreach ($jenisList as $j) {
                        $tebalList = is_array($j['tebal'] ?? null) ? $j['tebal'] : [];
                        $groupRowspan += count($tebalList) + 1; // +1 for jenis subtotal row
                    }

                    $groupTotals = is_array($group['totals'] ?? null) ? $group['totals'] : [];
                    $firstGroupRow = true;
                @endphp

                @foreach ($jenisList as $jenis)
                    @php
                        $jenisName = trim((string) ($jenis['name'] ?? ''));
                        $jenisName = $jenisName !== '' ? $jenisName : 'Tanpa Jenis';
                        $tebalList = is_array($jenis['tebal'] ?? null) ? $jenis['tebal'] : [];
                        $jenisTotals = is_array($jenis['totals'] ?? null) ? $jenis['totals'] : [];
                        $jenisRowspan = count($tebalList) + 1; // +1 for subtotal row
                        $firstJenisRow = true;
                    @endphp

                    @foreach ($tebalList as $t)
                        @php
                            $rowIndex++;
                            $vals = is_array($t['values'] ?? null) ? $t['values'] : [];
                            $tebal = is_numeric($t['tebal'] ?? null) ? (float) $t['tebal'] : null;
                            $rowTotal = 0.0;
                            foreach ($meja as $m) {
                                $rowTotal += (float) ($vals[$m] ?? 0.0);
                            }
                        @endphp
                        <tr class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            @if ($firstGroupRow)
                                <td rowspan="{{ $groupRowspan }}" class="center"><strong>{{ $groupName }}</strong>
                                </td>
                                @php $firstGroupRow = false; @endphp
                            @endif
                            @if ($firstJenisRow)
                                <td rowspan="{{ $jenisRowspan }}" class="center"><strong>{{ $jenisName }}</strong>
                                </td>
                                @php $firstJenisRow = false; @endphp
                            @endif
                            <td class="center">{{ $fmtTebal($tebal) }}</td>
                            @if ($meja !== [])
                                @foreach ($meja as $m)
                                    @php $v = (float) ($vals[$m] ?? 0.0); @endphp
                                    <td class="number">{{ $fmt($v) }}</td>
                                @endforeach
                            @else
                                <td class="number"></td>
                            @endif
                            <td class="number">{{ $fmt($rowTotal) }}</td>
                        </tr>
                    @endforeach

                    @php $rowIndex++; @endphp
                    <tr class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }} totals-row">
                        <td class="center"><strong>Jumlah</strong></td>
                        @php
                            $jenisTotal = 0.0;
                            foreach ($meja as $m) {
                                $jenisTotal += (float) ($jenisTotals[$m] ?? 0.0);
                            }
                        @endphp
                        @if ($meja !== [])
                            @foreach ($meja as $m)
                                @php $v = (float) ($jenisTotals[$m] ?? 0.0); @endphp
                                <td class="number"><strong>{{ $fmt($v) }}</strong></td>
                            @endforeach
                        @else
                            <td class="number"><strong></strong></td>
                        @endif
                        <td class="number"><strong>{{ $fmt($jenisTotal) }}</strong></td>
                    </tr>
                @endforeach

                @php $rowIndex++; @endphp
                <tr class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }} totals-row">
                    <td class="center"><strong>Jumlah</strong></td>
                    <td class="center">&nbsp;</td>
                    @php
                        $groupTotal = 0.0;
                        foreach ($meja as $m) {
                            $groupTotal += (float) ($groupTotals[$m] ?? 0.0);
                        }
                    @endphp
                    @if ($meja !== [])
                        @foreach ($meja as $m)
                            @php $v = (float) ($groupTotals[$m] ?? 0.0); @endphp
                            <td class="number"><strong>{{ $fmt($v) }}</strong></td>
                        @endforeach
                    @else
                        <td class="number"><strong></strong></td>
                    @endif
                    <td class="number"><strong>{{ $fmt($groupTotal) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 4 + max(1, count($meja)) }}" class="center">Tidak ada data.</td>
                </tr>
            @endforelse

            @if ($groups !== [])
                @php $rowIndex++; @endphp
                <tr class="totals-row">
                    <td colspan="3" class="center"><strong>Total</strong></td>
                    @php
                        $grandTotal = 0.0;
                        foreach ($meja as $m) {
                            $grandTotal += (float) ($grandTotals[$m] ?? 0.0);
                        }
                    @endphp
                    @if ($meja !== [])
                        @foreach ($meja as $m)
                            @php $v = (float) ($grandTotals[$m] ?? 0.0); @endphp
                            <td class="number"><strong>{{ $fmt($v) }}</strong></td>
                        @endforeach
                    @else
                        <td class="number"><strong></strong></td>
                    @endif
                    <td class="number"><strong>{{ $fmt($grandTotal) }}</strong></td>
                </tr>
            @endif
        </tbody>
    </table>

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
