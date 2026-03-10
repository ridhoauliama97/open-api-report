<htmlpagefooter name="reportFooter">
    <div class="footer-wrap">
        <div class="footer-left">Dicetak oleh: {{ $generatedByName ?? 'sistem' }} pada {{ $generatedAtText ?? '' }}</div>
        <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
    </div>
</htmlpagefooter>
<sethtmlpagefooter name="reportFooter" value="on" />
