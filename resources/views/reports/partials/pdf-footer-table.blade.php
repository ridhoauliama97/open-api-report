@php
    $footerGeneratedByName = $generatedByName
        ?? ($generatedBy?->name
            ?? ($generatedBy?->Username ?? 'sistem'));
    $footerGeneratedAtText = $generatedAtText
        ?? (isset($generatedAt) && method_exists($generatedAt, 'copy')
            ? $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i')
            : now()->locale('id')->translatedFormat('d-M-y H:i'));
@endphp

<htmlpagefooter name="reportFooter">
    <table
        class="footer-table"
        style="width: 100%; border-collapse: collapse; border-spacing: 0; table-layout: fixed; border: 0; margin: 0; padding: 0;"
    >
        <colgroup>
            <col style="width: 68%;">
            <col style="width: 32%;">
        </colgroup>
        <tr>
            <td
                class="footer-print"
                style="border: 0; background: transparent; padding: 0; margin: 0; vertical-align: bottom; text-align: left; white-space: nowrap; font-family: 'Noto Serif', serif; font-size: 8px; font-style: italic; font-weight: normal;"
            >
                Dicetak oleh: {{ $footerGeneratedByName }} pada {{ $footerGeneratedAtText }}
            </td>
            <td
                class="footer-page-cell"
                style="border: 0; background: transparent; padding: 0; margin: 0; vertical-align: bottom; text-align: right; white-space: nowrap; font-family: 'Noto Serif', serif; font-size: 8px; font-style: italic; font-weight: normal;"
            >
                Halaman {PAGENO} dari {nbpg}
            </td>
        </tr>
    </table>
</htmlpagefooter>
<sethtmlpagefooter name="reportFooter" value="on" />
