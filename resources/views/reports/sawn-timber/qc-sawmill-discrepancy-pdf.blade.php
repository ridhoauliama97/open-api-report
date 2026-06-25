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
            margin: 14mm 10mm 14mm 10mm;
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
            margin: 0;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }

        .report-subtitle {
            margin: 2px 0 20px 0;
            text-align: center;
            font-size: 12px;
            color: #636466;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: auto;
            table-layout: fixed;
        }

        .report-table {
            border: 1px solid #000;
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
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        td.center {
            text-align: center;
        }

        td.number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .headers-row th {
            font-size: 11px;
            border-top: 0;
            border-bottom: 1px solid #000;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: none !important;
            border-bottom: none !important;
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
        }

        .report-table tbody tr.row-last td.data-cell {
            border-bottom: 1px solid #000 !important;
        }

        .totals-row td {
            font-size: 11px;
            font-weight: bold;
            border: 1px solid #000;
            background: #fff !important;
        }

        .meta-table {
            width: 100%;
            border: 0;
            margin-bottom: 8px;
            table-layout: fixed;
        }

        .meta-table td {
            border: 0 !important;
            padding: 0 8px 3px 0;
            vertical-align: top;
        }

        .meta-label {
            width: 90px;
            white-space: nowrap;
        }

        .meta-sep {
            width: 10px;
            text-align: center;
        }

        .group-block {
            page-break-inside: auto;
            margin-bottom: 12px;
        }

        .meja-block {
            page-break-inside: auto;
            margin-bottom: 14px;
        }

        .group-meta-table {
            width: 240px;
            border: 0;
            margin: 0 0 8px 0;
        }

        .group-meta-table td {
            border: 0 !important;
            padding: 0 4px 3px 0;
            font-size: 12px;
            font-weight: bold;
        }

        .group-meta-label {
            width: 58px;
            white-space: nowrap;
        }

        .group-meta-sep {
            width: 10px;
            text-align: center;
        }

        .date-meta-table {
            margin-top: 8px;
            margin-bottom: 6px;
        }

        .meja-total-table {
            margin-top: -6px;
        }

        .report-table th {
            font-size: 10px;
        }

        .report-table td {
            font-size: 10px;
            padding: 2px 3px;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $formatDate = static function ($value, string $format = 'd-M-y'): string {
            if ($value === null || trim((string) $value) === '') {
                return '-';
            }

            try {
                return \Carbon\Carbon::parse($value)->locale('id')->translatedFormat($format);
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };

        $formatDim = static function ($value): string {
            $text = number_format((float) $value, 2, '.', ',');
            return rtrim(rtrim($text, '0'), '.');
        };

        $formatDecimal = static fn($value): string => number_format((float) $value, 2, ',', '.');
        $formatPercent = static fn($value): string => number_format((float) $value, 2, ',', '.') . '%';

        $mejaGroups = [];

        foreach ($groups as $group) {
            $namaMeja = trim((string) ($group['nama_meja'] ?? ''));
            $namaMeja = $namaMeja !== '' ? $namaMeja : 'Meja ' . (string) ($group['no_meja'] ?? '-');
            $mejaKey = $namaMeja;
            $groupRows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            $groupSummary = is_array($group['summary'] ?? null) ? $group['summary'] : [];
            $totalRows = (int) ($groupSummary['total_rows'] ?? count($groupRows));

            if (!isset($mejaGroups[$mejaKey])) {
                $mejaGroups[$mejaKey] = [
                    'nama_meja' => $namaMeja,
                    'date_groups' => [],
                    'summary' => [
                        'total_rows' => 0,
                        'total_accurate' => 0,
                        'total_discrepancy' => 0,
                        'avg_deviation_tebal' => 0.0,
                        'avg_deviation_lebar' => 0.0,
                        'accurate_rate' => 0.0,
                    ],
                    '_weighted_deviation_tebal' => 0.0,
                    '_weighted_deviation_lebar' => 0.0,
                ];
            }

            $mejaGroups[$mejaKey]['date_groups'][] = $group;
            $mejaGroups[$mejaKey]['summary']['total_rows'] += $totalRows;
            $mejaGroups[$mejaKey]['summary']['total_accurate'] += (int) ($groupSummary['total_accurate'] ?? 0);
            $mejaGroups[$mejaKey]['summary']['total_discrepancy'] += (int) ($groupSummary['total_discrepancy'] ?? 0);
            $mejaGroups[$mejaKey]['_weighted_deviation_tebal'] +=
                ((float) ($groupSummary['avg_deviation_tebal'] ?? 0)) * $totalRows;
            $mejaGroups[$mejaKey]['_weighted_deviation_lebar'] +=
                ((float) ($groupSummary['avg_deviation_lebar'] ?? 0)) * $totalRows;
        }

        foreach ($mejaGroups as &$mejaGroup) {
            $totalRows = (int) ($mejaGroup['summary']['total_rows'] ?? 0);
            $totalAccurate = (int) ($mejaGroup['summary']['total_accurate'] ?? 0);

            $mejaGroup['summary']['avg_deviation_tebal'] =
                $totalRows > 0 ? $mejaGroup['_weighted_deviation_tebal'] / $totalRows : 0.0;
            $mejaGroup['summary']['avg_deviation_lebar'] =
                $totalRows > 0 ? $mejaGroup['_weighted_deviation_lebar'] / $totalRows : 0.0;
            $mejaGroup['summary']['accurate_rate'] = $totalRows > 0 ? ($totalAccurate / $totalRows) * 100 : 0.0;

            unset($mejaGroup['_weighted_deviation_tebal'], $mejaGroup['_weighted_deviation_lebar']);
        }
        unset($mejaGroup);

        $mejaGroups = array_values(
            array_filter(
                $mejaGroups,
                static fn(array $mejaGroup): bool => (int) ($mejaGroup['summary']['total_discrepancy'] ?? 0) > 0,
            ),
        );
    @endphp

    <h1 class="report-title">Laporan QC Sawmill - Discrepancy</h1>
    <p class="report-subtitle">
        Periode {{ $formatDate($startDate ?? null) }} s/d {{ $formatDate($endDate ?? null) }}
    </p>

    @forelse ($mejaGroups as $mejaGroup)
        <div class="meja-block">
            <table class="group-meta-table">
                <tr>
                    <td class="group-meta-label">Meja</td>
                    <td class="group-meta-sep">:</td>
                    <td>{{ $mejaGroup['nama_meja'] ?? '-' }}</td>
                </tr>
            </table>

            @foreach ($mejaGroup['date_groups'] as $group)
                @php
                    $groupRows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
                    $groupSummary = is_array($group['summary'] ?? null) ? $group['summary'] : [];
                    $tanggal = $group['tanggal'] ?? null;
                @endphp

                <table class="group-meta-table date-meta-table">
                    <tr>
                        <td class="group-meta-label">Tanggal</td>
                        <td class="group-meta-sep">:</td>
                        <td>{{ $formatDate($tanggal, 'd-M-y') }}</td>
                    </tr>
                </table>

                <table class="report-table">
                    <thead>
                        <tr class="headers-row">
                            <th rowspan="2" style="width: 4%;">No</th>
                            <th colspan="2" style="width: 22%;">Cutting</th>
                            <th colspan="2" style="width: 22%;">Actual</th>
                            <th colspan="2" style="width: 22%;">Deviation</th>
                            <th rowspan="2" style="width: 20%;">Accurate</th>
                        </tr>
                        <tr class="headers-row">
                            <th style="width: 11%;">Tebal</th>
                            <th style="width: 11%;">Lebar</th>
                            <th style="width: 11%;">Tebal</th>
                            <th style="width: 11%;">Lebar</th>
                            <th style="width: 11%;">Tebal</th>
                            <th style="width: 11%;">Lebar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groupRows as $index => $row)
                            <tr
                                class="data-row {{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }} {{ $loop->last ? 'row-last' : '' }}">
                                <td class="center data-cell">{{ $row['DisplayNo'] ?? $index + 1 }}</td>
                                <td class="number data-cell">{{ $formatDecimal($row['CuttingTebal'] ?? 0) }}</td>
                                <td class="number data-cell">{{ $formatDecimal($row['CuttingLebar'] ?? 0) }}</td>
                                <td class="number data-cell">{{ $formatDecimal($row['ActualTebal'] ?? 0) }}</td>
                                <td class="number data-cell">{{ $formatDecimal($row['ActualLebar'] ?? 0) }}</td>
                                <td class="number data-cell">{{ $formatDecimal($row['DeviationTebal'] ?? 0) }}</td>
                                <td class="number data-cell">{{ $formatDecimal($row['DeviationLebar'] ?? 0) }}</td>
                                <td class="center data-cell">{{ $row['Accurate'] ?? '' }}</td>
                            </tr>
                        @endforeach
                        <tr class="totals-row">
                            <td colspan="5" class="center">Per-Tanggal {{ $formatDate($tanggal, 'j-M-Y') }} :</td>
                            <td class="number">{{ $formatDecimal($groupSummary['avg_deviation_tebal'] ?? 0) }}</td>
                            <td class="number">{{ $formatDecimal($groupSummary['avg_deviation_lebar'] ?? 0) }}</td>
                            <td class="center">{{ $formatPercent($groupSummary['accurate_rate'] ?? 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            @endforeach

            @php
                $mejaSummary = is_array($mejaGroup['summary'] ?? null) ? $mejaGroup['summary'] : [];
            @endphp

            <table class="report-table meja-total-table">
                <tbody>
                    <tr class="totals-row">
                        <td colspan="5" class="center">Per-Meja {{ $mejaGroup['nama_meja'] ?? '-' }} :</td>
                        <td class="number" style="width: 12.5%">
                            {{ $formatDecimal($mejaSummary['avg_deviation_tebal'] ?? 0) }}
                        </td>
                        <td class="number" style="width: 12.5%">
                            {{ $formatDecimal($mejaSummary['avg_deviation_lebar'] ?? 0) }}
                        </td>
                        <td class="center" style="width: 21%">{{ $formatPercent($mejaSummary['accurate_rate'] ?? 0) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @empty
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="center">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @include('reports.partials.pdf-footer-table')
</body>

</html>