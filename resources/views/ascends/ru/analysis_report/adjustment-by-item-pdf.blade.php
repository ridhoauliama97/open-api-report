<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $reportData['title'] }}</title>
    <style>
        body {
            font-family: dejavusans, sans-serif;
            font-size: 9px;
            color: #111827;
        }

        h1 {
            margin: 0 0 8px 0;
            font-size: 18px;
            letter-spacing: 0.3px;
        }

        .meta-table,
        .summary-table,
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 3px 5px;
            vertical-align: top;
        }

        .summary-wrap {
            margin: 12px 0;
        }

        .summary-table th,
        .summary-table td,
        .detail-table th,
        .detail-table td {
            border: 1px solid #9ca3af;
            padding: 5px 6px;
        }

        .summary-table th,
        .detail-table th {
            background: #e5e7eb;
            font-weight: bold;
            text-align: left;
        }

        .detail-table thead th {
            font-size: 8px;
        }

        .number {
            text-align: right;
            white-space: nowrap;
        }

        .center {
            text-align: center;
        }

        .muted {
            color: #4b5563;
        }

        .section-title {
            margin: 14px 0 6px 0;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .footer-note {
            margin-top: 8px;
            font-size: 8px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    @php
        $formatDate = static function (?string $value): string {
            if (blank($value)) {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)->format('d-M-y');
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };

        $formatNumber = static function ($value, int $decimals = 4): string {
            return number_format((float) $value, $decimals, '.', ',');
        };
    @endphp

    <h1>{{ $reportData['title'] }}</h1>

    <table class="meta-table">
        <tr>
            <td width="18%"><strong>Source XML</strong></td>
            <td width="32%">{{ $reportData['source_file'] }}</td>
            <td width="18%"><strong>Generated At</strong></td>
            <td width="32%">{{ $reportData['generated_at'] }}</td>
        </tr>
        <tr>
            <td><strong>Total Rows</strong></td>
            <td>{{ number_format($reportData['row_count']) }}</td>
            <td><strong>Total Adjusted Value</strong></td>
            <td>{{ $formatNumber($reportData['total_adjusted_value']) }}</td>
        </tr>
        <tr>
            <td><strong>Total Qty DB</strong></td>
            <td>{{ $formatNumber($reportData['total_quantity_db']) }}</td>
            <td><strong>Total Qty CR</strong></td>
            <td>{{ $formatNumber($reportData['total_quantity_cr']) }}</td>
        </tr>
    </table>

    <div class="summary-wrap">
        <div class="section-title">Warehouse Summary</div>
        <table class="summary-table">
            <thead>
                <tr>
                    <th width="15%">Code</th>
                    <th width="65%">Warehouse</th>
                    <th width="20%" class="center">Rows</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reportData['warehouse_summary'] as $warehouse)
                    <tr>
                        <td>{{ $warehouse['warehouse_code'] ?: '-' }}</td>
                        <td>{{ $warehouse['warehouse_name'] ?: '-' }}</td>
                        <td class="center">{{ number_format($warehouse['count']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section-title">Detail Rows</div>
    <table class="detail-table">
        <thead>
            <tr>
                <th width="5%" class="center">No</th>
                <th width="10%">Tanggal</th>
                <th width="20%">Nama</th>
                <th width="30%">Keterangan</th>
                <th width="10%" class="number">Qty</th>
                <th width="25%" class="number">Adjusted Value</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $index => $row)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $formatDate($row['adjustment_date']) }}</td>
                    <td>{{ $row['item_name'] ?: '-' }}</td>
                    <td>{{ $row['memo_remarks'] ?: '-' }}</td>
                    <td>{{ $row['memo_remarks'] ?: '-' }}</td>
                    <td class="number">
                        {{ $formatNumber($row['quantity_db']) - $formatNumber($row['quantity_cr']) }}
                        {{ $row['uom'] ?: '-' }}
                    </td>
                    <td class="number">{{ $formatNumber($row['adjusted_value']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer-note">
        Remarks are omitted from the detail table to keep the PDF readable in landscape format.
    </div>
</body>

</html>
