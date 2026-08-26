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
            margin: 0;
            padding: 0;
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
            width: calc(100% - 2px);
            line-height: inherit;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px 2px;
        }

        td.center {
            text-align: center;
        }

        td.label {
            white-space: nowrap;
        }

        td.number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .totals-row td {
            font-weight: bold;
        }

        .headers-row th {
            font-weight: bold;
        }

        .kd-title {
            margin: 10px 0 6px 0;
            font-weight: bold;
            font-size: 11px;
        }

        .center {
            text-align: center;
        }

        tfoot td {
            font-weight: bold;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse((string) ($startDate ?? ''))->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) ($endDate ?? ''))->locale('id')->translatedFormat('d-M-y');

        $fmtTon = static function ($v): string {
            $n = (float) ($v ?? 0.0);
            if (abs($n) < 0.0000001) {
                return '';
            }
            return number_format($n, 4, '.', ',');
        };

        $fmtDate = static function ($v): string {
            $t = is_string($v) ? trim($v) : '';
            if ($t === '') {
                return '';
            }
            try {
                return \Carbon\Carbon::parse($t)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $e) {
                return $t;
            }
        };

        $calcDays = static function ($in, $out): string {
            $inDate = is_string($in) ? trim($in) : '';
            $outDate = is_string($out) ? trim($out) : '';

            if ($inDate === '' || $outDate === '') {
                return '0';
            }

            try {
                return (string) \Carbon\Carbon::parse($outDate)->diffInDays(\Carbon\Carbon::parse($inDate), false);
            } catch (\Throwable $e) {
                return '0';
            }
        };
    @endphp

    <h1 class="report-title">Laporan Mutasi KD</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @forelse ($groups as $group)
        @php
            $kd = (int) ($group['no_ruang_kd'] ?? 0);
            $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            $totals = is_array($group['totals'] ?? null) ? $group['totals'] : [];
            $totalDays = 0;

            foreach ($rows as $row) {
                $days = $calcDays($row['TglKeluar'] ?? '', $row['TglMasuk'] ?? '');

                if ($days !== '') {
                    $totalDays += (int) $days;
                }
            }
        @endphp

        <div class="kd-title">No KD : {{ $kd }}</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 20%;">Tanggal (In)</th>
                    <th style="width: 20%;">Ton (In)</th>
                    <th style="width: 20%;">Tanggal (Out)</th>
                    <th style="width: 20%;">Ton (Out)</th>
                    <th style="width: 16%;">Jumlah Hari</th>
                </tr>
            </thead>
            <tbody>
                @php $i = 0; @endphp
                @forelse ($rows as $r)
                    @php $i++; @endphp
                    <tr class="{{ $i % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $i }}</td>
                        <td class="center">{{ $fmtDate($r['TglMasuk'] ?? '') }}</td>
                        <td class="number">{{ $fmtTon($r['TonIn'] ?? 0) }}</td>
                        <td class="center">{{ $fmtDate($r['TglKeluar'] ?? '') }}</td>
                        <td class="number">{{ $fmtTon($r['TonOut'] ?? 0) }}</td>
                        <td class="center">{{ $calcDays($r['TglKeluar'] ?? '', $r['TglMasuk'] ?? '') }} Hari</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="center">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="center" style="font-weight: bold;">Total</td>
                    <td class="number">{{ $fmtTon($totals['ton_in'] ?? 0) }}</td>
                    <td></td>
                    <td class="number">{{ $fmtTon($totals['ton_out'] ?? 0) }}</td>
                    <td class="center">{{ $totalDays }} Hari</td>
                </tr>
            </tfoot>
        </table>
    @empty
        <div class="center">Tidak ada data.</div>
    @endforelse
</body>

</html>
