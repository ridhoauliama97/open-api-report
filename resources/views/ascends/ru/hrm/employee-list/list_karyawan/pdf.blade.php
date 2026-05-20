<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        @page {
            margin: 12mm 8mm 12mm 8mm;
        }

        body {
            font-family: "Noto Serif", serif;
            font-size: 10px;
            color: #000000;
        }

        .report-header {
            border-bottom: 2px solid #000000;
            margin-bottom: 10px;
            padding-bottom: 6px;
        }

        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .period {
            text-align: center;
            font-size: 12px;
            margin-bottom: 6px;
            color: #636466;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9px;
        }

        .meta-table td {
            border: 0;
            padding: 1px 2px;
        }

        .meta-label {
            width: 13%;
            font-weight: bold;
            white-space: nowrap;
        }

        .meta-value {
            width: 37%;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        .detail-table th,
        .detail-table td {
            border: 1px solid #000000;
            padding: 3px 3px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .detail-table th {
            text-align: center;
            font-weight: bold;
            background: #d7deea;
            font-size: 9px;
            line-height: 1.15;
        }

        .group-row td {
            font-weight: bold;
            text-align: left;
            font-size: 11px;
            padding: 5px 6px;
            background: #b9c4d6;
        }

        .center {
            text-align: center;
        }

        .nowrap {
            white-space: nowrap;
        }

        .text-muted {
            color: #555555;
        }

        .row-odd td {
            background: #f4f6fa;
        }

        .empty-value {
            color: #666666;
            font-style: italic;
        }
    </style>
</head>

<body>
    @php
        $headers = $reportData['headers'] ?? [];
        $columnCount = count($headers) + 1;
        $printedAt = \Carbon\Carbon::parse($reportData['printed_at'] ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y');
        $formatCell = static function (mixed $value): string {
            $text = trim((string) $value);
            if ($text === '') {
                return '';
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}T/', $text) === 1) {
                return \Carbon\Carbon::parse($text)->locale('id')->translatedFormat('d M Y');
            }

            return $text;
        };
    @endphp

    <div class="report-header">
        <div class="title">{{ $reportData['title'] }}</div>
        <div class="period">Per : {{ $printedAt }}</div>
    </div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Company</td>
            <td class="meta-value">: {{ $reportData['company'] ?? 'RU' }}</td>
            <td class="meta-label">Total Karyawan</td>
            <td class="meta-value">: {{ number_format((int) ($reportData['total_rows'] ?? 0), 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Module</td>
            <td class="meta-value">: {{ strtoupper($reportData['module'] ?? 'HRM') }}</td>
            <td class="meta-label">Total Departemen</td>
            <td class="meta-value">: {{ number_format((int) ($reportData['summary']['department_count'] ?? 0), 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Sumber XML</td>
            <td class="meta-value text-muted" colspan="3">: {{ $reportData['source_file'] ?? '-' }}</td>
        </tr>
    </table>

    <table class="detail-table">
        <thead>
            <tr>
                <th>No</th>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($reportData['grouped_rows'] ?? [] as $department => $departmentRows)
                <tr class="group-row">
                    <td colspan="{{ $columnCount }}">{{ $department }} ({{ count($departmentRows) }} karyawan)</td>
                </tr>
                @foreach ($departmentRows as $index => $row)
                    <tr class="{{ $index % 2 === 0 ? '' : 'row-odd' }}">
                        <td class="center">{{ $index + 1 }}</td>
                        @foreach ($headers as $header)
                            @php $cellValue = $formatCell($row[$header] ?? ''); @endphp
                            <td>
                                @if ($cellValue !== '')
                                    {{ $cellValue }}
                                @else
                                    <span class="empty-value">-</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>

</html>
