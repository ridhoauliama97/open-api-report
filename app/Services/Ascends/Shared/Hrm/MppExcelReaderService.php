<?php

namespace App\Services\Ascends\Shared\Hrm;

use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

class MppExcelReaderService
{
    private const COMPANY_COLUMNS = ['UC', 'GSU', 'RU'];

    private const COMPANY_ALIASES = [
        'UC' => ['UC', 'Uc', 'uc'],
        'GSU' => ['GSU', 'Gsu', 'gsu'],
        'RU' => ['RU', 'Ru', 'ru'],
    ];

    /**
     * @var array<int, array{tanggal: string, values: array<string, int>}>|null
     */
    private ?array $cachedData = null;

    public function getMpp(string $company, int $year, int $month): int
    {
        $company = self::normalizeCompany($company);
        $data = $this->loadData();

        $targetDate = sprintf('%04d-%02d', $year, $month);

        $bestMatch = null;
        foreach ($data as $entry) {
            if ($entry['tanggal'] <= $targetDate) {
                $bestMatch = $entry;
            } else {
                break;
            }
        }

        if ($bestMatch === null) {
            throw new RuntimeException("Tidak ada data MPP untuk {$company} pada {$targetDate}.");
        }

        return $bestMatch['values'][$company] ?? throw new RuntimeException("Kolom MPP untuk {$company} tidak ditemukan.");
    }

    private function loadData(): array
    {
        if ($this->cachedData !== null) {
            return $this->cachedData;
        }

        $filePath = config('app.mpp_excel_path', '');

        if ($filePath === '') {
            throw new RuntimeException('Konfigurasi MPP_EXCEL_PATH tidak ditemukan.');
        }

        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw new RuntimeException("File MPP tidak ditemukan atau tidak bisa dibaca: {$filePath}");
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            $spreadsheet->disconnectWorksheets();
        } catch (Throwable $e) {
            throw new RuntimeException("Gagal membaca file MPP: {$e->getMessage()}");
        }

        if (count($rows) < 2) {
            throw new RuntimeException('File MPP tidak memiliki data.');
        }

        $headerRow = array_map('trim', (array) ($rows[0] ?? []));
        $headerMap = $this->buildHeaderMap($headerRow);

        $data = [];
        for ($i = 1, $iMax = count($rows); $i < $iMax; $i++) {
            $row = array_values((array) ($rows[$i] ?? []));

            $rawDate = trim((string) ($row[0] ?? ''));
            if ($rawDate === '') {
                continue;
            }

            $formattedDate = $this->formatDate($rawDate);
            if ($formattedDate === null) {
                continue;
            }

            $values = [];
            foreach (self::COMPANY_COLUMNS as $company) {
                $colIndex = $headerMap[$company] ?? null;
                if ($colIndex !== null) {
                    $rawValue = trim((string) ($row[$colIndex] ?? ''));
                    $values[$company] = $rawValue !== '' && is_numeric($rawValue) ? (int) $rawValue : 0;
                }
            }

            $data[] = [
                'tanggal' => $formattedDate,
                'values' => $values,
            ];
        }

        if ($data === []) {
            throw new RuntimeException('Tidak ada baris data MPP yang valid.');
        }

        usort($data, static fn (array $a, array $b): int => strcmp($a['tanggal'], $b['tanggal']));

        $this->cachedData = $data;

        return $data;
    }

    /**
     * @param  array<int, string>  $headerRow
     * @return array<string, int>
     */
    private function buildHeaderMap(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $index => $header) {
            $normalized = strtolower(str_replace([' ', '_x0020_', '_', '-'], '', $header));
            $map[$normalized] = $index;

            foreach (self::COMPANY_COLUMNS as $company) {
                if (in_array($header, self::COMPANY_ALIASES[$company], true)) {
                    $map[strtolower($company)] = $index;
                }
            }
        }

        $result = [];
        foreach (self::COMPANY_COLUMNS as $company) {
            $key = strtolower($company);
            if (isset($map[$key])) {
                $result[$company] = $map[$key];
            }
        }

        return $result;
    }

    private static function formatDate(string $value): ?string
    {
        if (preg_match('/^\d{4}-\d{2}/', $value)) {
            return substr($value, 0, 7);
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $value)) {
            $parts = explode('/', $value);

            return $parts[2].'-'.$parts[1];
        }

        if (is_numeric($value)) {
            $excelTimestamp = (int) $value;
            $unixTime = ($excelTimestamp - 25569) * 86400;

            return date('Y-m', $unixTime);
        }

        if (preg_match('/^\d{1,2}-[A-Za-z]{3}-\d{2}$/', $value)) {
            $dt = \DateTime::createFromFormat('j-M-y', $value);

            return $dt !== false ? $dt->format('Y-m') : null;
        }

        return null;
    }

    private static function normalizeCompany(string $company): string
    {
        $upper = strtoupper(trim($company));

        return in_array($upper, ['UC', 'GSU', 'RU'], true) ? $upper : $company;
    }
}
