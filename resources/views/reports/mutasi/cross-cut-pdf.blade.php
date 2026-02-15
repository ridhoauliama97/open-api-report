<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 24mm 12mm 20mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Calibri", sans-serif;
            font-size: 8px;
            line-height: 1.45;
            color: #1f2937;
            background: #fff;
        }

        .report-header {
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 8px;
            margin-bottom: 10px;
            text-align: center;
            font-family: "Noto Serif", serif;
        }

        .report-title {
            margin: 0;
            font-size: 14px;
            letter-spacing: 0.2px;
        }

        .report-subtitle {
            margin: 2px 0 0 0;
            color: #6b7280;
            font-size: 10px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        .table th,
        .table td {
            border: 1px solid #9ca3af;
            padding: 6px 7px;
            vertical-align: middle;
            word-break: break-word;
        }

        .table thead th {
            background: #fff;
            color: #1f2937;
            font-weight: bold;
            text-align: center;
            border: 1px solid #9ca3af;
        }

        .table thead .sub-header th {
            background: #fff;
            color: #1f2937;
            font-weight: bold;
            border: 1px solid #9ca3af;
        }

        .table-striped tbody tr.row-odd td {
            background-color: #c9d1df;
        }

        .table-striped tbody tr.row-even td {
            background-color: #eef2f8;
        }

        .total-row td {
            background: #fff !important;
            font-weight: bold;
        }

        .number {
            text-align: right;
            white-space: nowrap;
        }

        .col-total {
            font-weight: bold;
        }

        .no-col {
            text-align: center;
            white-space: nowrap;
        }

        .empty {
            text-align: center;
            padding: 16px;
            color: #6b7280;
        }

        .page-footer {
            width: 100%;
            border-top: 1px solid #d1d5db;
            color: #6b7280;
            font-size: 8px;
            padding-top: 4px;
            font-style: italic;
            border-collapse: collapse;
            font-family: "Noto Serif", serif;
        }

        .page-footer td {
            padding: 0;
            vertical-align: top;
        }

        .page-footer-left {
            width: 70%;
            text-align: left;
        }

        .page-footer-right {
            width: 30%;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $generatedByName = $generatedBy?->name ?? 'Sistem';

        $excludedKeys = ['created_at', 'updated_at'];
        $groupConfig = [
            'adjoutcca' => ['group' => 'masuk', 'order' => 1, 'label' => 'Adj Out CCA'],
            'bsoutcca' => ['group' => 'masuk', 'order' => 2, 'label' => 'BS Out CCA'],
            'ccaprodout' => ['group' => 'masuk', 'order' => 3, 'label' => 'CCA Prod Out'],
            'adjincca' => ['group' => 'keluar', 'order' => 1, 'label' => 'Adj In CCA'],
            'bsincca' => ['group' => 'keluar', 'order' => 2, 'label' => 'BS In CCA'],
            'ccajual' => ['group' => 'keluar', 'order' => 3, 'label' => 'CCA Jual'],
            'fjprodinput' => ['group' => 'keluar', 'order' => 4, 'label' => 'FJ Prod Input'],
            'lmtprodinput' => ['group' => 'keluar', 'order' => 5, 'label' => 'LMT Prod Input'],
            'mildprodinput' => ['group' => 'keluar', 'order' => 6, 'label' => 'Mild Prod Input'],
            's4sprodinput' => ['group' => 'keluar', 'order' => 7, 'label' => 'S4S Prod Input'],
            'sandprodinput' => ['group' => 'keluar', 'order' => 8, 'label' => 'Sand Prod Input'],
            'packprodinput' => ['group' => 'keluar', 'order' => 9, 'label' => 'Pack Prod Input'],
            'ccaprodinput' => ['group' => 'keluar', 'order' => 10, 'label' => 'CCA Prod Input'],
        ];

        $normalizedRows = [];
        $displayKeys = [];
        $displayLabels = [];
        $totals = [];

        $idKey = null;
        $jenisKey = null;
        $awalKey = null;
        $masukKeysWithOrder = [];
        $keluarKeysWithOrder = [];
        $masukKeys = [];
        $keluarKeys = [];
        $totalMasukKey = null;
        $totalKeluarKey = null;
        $totalAkhirKey = null;
        $otherKeys = [];
        $renderKeys = [];

        if (isset($rows) && is_iterable($rows)) {
            $normalizedRows = is_array($rows) ? $rows : collect($rows)->values()->all();
        }

        if (!empty($normalizedRows) && is_array($normalizedRows[0])) {
            foreach (array_keys($normalizedRows[0]) as $key) {
                if (in_array($key, $excludedKeys, true)) {
                    continue;
                }

                $displayKeys[] = $key;

                if (!in_array($key, ['id', 'jenis'], true)) {
                    $totals[$key] = 0.0;
                }
            }

            foreach ($displayKeys as $key) {
                $normalizedKey = strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $key));
                $defaultLabel = str_replace('_', ' ', ucwords((string) $key, '_'));

                if ($normalizedKey === 'id' && $idKey === null) {
                    $idKey = $key;
                    $displayLabels[$key] = 'No';
                    continue;
                }

                if ($normalizedKey === 'jenis' && $jenisKey === null) {
                    $jenisKey = $key;
                    $displayLabels[$key] = 'Jenis';
                    continue;
                }

                if ($awalKey === null && in_array($normalizedKey, ['awal', 'saldoawal', 'openingbalance'], true)) {
                    $awalKey = $key;
                    $displayLabels[$key] = 'Awal';
                    continue;
                }

                if (isset($groupConfig[$normalizedKey])) {
                    $cfg = $groupConfig[$normalizedKey];
                    $displayLabels[$key] = $cfg['label'];

                    if ($cfg['group'] === 'masuk') {
                        $masukKeysWithOrder[] = ['key' => $key, 'order' => $cfg['order']];
                    } else {
                        $keluarKeysWithOrder[] = ['key' => $key, 'order' => $cfg['order']];
                    }

                    continue;
                }

                if ($totalMasukKey === null && in_array($normalizedKey, ['totalmasuk', 'masuktotal'], true)) {
                    $totalMasukKey = $key;
                    $displayLabels[$key] = 'Total Masuk';
                    continue;
                }

                if ($totalKeluarKey === null && in_array($normalizedKey, ['totalkeluar', 'keluartotal'], true)) {
                    $totalKeluarKey = $key;
                    $displayLabels[$key] = 'Total Keluar';
                    continue;
                }

                if ($totalAkhirKey === null && in_array($normalizedKey, ['totalakhir', 'akhirtotal', 'grandtotal', 'total'], true)) {
                    $totalAkhirKey = $key;
                    $displayLabels[$key] = 'Total Akhir';
                    continue;
                }

                $displayLabels[$key] = $defaultLabel;
                $otherKeys[] = $key;
            }

            usort($masukKeysWithOrder, fn($a, $b) => $a['order'] <=> $b['order']);
            usort($keluarKeysWithOrder, fn($a, $b) => $a['order'] <=> $b['order']);

            $masukKeys = array_map(fn($item) => $item['key'], $masukKeysWithOrder);
            $keluarKeys = array_map(fn($item) => $item['key'], $keluarKeysWithOrder);

            $renderKeys = array_values(array_filter([
                $idKey,
                $jenisKey,
                $awalKey,
                ...$masukKeys,
                $totalMasukKey,
                ...$keluarKeys,
                $totalKeluarKey,
                $totalAkhirKey,
                ...$otherKeys,
            ]));

            foreach ($normalizedRows as $row) {
                foreach (array_keys($totals) as $totalKey) {
                    $value = $row[$totalKey] ?? null;

                    if (is_numeric($value)) {
                        $totals[$totalKey] += (float) $value;
                    }
                }
            }
        }

        $hasSubHeader = !empty($masukKeys) || !empty($keluarKeys);
    @endphp

    <htmlpagefooter name="report-footer">
        <table class="page-footer">
            <tr>
                <td class="page-footer-left">Dicetak oleh {{ $generatedByName }} pada
                    {{ $generatedAt->format('Y-m-d H:i:s') }}
                </td>
                <td class="page-footer-right">Halaman {PAGENO} dari {nbpg}</td>
            </tr>
        </table>
    </htmlpagefooter>
    <sethtmlpagefooter name="report-footer" value="on" />

    <div class="report-header">
        <h1 class="report-title">Laporan Mutasi Cross Cut</h1>
        <p class="report-subtitle">Periode {{ $startDate }} s/d {{ $endDate }}</p>
    </div>

    <table class="table table-striped">
        <thead>
            @if (!empty($renderKeys))
                <tr>
                    @if ($idKey)
                        <th rowspan="{{ $hasSubHeader ? '2' : '1' }}">{{ $displayLabels[$idKey] }}</th>
                    @endif
                    @if ($jenisKey)
                        <th rowspan="{{ $hasSubHeader ? '2' : '1' }}">{{ $displayLabels[$jenisKey] }}</th>
                    @endif
                    @if ($awalKey)
                        <th rowspan="{{ $hasSubHeader ? '2' : '1' }}">{{ $displayLabels[$awalKey] }}</th>
                    @endif
                    @if (!empty($masukKeys))
                        <th colspan="{{ count($masukKeys) }}">Masuk</th>
                    @endif
                    @if ($totalMasukKey)
                        <th rowspan="{{ $hasSubHeader ? '2' : '1' }}">{{ $displayLabels[$totalMasukKey] }}</th>
                    @endif
                    @if (!empty($keluarKeys))
                        <th colspan="{{ count($keluarKeys) }}">Keluar</th>
                    @endif
                    @if ($totalKeluarKey)
                        <th rowspan="{{ $hasSubHeader ? '2' : '1' }}">{{ $displayLabels[$totalKeluarKey] }}</th>
                    @endif
                    @if ($totalAkhirKey)
                        <th rowspan="{{ $hasSubHeader ? '2' : '1' }}">{{ $displayLabels[$totalAkhirKey] }}</th>
                    @endif
                    @foreach ($otherKeys as $header)
                        <th rowspan="{{ $hasSubHeader ? '2' : '1' }}">{{ $displayLabels[$header] }}</th>
                    @endforeach
                </tr>
                @if ($hasSubHeader)
                    <tr class="sub-header">
                        @foreach ($masukKeys as $header)
                            <th>{{ $displayLabels[$header] }}</th>
                        @endforeach
                        @foreach ($keluarKeys as $header)
                            <th>{{ $displayLabels[$header] }}</th>
                        @endforeach
                    </tr>
                @endif
            @else
                <tr>
                    <th>Data</th>
                </tr>
            @endif
        </thead>
        <tbody>
            @forelse ($normalizedRows as $row)
            <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                @foreach ($renderKeys as $key)
                @php($value = $row[$key] ?? null)
                <td
                    class="{{ $key === $idKey ? 'no-col' : (array_key_exists($key, $totals) ? 'number' : '') }} {{ in_array($key, [$totalMasukKey, $totalKeluarKey, $totalAkhirKey], true) ? 'col-total' : '' }}">
                    @if ($key === $idKey)
                        {{ $loop->parent->iteration }}
                    @else
                        {{ is_numeric($value) ? number_format((float) $value, 2, ',', '.') : $value }}
                    @endif
                </td>
                @endforeach
            </tr>
            @empty
            <tr>
                <td class="empty" colspan="{{ count($renderKeys) > 0 ? count($renderKeys) : 1 }}">Tidak ada data mutasi
                    pada periode ini.</td>
            </tr>
            @endforelse
            @if (!empty($normalizedRows) && !empty($renderKeys))
                <tr class="total-row">
                    @foreach ($renderKeys as $key)
                        @if ($key === $idKey)
                            <td class="no-col" colspan="2">Total</td>
                        @elseif ($key === $jenisKey)
                            {{-- <td>-</td> --}}
                        @else
                            <td class="number">{{ number_format($totals[$key] ?? 0, 2, ',', '.') }}</td>
                        @endif
                    @endforeach
                </tr>
            @endif
        </tbody>
    </table>
</body>

</html>