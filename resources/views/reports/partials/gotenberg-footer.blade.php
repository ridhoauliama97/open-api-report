<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            width: 100%;
            padding: 0 10mm;
            font-family: "Noto Serif", serif;
            font-size: 8px;
            font-style: italic;
            color: #000;
            line-height: 1.2;
        }

        table.footer-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.footer-table td {
            vertical-align: bottom;
            padding: 0;
        }

        .footer-left {
            text-align: left;
            width: 40%;
        }

        .footer-center {
            text-align: center;
            width: 20%;
        }

        .footer-right {
            text-align: right;
            width: 40%;
        }
    </style>
</head>

<body>
    <table class="footer-table">
        <tbody>
            <tr>
                <td class="footer-left">Dicetak oleh: {{ $generatedByName ?? 'sistem' }} pada
                    {{ $generatedAtText ?? '-' }}</td>
                <td class="footer-center">{{ $footerCenterText ?? '' }}</td>
                <td class="footer-right">Halaman <span class="pageNumber"></span> dari <span class="totalPages"></span>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>
