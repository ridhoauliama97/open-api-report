<?php

namespace App\Services\Ascends\Shared\GeneralLedger\TrialBalanceMonthly;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class FinancialRasioGsuReportService
{
    private const TITLE = 'Laporan Financial Ratio';

    private const CURRENT_ASSET_PREFIXES = [
        '111.101', '111.102', '111.105',
        '111.200', '111.300', '111.400',
    ];

    private const CURRENT_LIABILITY_PREFIXES = [
        '211.100', '211.200', '211.300',
        '211.400', '211.500', '212.100', '222.000',
    ];

    private const REVENUE_PREFIX = '411';

    private const REVENUE_DEDUCTION_PREFIXES = ['431', '451'];

    private const EXPENSE_PREFIXES = [
        '501', '512', '514', '516', '642',
        '711', '721', '722', '900',
    ];

    private const OTHER_INCOME_PREFIX = '800';

    private const DEDUCTION_PREFIX = '621';

    private const OPERATING_EXPENSE_PREFIXES = ['711', '721', '514'];

    private const ASSET_PREFIXES = ['111', '112', '121', '131'];

    private const LIABILITY_PREFIXES = ['211', '212', '222'];

    private const EQUITY_PREFIXES = ['312', '313', '314'];

    private const RECEIVABLE_PREFIX = '111.200';

    private const INVENTORY_PREFIX = '111.400';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $periods = $this->extractPeriods($allRows);
        $monthlyData = $this->computeMonthlyValues($allRows, $periods);

        $ratios = $this->buildRatios($monthlyData);

        $maxDate = end($periods);
        $periodLabel = $maxDate !== false
            ? (clone $maxDate)->locale('id')->isoFormat('MMM-YY')
            : '';

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => 'Periode '.$periodLabel,
            'ratios' => $ratios,
            'printed_by' => '',
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML kosong.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("File XML tidak valid: {$sourceLabel}");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'Table1') {
                continue;
            }

            $recordXml = $reader->readOuterXml();
            if (! is_string($recordXml) || trim($recordXml) === '') {
                continue;
            }

            $node = @simplexml_load_string($recordXml, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($node === false) {
                continue;
            }

            $row = [];
            foreach ($node->children() as $key => $value) {
                $cleanKey = $this->cleanXmlKey((string) $key);
                $row[$cleanKey] = trim((string) $value);
            }

            if (($row['AccountCode1'] ?? '') !== '') {
                $rows[] = $row;
            }
        }

        $reader->close();

        return $rows;
    }

    private function cleanXmlKey(string $key): string
    {
        $key = str_replace('_x0020_', ' ', $key);
        $key = str_replace('_x0028_', '(', $key);
        $key = str_replace('_x0029_', ')', $key);

        return str_replace('_x002F_', '/', $key);
    }

    private function extractPeriods(array $rows): array
    {
        $periodSet = [];
        foreach ($rows as $row) {
            $dateStr = (string) ($row['PeriodDate'] ?? '');
            if ($dateStr === '') {
                continue;
            }
            try {
                $date = Carbon::parse($dateStr)->startOfMonth();
                $key = $date->format('Y-m');
                $periodSet[$key] = $date;
            } catch (Throwable) {
            }
        }

        ksort($periodSet);

        return array_values($periodSet);
    }

    private function computeMonthlyValues(array $rows, array $periods): array
    {
        $monthly = [];

        foreach ($periods as $period) {
            $key = $period->format('Y-m');
            $monthly[$key] = [
                'aktiva_lancar' => 0.0,
                'hutang_lancar' => 0.0,
                'pendapatan' => 0.0,
                'other_income' => 0.0,
                'potongan' => 0.0,
                'potongan_penjualan' => 0.0,
                'retur_penjualan' => 0.0,
                'hpp' => 0.0,
                'beban_produksi' => 0.0,
                'beban_penjualan' => 0.0,
                'beban_adm' => 0.0,
                'beban_lain' => 0.0,
                'operating_expense' => 0.0,
                'total_asset' => 0.0,
                'liabilitas' => 0.0,
                'equitas' => 0.0,
                'piutang' => 0.0,
                'persediaan' => 0.0,
                'period' => $period,
            ];
        }

        foreach ($rows as $row) {
            $dateStr = (string) ($row['PeriodDate'] ?? '');
            if ($dateStr === '') {
                continue;
            }
            try {
                $date = Carbon::parse($dateStr)->startOfMonth();
                $key = $date->format('Y-m');
            } catch (Throwable) {
                continue;
            }

            if (! isset($monthly[$key])) {
                continue;
            }

            $accountCode = (string) ($row['AccountCode1'] ?? '');
            $ending = (float) ($row['Ending'] ?? 0);

            $prefix7 = substr($accountCode, 0, 7);
            $prefix3 = substr($accountCode, 0, 3);

            if (in_array($prefix7, self::CURRENT_ASSET_PREFIXES, true)) {
                $monthly[$key]['aktiva_lancar'] += $ending;
            }

            if (in_array($prefix7, self::CURRENT_LIABILITY_PREFIXES, true)) {
                $monthly[$key]['hutang_lancar'] += $ending;
            }

            if ($prefix3 === self::REVENUE_PREFIX) {
                $monthly[$key]['pendapatan'] += $ending;
            }

            if ($prefix3 === self::OTHER_INCOME_PREFIX) {
                $monthly[$key]['other_income'] += $ending;
            }

            if ($prefix3 === self::DEDUCTION_PREFIX) {
                $monthly[$key]['potongan'] += $ending;
            }

            if ($prefix3 === '431') {
                $monthly[$key]['potongan_penjualan'] += $ending;
            }

            if ($prefix3 === '451') {
                $monthly[$key]['retur_penjualan'] += $ending;
            }

            if ($prefix3 === '516') {
                $monthly[$key]['hpp'] += $ending;
            }

            if ($prefix3 === '514') {
                $monthly[$key]['beban_produksi'] += $ending;
                $monthly[$key]['operating_expense'] += $ending;
            }

            if ($prefix3 === '711') {
                $monthly[$key]['beban_penjualan'] += $ending;
                $monthly[$key]['operating_expense'] += $ending;
            }

            if ($prefix3 === '712') {
                $monthly[$key]['beban_penjualan'] += $ending;
            }

            if ($prefix3 === '721') {
                $monthly[$key]['beban_adm'] += $ending;
                $monthly[$key]['operating_expense'] += $ending;
            }

            if ($prefix3 === '722') {
                $monthly[$key]['beban_adm'] += $ending;
            }

            if ($prefix3 === '900') {
                $monthly[$key]['beban_lain'] += $ending;
            }

            if ($prefix3 === '501') {
                $monthly[$key]['beban_produksi'] += $ending;
            }

            if ($prefix3 === '512') {
                $monthly[$key]['beban_produksi'] += $ending;
            }

            if ($prefix3 === '642') {
                $monthly[$key]['beban_produksi'] += $ending;
            }

            if (in_array($prefix3, self::ASSET_PREFIXES, true)) {
                $monthly[$key]['total_asset'] += $ending;
            }

            if (in_array($prefix3, self::LIABILITY_PREFIXES, true)) {
                $monthly[$key]['liabilitas'] += $ending;
            }

            if (in_array($prefix3, self::EQUITY_PREFIXES, true)) {
                $monthly[$key]['equitas'] += $ending;
            }

            if (substr($accountCode, 0, strlen(self::RECEIVABLE_PREFIX)) === self::RECEIVABLE_PREFIX) {
                $monthly[$key]['piutang'] += $ending;
            }

            if (substr($accountCode, 0, strlen(self::INVENTORY_PREFIX)) === self::INVENTORY_PREFIX) {
                $monthly[$key]['persediaan'] += $ending;
            }
        }

        return $monthly;
    }

    private function buildRatios(array $monthlyData): array
    {
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $currentRatioRows = [];
        $rosRows = [];
        $opexRows = [];
        $derRows = [];
        $receivableTurnoverRows = [];
        $inventoryTurnoverRows = [];

        $no = 0;
        foreach ($monthlyData as $key => $data) {
            $no++;
            $period = $data['period'];
            $monthNum = (int) $period->format('n');
            $bulan = $monthNames[$monthNum] ?? $period->locale('id')->isoFormat('MMMM');

            $aktivaLancar = $data['aktiva_lancar'];
            $hutangLancar = $data['hutang_lancar'];
            $pendapatan = $data['pendapatan'];
            $labaBersih = $pendapatan + $data['other_income'] + $data['potongan']
                - $data['potongan_penjualan'] - $data['retur_penjualan']
                - $data['hpp'] - $data['beban_produksi']
                - $data['beban_penjualan'] - $data['beban_adm'] - $data['beban_lain'];
            $operatingExpense = $data['operating_expense'];
            $liabilitas = $data['liabilitas'];
            $equitas = $data['equitas'];
            $piutang = $data['piutang'];
            $persediaan = $data['persediaan'];

            $currentRatioRows[] = [
                'no' => $no,
                'bulan' => $bulan,
                'nilai_x' => $aktivaLancar,
                'nilai_y' => $hutangLancar,
                'rasio' => $hutangLancar != 0 ? ($aktivaLancar / $hutangLancar) * 100 : 0,
            ];

            $rosRows[] = [
                'no' => $no,
                'bulan' => $bulan,
                'nilai_x' => $labaBersih,
                'nilai_y' => $pendapatan,
                'rasio' => $pendapatan != 0 ? ($labaBersih / $pendapatan) * 100 : 0,
            ];

            $opexRows[] = [
                'no' => $no,
                'bulan' => $bulan,
                'nilai_x' => $operatingExpense,
                'nilai_y' => $pendapatan,
                'rasio' => $pendapatan != 0 ? ($operatingExpense / $pendapatan) * 100 : 0,
            ];

            $derRows[] = [
                'no' => $no,
                'bulan' => $bulan,
                'nilai_x' => $hutangLancar,
                'nilai_y' => $equitas,
                'rasio' => $equitas != 0 ? ($hutangLancar / $equitas) * 100 : 0,
            ];

            $receivableTurnoverRows[] = [
                'no' => $no,
                'bulan' => $bulan,
                'nilai_x' => $pendapatan,
                'nilai_y' => $piutang,
                'rasio' => $piutang != 0 ? ($pendapatan / $piutang) * 100 : 0,
            ];

            $inventoryTurnoverRows[] = [
                'no' => $no,
                'bulan' => $bulan,
                'nilai_x' => $pendapatan,
                'nilai_y' => $persediaan,
                'rasio' => $persediaan != 0 ? ($pendapatan / $persediaan) * 100 : 0,
            ];
        }

        return [
            [
                'id' => 'current_ratio',
                'title' => 'Current Ratio',
                'description' => 'Rasio yang mengukur kemampuan perusahaan dalam membayar kewajiban jangka pendek dengan aktiva lancar yang tersedia.',
                'footer_note' => 'Semakin besar perbandingan aktiva lancar dengan utang lancar, semakin tinggi kemampuan perusahaan menutupi kewajiban jangka pendeknya. <strong>Jadi dikatakan sehat jika rasionya berada di atas 100%.</strong>',
                'columns' => ['No', 'Bulan', 'Nilai Aktiva Lancar', 'Nilai Hutang Lancar', 'Rasio %'],
                'rows' => $currentRatioRows,
            ],
            [
                'id' => 'ros',
                'title' => 'Return On Sales (ROS)',
                'description' => 'Return on Sales adalah rasio keuangan yang bertujuan untuk mengukur seberapa efisien perusahaan menghasilkan laba dari penjualan yang didapat. Angka ROS bisa memberikan informasi berapa banyak keuntungan yang dihasilkan oleh sebuah perusahaan setelah membayar biaya variabel produksi seperti upah, bahan baku dan lain-lainnya.',
                'footer_note' => '<strong>Semakin tinggi nilai persentase rasio maka semakin baik.</strong>',
                'columns' => ['No', 'Bulan', 'Nilai Laba Bersih', 'Nilai Pendapatan', 'Rasio %'],
                'rows' => $rosRows,
            ],
            [
                'id' => 'opex_ratio',
                'title' => 'Opex Ratio',
                'description' => 'Opex ratio merupakan rasio untuk mengukur tingkat biaya operasional perusahaan terhadap penjualan.',
                'footer_note' => '<strong>Semakin rendah nilai persentase rasio maka semakin baik.</strong>',
                'columns' => ['No', 'Bulan', 'Nilai Operating Expense', 'Nilai Sales', 'Rasio %'],
                'rows' => $opexRows,
            ],
            [
                'id' => 'der',
                'title' => 'Dept To Equality',
                'description' => 'Pengertian dari Debt to Equity Ratio (DER) adalah sebuah rasio keuangan yang membandingkan jumlah hutang dengan ekuitas. Ekuitas dan jumlah hutang yang digunakan untuk operasional perusahaan harus berada dalam jumlah yang proporsional. DER > 100%, maka hutang lebih besar dari ekuitas, artinya terlalu berisiko. DER < 100%, maka hutang lebih kecil dari pada ekuitas, artinya aman. Nilai maksimal dari rasio ini adalah 200% sebagai batas aman perusahaan memenuhi kewajiban jangka panjang.',
                'footer_note' => '',
                'columns' => ['No', 'Bulan', 'Nilai Kewajiban Lancar', 'Nilai Equitas', 'Rasio %'],
                'rows' => $derRows,
            ],
            [
                'id' => 'receivable_turnover',
                'title' => 'Rasio Perputaran Piutang (Receivable Turnover)',
                'description' => 'Rasio perputaran piutang digunakan untuk mengukur kualitas dan efisiensi tingkat perputaran piutang perusahaan dalam satu periode dengan membandingkan penjualan dengan rata-rata piutang.',
                'footer_note' => '<strong>Target rasio dikatakan baik apabila >= 40%.</strong>',
                'columns' => ['No', 'Bulan', 'Penjualan', 'Nilai Piutang Rata-rata', 'Rasio %'],
                'rows' => $receivableTurnoverRows,
            ],
            [
                'id' => 'inventory_turnover',
                'title' => 'Rasio Perputaran Persediaan (Inventory Turnover)',
                'description' => 'Rasio inventory turnover digunakan untuk mengukur tingkat kualitas dan efisiensi perputaran persediaan perusahaan terhadap penjualan dalam satu periode tertentu.',
                'footer_note' => '<strong>Semakin tinggi rasionya, maka pengelolaan persediaan yang dilakukan oleh perusahaan semakin efisien.</strong>',
                'columns' => ['No', 'Bulan', 'Penjualan', 'Persediaan', 'Rasio %'],
                'rows' => $inventoryTurnoverRows,
            ],
        ];
    }
}
