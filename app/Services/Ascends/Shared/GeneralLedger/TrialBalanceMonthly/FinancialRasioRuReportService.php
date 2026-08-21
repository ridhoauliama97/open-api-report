<?php

namespace App\Services\Ascends\Shared\GeneralLedger\TrialBalanceMonthly;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class FinancialRasioRuReportService
{
    private const TITLE = 'Laporan Financial Ratio';

    private const OPERATING_EXPENSE_EXCLUDED_500 = [
        '500.001', '500.003', '500.018',
    ];

    private const OPERATING_EXPENSE_INCLUDED_500 = [
        '500.004', '500.005', '500.006', '500.010', '500.015',
    ];

    private const CURRENT_ASSET_PREFIXES = [
        '111.101', '111.102', '111.103', '111.105',
        '111.200', '111.400',
    ];

    private const CURRENT_LIABILITY_PREFIXES = [
        '211.100', '211.200', '211.300',
        '211.400', '211.500', '212.100', '222.000',
    ];

    private const REVENUE_PREFIXES = ['411', '412'];

    private const EXPENSE_PREFIXES = ['516', '711', '721', '900'];

    private const OTHER_INCOME_PREFIX = '800';

    private const DEDUCTION_PREFIX = '621';

    private const ASSET_PREFIXES = ['111', '112', '121'];

    private const LIABILITY_PREFIXES = ['211', '212'];

    private const EQUITY_PREFIXES = ['311', '312', '313', '314', '399'];

    private const OPERATING_EXPENSE_PREFIXES = ['711', '721'];

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

        $periodLabel = now()->locale('id')->isoFormat('MMM-YY');

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
                'hpp' => 0.0,
                'beban_penjualan' => 0.0,
                'beban_adm' => 0.0,
                'beban_lain' => 0.0,
                'potongan' => 0.0,
                'total_asset' => 0.0,
                'total_aktiva' => 0.0,
                'operating_expense_500' => 0.0,
                'liabilitas' => 0.0,
                'equitas' => 0.0,
                'piutang' => 0.0,
                'persediaan' => 0.0,
                'operating_expense' => 0.0,
                'penyusutan' => 0.0,
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
            $normalBalance = (string) ($row['Normal Balance'] ?? $row['Normal_Balance'] ?? '');

            $prefix7 = substr($accountCode, 0, 7);
            $prefix3 = substr($accountCode, 0, 3);

            if (in_array($prefix7, self::CURRENT_ASSET_PREFIXES, true) || $accountCode === '121.300.101') {
                $monthly[$key]['aktiva_lancar'] += $ending;
            }

            if (in_array($prefix7, self::CURRENT_LIABILITY_PREFIXES, true)) {
                $monthly[$key]['hutang_lancar'] += $ending;
            }

            if (in_array($prefix3, self::REVENUE_PREFIXES, true)) {
                $monthly[$key]['pendapatan'] += $ending;
            }

            if ($prefix3 === self::OTHER_INCOME_PREFIX) {
                $monthly[$key]['other_income'] += $ending;
            }

            if ($prefix3 === self::DEDUCTION_PREFIX) {
                $monthly[$key]['potongan'] += $ending;
            }

            if ($prefix3 === '516') {
                $monthly[$key]['hpp'] += $ending;
            }

            if ($prefix3 === '711') {
                $monthly[$key]['beban_penjualan'] += $ending;
                $monthly[$key]['operating_expense'] += $ending;
            }

            if ($prefix3 === '721') {
                $monthly[$key]['beban_adm'] += $ending;
                $monthly[$key]['operating_expense'] += $ending;
            }

            if ($prefix3 === '500' && $ending > 0 && ! in_array($prefix7, self::OPERATING_EXPENSE_EXCLUDED_500, true)) {
                $monthly[$key]['operating_expense_500'] += $ending;
                $monthly[$key]['operating_expense'] += $ending;
            }

            if ($prefix7 === '500.001') {
                $monthly[$key]['penyusutan'] += $ending;
            }

            if ($prefix3 === '900') {
                $monthly[$key]['beban_lain'] += $ending;
            }

            if (in_array($prefix3, self::ASSET_PREFIXES, true)) {
                if ($prefix3 === '121' && $normalBalance === 'Credit') {
                    $monthly[$key]['total_asset'] -= $ending;
                } else {
                    $monthly[$key]['total_asset'] += $ending;
                }
            }

            if ($prefix3 === '121' && $normalBalance === 'Debit') {
                $monthly[$key]['total_aktiva'] += $ending;
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
        $roaRows = [];
        $roeRows = [];
        $nwcRows = [];
        $gpmRows = [];
        $ebitdaRows = [];
        $runRateRows = [];
        $opexRows = [];
        $derRows = [];
        $darRows = [];
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
                - $data['hpp'] - $data['beban_penjualan'] - $data['beban_adm'] - $data['beban_lain'];
            $operatingExpense = $data['operating_expense'];
            $totalAsset = $data['total_asset'];
            $totalAktiva = $data['total_aktiva'];
            $liabilitas = $data['liabilitas'];
            $equitas = $data['equitas'];
            $piutang = $data['piutang'];
            $persediaan = $data['persediaan'];
            $equityCalc = $totalAsset - $liabilitas;

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

            $roaRows[] = [
                'no' => $no,
                'bulan' => $bulan,
                'nilai_x' => $labaBersih,
                'nilai_y' => $totalAktiva,
                'rasio' => $totalAktiva != 0 ? ($labaBersih / $totalAktiva) * 100 : 0,
            ];

            $roeRows[] = [
                'no' => $no,
                'bulan' => $bulan,
                'nilai_x' => $labaBersih,
                'nilai_y' => $equityCalc,
                'rasio' => $equityCalc != 0 ? ($labaBersih / $equityCalc) * 100 : 0,
            ];

            $nwcRows[] = [
                'no' => $no,
                'bulan' => $bulan,
                'nilai_x' => $aktivaLancar,
                'nilai_y' => $hutangLancar,
                'rasio' => $aktivaLancar - $hutangLancar,
            ];

            $labaKotor = $pendapatan - $data['hpp'] + $data['potongan'];

            $gpmRows[] = [
                'no' => $no,
                'bulan' => $bulan,
                'nilai_x' => $labaKotor,
                'nilai_y' => $pendapatan,
                'rasio' => $pendapatan != 0 ? ($labaKotor / $pendapatan) * 100 : 0,
            ];

            $penyusutan = $data['penyusutan'];
            $labaOperasional = $labaKotor - $data['beban_penjualan'] - $data['beban_adm'];
            $ebitda = $labaOperasional + $penyusutan;

            $ebitdaRows[] = [
                'no' => $no,
                'bulan' => $bulan,
                'nilai_x' => $labaOperasional,
                'nilai_y' => $penyusutan,
                'rasio' => $ebitda,
            ];

            $runRateRows[] = [
                'no' => $no,
                'bulan' => $bulan,
                'nilai_x' => $pendapatan,
                'nilai_y' => $pendapatan * 12,
                'rasio' => $pendapatan * 12,
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
                'nilai_y' => $equityCalc,
                'rasio' => $equityCalc != 0 ? ($hutangLancar / $equityCalc) * 100 : 0,
            ];

            $darRows[] = [
                'no' => $no,
                'bulan' => $bulan,
                'nilai_x' => $liabilitas,
                'nilai_y' => $totalAsset,
                'rasio' => $totalAsset != 0 ? ($liabilitas / $totalAsset) * 100 : 0,
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
                'id' => 'roa',
                'title' => 'Return On Asset (ROA)',
                'description' => 'Tingkat pengembalian aset merupakan rasio profitabilitas untuk menilai persentase keuntungan (laba) yang diperoleh perusahaan terkait sumber daya atau total fixed aset sehingga efisiensi suatu perusahaan dalam mengelola asetnya bisa terlihat dari persentase rasio ini.',
                'footer_note' => 'Keterangan: < 1% Tidak baik, > 1% Baik',
                'columns' => ['No', 'Bulan', 'Nilai Laba Bersih', 'Nilai Total Aktiva', 'Rasio %'],
                'rows' => $roaRows,
            ],
            [
                'id' => 'roe',
                'title' => 'Return on Equity (ROE)',
                'description' => '(ROE) adalah cara untuk mengukur seberapa hebat perusahaan memakai modal atau uang dari pemilik saham untuk menghasilkan laba bersih.',
                'footer_note' => 'Keterangan:<br>- Di atas 15%: Biasanya dianggap sangat baik dan menunjukkan manajemen sangat efisien.<br>- Sekitar 10% - 15%: Dianggap cukup sehat dan normal untuk banyak perusahaan mapan.<br>- Di bawah 5%: Menandakan perusahaan kurang efisien dalam mengolah modal jadi laba.',
                'columns' => ['No', 'Bulan', 'Nilai Laba Bersih', 'Nilai Ekuitas', 'Rasio %'],
                'rows' => $roeRows,
            ],
            [
                'id' => 'nwc',
                'title' => 'Net Working Capital (NWC)',
                'description' => 'Net Working Capital (NWC) atau modal kerja bersih adalah selisih antara aset lancar (kas, piutang, persediaan) dan kewajiban lancar (utang usaha, beban yang masih harus dibayar). Analisis ini mengukur tingkat likuiditas jangka pendek, efisiensi operasional, dan kesehatan finansial perusahaan untuk memenuhi kewajiban yang jatuh tempo dalam waktu satu tahun.',
                'footer_note' => '<strong>Rumus NWC = Aset Lancar - Kewajiban Lancar</strong><br>- NWC positif, aset lancar lebih besar daripada kewajiban lancar. Secara umum, perusahaan memiliki ruang yang lebih baik untuk memenuhi kewajiban jangka pendek.<br>- NWC negatif, kewajiban lancar lebih besar daripada aset lancar. Ini dapat menunjukkan tekanan likuiditas, meskipun pada beberapa bisnis tertentu kondisi ini bisa menjadi bagian dari model bisnis.<br>- NWC meningkat, belum tentu selalu positif. Bisa berarti likuiditas membaik, tetapi juga dapat menunjukkan terlalu banyak dana yang tertahan dalam persediaan atau piutang.<br>- NWC menurun, bisa menunjukkan penggunaan modal kerja yang lebih efisien, tetapi jika terlalu rendah dapat meningkatkan risiko kesulitan memenuhi kewajiban jangka pendek.',
                'columns' => ['No', 'Bulan', 'Nilai Aset Lancar', 'Kewajiban Lancar', 'Nilai NWC'],
                'column_format' => 'amount',
                'rows' => $nwcRows,
            ],
            [
                'id' => 'gpm',
                'title' => 'Gross Profit Margin (GPM)',
                'description' => 'Gross Profit Margin (GPM) adalah cara menghitung persentase laba kotor dari total pendapatan penjualan setelah dikurangi Harga Pokok Penjualan (HPP). Rasio ini dipakai untuk menilai seberapa efisien sebuah usaha dalam memakai biaya produksi.',
                'footer_note' => '<strong>Rumus GPM = (Laba Kotor / Penjualan Bersih) * 100%</strong><br>- GPM tinggi, perusahaan mampu menghasilkan laba kotor yang relatif besar dari penjualannya. Hal ini dapat menunjukkan harga jual yang baik atau pengendalian biaya produksi yang efektif.<br>- GPM rendah, biaya produksi/HPP relatif besar dibandingkan penjualan, sehingga ruang perusahaan untuk memperoleh laba menjadi lebih kecil.<br>- GPM meningkat, biasanya menunjukkan peningkatan efisiensi produksi, kenaikan harga jual, atau penurunan HPP.<br>- GPM menurun, dapat disebabkan oleh kenaikan biaya bahan baku, biaya produksi, diskon yang lebih besar, atau tekanan harga jual.',
                'columns' => ['No', 'Bulan', 'Nilai Laba Kotor', 'Nilai Penjualan Bersih', 'Rasio %'],
                'rows' => $gpmRows,
            ],
            [
                'id' => 'ebitda',
                'title' => 'EBITDA',
                'description' => 'EBITDA (Earnings Before Interest, Taxes, Depreciation, and Amortization) adalah indikator finansial untuk mengukur profitabilitas operasional murni suatu perusahaan sebelum dikurangi beban bunga, pajak, penyusutan, dan amortisasi. Analisis ini membantu menilai kemampuan bisnis menghasilkan laba dari aktivitas inti tanpa dipengaruhi keputusan pendanaan atau akuntansi.',
                'footer_note' => '<strong>Rumus EBITDA = Laba Operasional (EBIT) + Depresiasi + Amortisasi</strong><br>- Evaluasi Kinerja Murni: Menunjukkan seberapa efisien operasional perusahaan tanpa gangguan struktur utang atau tarif pajak.<br>- Perbandingan Industri: Memudahkan perbandingan profitabilitas antar perusahaan yang memiliki metode pendanaan atau aset berbeda.<br>- Analisis Valuasi: EBITDA sering digunakan sebagai dasar valuasi perusahaan, misalnya melalui rasio EV/EBITDA.',
                'columns' => ['No', 'Bulan', 'Nilai Laba (Rugi) Operasional', 'Nilai Penyusutan', 'Nilai EBITDA'],
                'column_format' => 'amount',
                'rows' => $ebitdaRows,
            ],
            [
                'id' => 'revenue_run_rate',
                'title' => 'Revenue Run Rate',
                'description' => 'Revenue Run Rate (sering disebut Run Rate saja) adalah metode peramalan kinerja keuangan perusahaan untuk satu tahun penuh (12 bulan) dengan mengeksplorasi data pendapatan dari periode yang lebih pendek (seperti satu bulan atau satu kuartal). Rumus dasarnya adalah mengalikan pendapatan periode pendek dengan faktor pengali agar genap menjadi 12 bulan.',
                'footer_note' => '<strong>1. Menggunakan Data Bulanan: Revenue Run Rate = Revenue Bulanan x 12</strong><br>2. Menggunakan Data Kuartal (3 Bulan): Revenue Run Rate = Revenue 3 Bulan x 4<br>Laporan ini menggunakan metode data bulanan (x 12).',
                'columns' => ['No', 'Bulan', 'Nilai Pendapatan Bulanan', 'Revenue Run Rate'],
                'column_format' => 'amount',
                'rows' => $runRateRows,
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
                'title' => 'Debt To Equity',
                'description' => 'Pengertian dari Debt to Equity Ratio (DER) adalah sebuah rasio keuangan yang membandingkan jumlah hutang dengan ekuitas. Ekuitas dan jumlah hutang yang digunakan untuk operasional perusahaan harus berada dalam jumlah yang proporsional. DER > 100%, maka hutang lebih besar dari ekuitas, artinya terlalu berisiko. DER < 100%, maka hutang lebih kecil dari pada ekuitas, artinya aman. Nilai maksimal dari rasio ini adalah 200% sebagai batas aman perusahaan memenuhi kewajiban jangka panjang.',
                'footer_note' => '',
                'columns' => ['No', 'Bulan', 'Nilai Kewajiban Lancar', 'Nilai Equitas', 'Rasio %'],
                'rows' => $derRows,
            ],
            [
                'id' => 'dar',
                'title' => 'Debt To Asset (DAR)',
                'description' => 'Menurut Kasmir Debt to Asset Ratio juga bisa digunakan untuk mengukur seberapa besar aset perusahaan dibiayai oleh utang. Rasio ini sangat penting guna melihat solvabilitas perusahaan atau kemampuan untuk menyelesaikan segala kewajiban jangka panjang. Namun, secara garis besar nilai DAR dapat diklasifikasikan sebagai berikut: DAR < 0,5 : Artinya mayoritas aset perusahaan tersebut didanai oleh modal (equitas) perusahaan sendiri dan bukan dari pinjaman. DAR > 0,5 : Artinya mayoritas aset perusahaan berasal dari utang. DAR = 0,6-0,7 : Artinya mayoritas aset perusahaan berasal dari utang tapi masih dalam batas kewajaran.',
                'footer_note' => 'Liabilitas sendiri dapat diartikan sebagai hutang yang mesti dilunasi pihak lain di masa datang. Keduanya, baik liabilitas maupun aset, sama-sama diambil dari nilai totalnya. Maka dapat disimpulkan aset haruslah dari hasil penjumlahan aset lancar dan aset tidak lancar. Liabilitas juga diambil dari liabilitas jangka pendek dan liabilitas jangka panjang.',
                'columns' => ['No', 'Bulan', 'Nilai Liabilitas', 'Nilai Total Asset', 'Rasio %'],
                'rows' => $darRows,
            ],
            [
                'id' => 'receivable_turnover',
                'title' => 'Rasio Perputaran Piutang (Receivable Turnover)',
                'description' => 'Rasio perputaran piutang digunakan untuk mengukur kualitas dan efisiensi tingkat perputaran piutang perusahaan dalam satu periode dengan membandingkan penjualan dengan rata-rata piutang.',
                'footer_note' => '<strong>Target rasio dikatakan baik apabila >= 100%.</strong>',
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
