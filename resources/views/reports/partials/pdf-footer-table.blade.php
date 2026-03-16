@php
    $footerGeneratedByName = $generatedByName ?? ($generatedBy?->name ?? 'sistem');
    $footerGeneratedAtText = $generatedAtText
        ?? (isset($generatedAt) && method_exists($generatedAt, 'copy')
            ? $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i')
            : now()->locale('id')->translatedFormat('d-M-y H:i'));
@endphp

<htmlpagefooter name="reportFooter">
    <table class="footer-table">
        <colgroup>
            <col style="width: 25%;">
            <col style="width: 25%;">
            <col style="width: 25%;">
            <col style="width: 25%;">
        </colgroup>
        <tr>
            <td colspan="2" class="footer-print">Dicetak oleh: {{ $footerGeneratedByName }} pada
                {{ $footerGeneratedAtText }}</td>
            <td class="footer-spacer"></td>
            <td class="footer-page-cell"><span class="footer-page">Halaman {PAGENO} dari {nbpg}</span></td>
        </tr>
    </table>
</htmlpagefooter>
<sethtmlpagefooter name="reportFooter" value="on" />

