<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page { margin: 14mm 10mm 14mm 10mm; footer: html_reportFooter; }
        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
        }
        .report-companyTitle { text-align: center; margin: 0 0 4px; font-size: 18px; font-weight: bold; }
        .report-title { text-align: center; margin: 0; font-size: 16px; font-weight: bold; }
        .report-subtitle { text-align: center; margin: 2px 0 14px; font-size: 12px; color: #636466; }

        .report-table { width: 100%; border-collapse: collapse; table-layout: fixed; border: 1px solid #000; }
        .report-table th {
            font-weight: bold; font-size: 11px; text-align: center;
            border: 1px solid #000; padding: 4px 3px; background: #fff;
        }
        .report-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            border-top: none;
            border-bottom: none;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .col-name { width: 36%; }
        .col-ref { width: 14%; }

        .section-label { padding: 3px 4px; }
        .sub-section-label { padding: 2px 4px 2px 14px; }
        .ref-cell { text-align: center; color: #999; font-size: 9px; }

        .section-row td { border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .subtotal-row td { border-bottom: 1px solid #000; }
        .section-total-row td { border-top: 1px solid #000; border-bottom: 1px solid #000; }
        .grand-total-row td { border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 3px 4px; }

        .row-odd td { background: #c9d1df; }
        .row-even td { background: #eef2f8; }

        .nt { border: none; border-collapse: collapse; width: 100%; table-layout: fixed; }
        .nt td { border: none !important; padding: 0; }
        .ntl { text-align: left; word-wrap: break-word; overflow: hidden; }
        .ntr { text-align: right; white-space: nowrap; width: 45%; }

        .b { font-weight: bold; }
        .fs10 { font-size: 10px; }
        .fs11 { font-size: 11px; }
        .p1 { padding: 1px 4px; }
        .p2 { padding: 2px 4px; }
        .p3 { padding: 3px 4px; }
    </style>
</head>

<body>
    @php
        $leftSections = $reportData['left_sections'] ?? [];
        $rightSections = $reportData['right_sections'] ?? [];
        $leftGrandTotal = (float) ($reportData['left_grand_total'] ?? 0);
        $rightGrandTotal = (float) ($reportData['right_grand_total'] ?? 0);
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));

        function fmtAmount($value)
        {
            $v = (float) $value;
            if ($v < 0) {
                return '(' . number_format(abs($v), 0, ',', '.') . ')';
            }
            if ($v == 0.0) {
                return '0';
            }
            return number_format($v, 0, ',', '.');
        }

        function buildSideRows($sections)
        {
            $rows = [];
            foreach ($sections as $section) {
                $hasItems = false;
                foreach ($section['sub_sections'] as $sub) {
                    if (count($sub['items']) > 0) { $hasItems = true; break; }
                }
                if (!$hasItems) continue;

                $rows[] = ['type' => 'section', 'name' => $section['name']];

                foreach ($section['sub_sections'] as $sub) {
                    if (count($sub['items']) === 0) continue;

                    $rows[] = ['type' => 'sub_section', 'name' => $sub['name']];

                    foreach ($sub['items'] as $item) {
                        $rows[] = [
                            'type' => 'item',
                            'name' => (string) ($item['account_name'] ?? ''),
                            'amount' => fmtAmount($item['balance'] ?? 0),
                        ];
                    }

                    $rows[] = ['type' => 'subtotal', 'name' => 'TOTAL ' . $sub['name'], 'amount' => fmtAmount($sub['total'] ?? 0)];
                }

                $rows[] = ['type' => 'section_total', 'name' => 'TOTAL ' . $section['name'], 'amount' => fmtAmount($section['total'] ?? 0)];
            }
            return $rows;
        }

        function nmCell($name, $amount, $padLeft, $isBold)
        {
            $pad = $padLeft > 0 ? 'padding-left:' . $padLeft . 'px;' : '';
            $bold = $isBold ? 'font-weight:bold;' : '';
            $sName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $sAmount = htmlspecialchars($amount, ENT_QUOTES, 'UTF-8');
            $sty = $bold . $pad;
            $attr = $sty !== '' ? ' style="' . $sty . '"' : '';
            return '<table class="nt"><tr>'
                . '<td class="ntl"' . $attr . '>' . $sName . '</td>'
                . '<td class="ntr" style="' . $bold . '">' . $sAmount . '</td>'
                . '</tr></table>';
        }

        $leftRows = buildSideRows($leftSections);
        $rightRows = buildSideRows($rightSections);
        $maxRows = max(count($leftRows), count($rightRows));
        $itemIdx = 0;
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    <table class="report-table">
        <colgroup>
            <col class="col-name">
            <col class="col-ref">
            <col class="col-name">
            <col class="col-ref">
        </colgroup>
        <thead>
            <tr>
                <th>AKTIVA</th>
                <th>REF</th>
                <th>KEWAJIBAN &amp; EKUITAS</th>
                <th>REF</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < $maxRows; $i++)
                @php
                    $lr = $leftRows[$i] ?? null;
                    $rr = $rightRows[$i] ?? null;
                    $lt = $lr['type'] ?? null;
                    $rt = $rr['type'] ?? null;
                    $isSection = $lt === 'section' || $rt === 'section';
                    $isSubtotal = $lt === 'subtotal' || $rt === 'subtotal';
                    $isSectionTotal = $lt === 'section_total' || $rt === 'section_total';
                    $isItem = $lt === 'item' || $rt === 'item';
                    if ($isItem) $itemIdx++;
                @endphp
                <tr class="@if($isSection) section-row @endif @if($isSubtotal) subtotal-row @endif @if($isSectionTotal) section-total-row @endif @if($isItem) {{ $itemIdx % 2 === 0 ? 'row-even' : 'row-odd' }} @endif">

                    {{-- LEFT --}}
                    @if ($lt === 'section')
                        <td class="section-label b fs11">{{ $lr['name'] }}</td>
                        <td class="ref-cell">&nbsp;</td>
                    @elseif ($lt === 'sub_section')
                        <td class="sub-section-label b fs10">{{ $lr['name'] }}</td>
                        <td class="ref-cell">&nbsp;</td>
                    @elseif ($lt === 'item')
                        <td class="p1">{!! nmCell($lr['name'], $lr['amount'], 28, false) !!}</td>
                        <td class="ref-cell">&nbsp;</td>
                    @elseif ($lt === 'subtotal')
                        <td class="p2">{!! nmCell($lr['name'], $lr['amount'], 14, true) !!}</td>
                        <td class="ref-cell">&nbsp;</td>
                    @elseif ($lt === 'section_total')
                        <td class="p3">{!! nmCell($lr['name'], $lr['amount'], 0, true) !!}</td>
                        <td class="ref-cell">&nbsp;</td>
                    @else
                        <td>&nbsp;</td>
                        <td class="ref-cell">&nbsp;</td>
                    @endif

                    {{-- RIGHT --}}
                    @if ($rt === 'section')
                        <td class="section-label b fs11">{{ $rr['name'] }}</td>
                        <td class="ref-cell">&nbsp;</td>
                    @elseif ($rt === 'sub_section')
                        <td class="sub-section-label b fs10">{{ $rr['name'] }}</td>
                        <td class="ref-cell">&nbsp;</td>
                    @elseif ($rt === 'item')
                        <td class="p1">{!! nmCell($rr['name'], $rr['amount'], 28, false) !!}</td>
                        <td class="ref-cell">&nbsp;</td>
                    @elseif ($rt === 'subtotal')
                        <td class="p2">{!! nmCell($rr['name'], $rr['amount'], 14, true) !!}</td>
                        <td class="ref-cell">&nbsp;</td>
                    @elseif ($rt === 'section_total')
                        <td class="p3">{!! nmCell($rr['name'], $rr['amount'], 0, true) !!}</td>
                        <td class="ref-cell">&nbsp;</td>
                    @else
                        <td>&nbsp;</td>
                        <td class="ref-cell">&nbsp;</td>
                    @endif

                </tr>
            @endfor
            {{-- Grand Total --}}
            <tr class="grand-total-row">
                <td class="b fs11">{!! nmCell('TOTAL AKTIVA', fmtAmount($leftGrandTotal), 0, true) !!}</td>
                <td class="ref-cell">&nbsp;</td>
                <td class="b fs11">{!! nmCell('TOTAL PASSIVA & MODAL', fmtAmount($rightGrandTotal), 0, true) !!}</td>
                <td class="ref-cell">&nbsp;</td>
            </tr>
        </tbody>
    </table>

    @include('ascends.shared.partials.report-footer')
</body>

</html>
