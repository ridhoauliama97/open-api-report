<?php

namespace App\Services\Ascends\Shared\GeneralLedger\TrialBalanceMonthly;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class FinancialRasioUcReportService
{
    private const TITLE = 'Laporan Financial Rasio';

    private const CURRENT_ASSET_PREFIXES = [
        '111.101', '111.102', '111.103', '111.105',
        '111.200', '111.300', '111.400',
    ];

    private const CURRENT_LIABILITY_PREFIXES = [
        '211.100', '211.200', '211.300',
        '211.400', '211.500', '212.100', '222.000',
    ];

    private const FIXED_ASSET_CATEGORY = 'FixedAsset';

    private const REVENUE_PREFIX = '421';

    private const EXPENSE_PREFIX = '721';

    private const OTHER_INCOME_PREFIX = '800';

    private const OTHER_EXPENSE_PREFIX = '900';

    private const ASSET_PREFIXES = ['111', '112', '121'];

    private const LIABILITY_PREFIXES = ['211', '212', '221'];

    private const EQUITY_PREFIXES = ['311', '312', '313', '314'];

    private const RECEIVABLE_PREFIXES = ['111.200', '112.100'];

    private const OPERATING_EXPENSE_EXCLUDED_CODES = [
        '721.000.201A', '721.000.201B', '721.000.201C', '721.000.201D',
        '721.000.251', '721.000.259', '721.000.260',
        '721.000.268', '721.000.269', '721.000.270',
    ];

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
                'total_expense' => 0.0,
                'other_expense' => 0.0,
                'operating_expense' => 0.0,
                'total_expense_excluded' => 0.0,
                'aktiva_tetap' => 0.0,
                'total_asset' => 0.0,
                'liabilitas' => 0.0,
                'equitas' => 0.0,
                'piutang' => 0.0,
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
            $category = (string) ($row['Category'] ?? '');

            $prefix7 = substr($accountCode, 0, 7);
            $prefix3 = substr($accountCode, 0, 3);

            if (in_array($prefix7, self::CURRENT_ASSET_PREFIXES, true) || $accountCode === '121.300.101') {
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

            if ($prefix3 === self::EXPENSE_PREFIX) {
                $monthly[$key]['total_expense'] += $ending;

                $isExcluded = false;
                foreach (self::OPERATING_EXPENSE_EXCLUDED_CODES as $excludedCode) {
                    if ($accountCode === $excludedCode) {
                        $isExcluded = true;
                        break;
                    }
                }

                if ($isExcluded) {
                    $monthly[$key]['total_expense_excluded'] += $ending;
                } else {
                    $monthly[$key]['operating_expense'] += $ending;
                }
            }

            if ($prefix3 === self::OTHER_EXPENSE_PREFIX) {
                $monthly[$key]['other_expense'] += $ending;
            }

            if ($category === self::FIXED_ASSET_CATEGORY) {
                $monthly[$key]['aktiva_tetap'] += $ending;
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

            foreach (self::RECEIVABLE_PREFIXES as $receivablePrefix) {
                if (substr($accountCode, 0, strlen($receivablePrefix)) === $receivablePrefix) {
                    $monthly[$key]['piutang'] += $ending;
                    break;
                }
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
        $opexRows = [];
        $darRows = [];
        $derRows = [];
        $receivableTurnoverRows = [];

        $no = 0;
        foreach ($monthlyData as $key => $data) {
            $no++;
            $period = $data['period'];
            $monthNum = (int) $period->format('n');
            $bulan = $monthNames[$monthNum] ?? $period->locale('id')->isoFormat('MMMM');

            $aktivaLancar = $data['aktiva_lancar'];
            $hutangLancar = $data['hutang_lancar'];
            $pendapatan = $data['pendapatan'];
            $labaBersih = $pendapatan + $data['other_income'] - $data['total_expense'] - $data['other_expense'];
            $operatingExpense = $data['operating_expense'];
            $aktivaTetap = $data['aktiva_tetap'];
            $totalAsset = $data['total_asset'];
            $liabilitas = $data['liabilitas'];
            $equitas = $data['equitas'];
            $piutang = $data['piutang'];
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
                'nilai_y' => $aktivaTetap,
                'rasio' => $aktivaTetap != 0 ? ($labaBersih / $aktivaTetap) * 100 : 0,
            ];

            $opexRows[] = [
                'no' => $no,
                'bulan' => $bulan,
                'nilai_x' => $operatingExpense,
                'nilai_y' => $pendapatan,
                'rasio' => $pendapatan != 0 ? ($operatingExpense / $pendapatan) * 100 : 0,
            ];

            $darRows[] = [
                'no' => $no,
                'bulan' => $bulan,
                'nilai_x' => $liabilitas,
                'nilai_y' => $totalAsset,
                'rasio' => $totalAsset != 0 ? ($liabilitas / $totalAsset) * 100 : 0,
            ];

            $derRows[] = [
                'no' => $no,
                'bulan' => $bulan,
                'nilai_x' => $liabilitas,
                'nilai_y' => $equityCalc,
                'rasio' => $equityCalc != 0 ? ($liabilitas / $equityCalc) * 100 : 0,
            ];

            $receivableTurnoverRows[] = [
                'no' => $no,
                'bulan' => $bulan,
                'nilai_x' => $pendapatan,
                'nilai_y' => $piutang,
                'rasio' => $piutang != 0 ? ($pendapatan / $piutang) * 100 : 0,
            ];
        }

        return [
            [
                'id' => 'current_ratio',
                'title' => 'Current Ratio',
                'description' => 'Rasio yang mengukur kemampuan perusahaan dalam membayar kewajiban jangka pendek dengan aktiva lancar yang tersedia.',
                'footer_note' => 'Semakin besar perbandingan aktiva lancar dengan utang lancar, semakin tinggi kemampuan perusahaan menutupi kewajiban jangka pendeknya. <strong>Jadi dikatakan sehat jika rasionya berada di atas 100%.</strong>',
                'columns' => ['No', 'Bulan', 'Nilai Aktiva Lancar', 'Nilai Hutang Lancar', 'Rasio %'],
                'col_x_label' => 'Nilai Aktiva Lancar',
                'col_y_label' => 'Nilai Hutang Lancar',
                'rows' => $currentRatioRows,
            ],
            [
                'id' => 'ros',
                'title' => 'Return On Sales (ROS)',
                'description' => 'Return on Sales adalah rasio keuangan yang bertujuan untuk mengukur seberapa efisien perusahaan menghasilkan laba dari penjualan yang didapat. Angka ROS bisa memberikan informasi berapa banyak keuntungan yang dihasilkan oleh sebuah perusahaan setelah membayar biaya variabel produksi seperti upah, bahan baku dan lain-lainnya.',
                'footer_note' => '<strong>Semakin tinggi nilai persentase rasio maka semakin baik.</strong>',
                'columns' => ['No', 'Bulan', 'Nilai Laba Bersih', 'Nilai Pendapatan', 'Rasio %'],
                'col_x_label' => 'Nilai Laba Bersih',
                'col_y_label' => 'Nilai Pendapatan',
                'rows' => $rosRows,
            ],
            [
                'id' => 'roa',
                'title' => 'Return On Asset (ROA)',
                'description' => 'Tingkat pengembalian aset merupakan rasio profitabilitas untuk menilai persentase keuntungan (laba) yang diperoleh perusahaan terkait sumber daya atau total fixed aset sehingga efisiensi suatu perusahaan dalam mengelola asetnya bisa terlihat dari persentase rasio ini.',
                'footer_note' => 'Keterangan: < 1% Tidak baik, > 1% Baik',
                'columns' => ['No', 'Bulan', 'Nilai Laba Bersih', 'Nilai Aktiva Tetap', 'Rasio %'],
                'col_x_label' => 'Nilai Laba Bersih',
                'col_y_label' => 'Nilai Aktiva Tetap',
                'rows' => $roaRows,
            ],
            [
                'id' => 'opex_ratio',
                'title' => 'Opex Ratio',
                'description' => 'Opex ratio merupakan rasio untuk mengukur tingkat biaya operasional perusahaan terhadap penjualan.',
                'footer_note' => '<strong>Semakin rendah nilai persentase rasio maka semakin baik.</strong>',
                'columns' => ['No', 'Bulan', 'Nilai Operating Expense', 'Nilai Sales', 'Rasio %'],
                'col_x_label' => 'Nilai Operating Expense',
                'col_y_label' => 'Nilai Sales',
                'rows' => $opexRows,
            ],
            [
                'id' => 'dar',
                'title' => 'Debt To Asset (DAR)',
                'description' => 'Menurut Kasmir Debt to Asset Ratio juga bisa digunakan untuk mengukur seberapa besar aset perusahaan dibiayai oleh utang. Rasio ini sangat penting guna melihat solvabilitas perusahaan atau kemampuan untuk menyelesaikan segala kewajiban jangka panjang. Namun, secara garis besar nilai DAR dapat diklasifikasikan sebagai berikut: DAR < 0,5 : Artinya mayoritas aset perusahaan tersebut didanai oleh modal (equitas) perusahaan sendiri dan bukan dari pinjaman. DAR > 0,5 : Artinya mayoritas aset perusahaan berasal dari utang. DAR = 0,6-0,7 : Artinya mayoritas aset perusahaan berasal dari utang tapi masih dalam batas kewajaran.',
                'footer_note' => 'Liabilitas sendiri dapat diartikan sebagai hutang yang mesti dilunasi pihak lain di masa datang. Keduanya, baik liabilitas maupun aset, sama-sama diambil dari nilai totalnya. Maka dapat disimpulkan aset haruslah dari hasil penjumlahan aset lancar dan aset tidak lancar. Liabilitas juga diambil dari liabilitas jangka pendek dan liabilitas jangka panjang.',
                'columns' => ['No', 'Bulan', 'Nilai Liabilitas', 'Nilai Total Asset', 'Rasio %'],
                'col_x_label' => 'Nilai Liabilitas',
                'col_y_label' => 'Nilai Total Asset',
                'rows' => $darRows,
            ],
            [
                'id' => 'der',
                'title' => 'Debt To Equity',
                'description' => 'Pengertian dari Debt to Equity Ratio (DER) adalah sebuah rasio keuangan yang membandingkan jumlah hutang dengan ekuitas. Ekuitas dan jumlah hutang yang digunakan untuk operasional perusahaan harus berada dalam jumlah yang proporsional. DER > 100%, maka hutang lebih besar dari ekuitas, artinya terlalu berisiko. DER < 100%, maka hutang lebih kecil dari pada ekuitas, artinya aman. Nilai maksimal dari rasio ini adalah 200% sebagai batas aman perusahaan memenuhi kewajiban jangka panjang.',
                'footer_note' => '',
                'columns' => ['No', 'Bulan', 'Nilai Hutang', 'Nilai Equitas', 'Rasio %'],
                'col_x_label' => 'Nilai Hutang',
                'col_y_label' => 'Nilai Equitas',
                'rows' => $derRows,
            ],
            [
                'id' => 'receivable_turnover',
                'title' => 'Rasio Perputaran Piutang (Receivable Turnover)',
                'description' => 'Rasio perputaran piutang digunakan untuk mengukur kualitas dan efisiensi tingkat perputaran piutang perusahaan dalam satu periode dengan membandingkan penjualan dengan rata-rata piutang.',
                'footer_note' => '<strong>Target rasio dikatakan baik apabila >= 100%.</strong>',
                'columns' => ['No', 'Bulan', 'Penjualan', 'Nilai Piutang Rata-rata', 'Rasio %'],
                'col_x_label' => 'Penjualan',
                'col_y_label' => 'Nilai Piutang Rata-rata',
                'rows' => $receivableTurnoverRows,
            ],
        ];
    }
}
