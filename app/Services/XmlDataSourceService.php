<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SimpleXMLElement;

class XmlDataSourceService
{
    private string $disk;

    private string $baseFolder;

    public function __construct()
    {
        $this->disk = (string) config('app.pdf_storage_disk', 'local');
        $this->baseFolder = 'xml_sources';
    }

    // =========================================================================
    // Sub-Report API — berbasis Company
    // =========================================================================

    /**
     * Load sub-report berdasarkan company dan module.
     *
     * Config dibaca dari:  config/xml_reports/{company}/{module}.php
     * File XML dibaca dari: storage/app/xml_sources/{company}/{module}/...
     *
     * Contoh pemanggilan:
     *   $service->loadSubReport('RU', 'hrm', 'employee_list');
     *   $service->loadSubReport('GSUT', 'hrm', 'employee_biodata');
     *
     * @param  string  $company  Kode perusahaan, contoh: 'RU', 'GSUT'
     * @param  string  $module  Nama modul/config, contoh: 'hrm', 'finance'
     * @param  string  $subReportKey  Key sub-report dalam config, contoh: 'employee_list'
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function loadSubReport(string $company, string $module, string $subReportKey): array
    {
        // 1. Baca config: config/xml_reports/{company}/{module}.php
        $configKey = "xml_reports.{$company}.{$module}";
        $moduleConfig = config($configKey);

        if (! is_array($moduleConfig)) {
            throw new RuntimeException(
                "Konfigurasi tidak ditemukan: config/xml_reports/{$company}/{$module}.php"
            );
        }

        // 2. Cari sub-report yang diminta
        $subReports = $moduleConfig['sub_reports'] ?? [];
        if (! isset($subReports[$subReportKey]) || ! is_array($subReports[$subReportKey])) {
            $available = implode(', ', array_keys($subReports));
            throw new RuntimeException(
                "Sub-report '{$subReportKey}' tidak ada di [{$company}/{$module}]. Tersedia: {$available}"
            );
        }

        $subConfig = $subReports[$subReportKey];
        $recordTag = (string) ($moduleConfig['record_tag'] ?? 'Employees');
        $xmlSource = (string) ($moduleConfig['xml_source'] ?? '');

        // 3. Parse XML
        $allRecords = $this->loadRecordsFromXml($xmlSource, $recordTag);

        // 4. Filter baris (opsional)
        $filter = $subConfig['filter'] ?? null;
        if (is_array($filter)) {
            $allRecords = $this->applyFilter($allRecords, $filter);
        }

        // 5. Pilih & rename kolom
        /** @var array<string, string> $columnMap */
        $columnMap = $subConfig['columns'] ?? [];
        $rows = $this->projectColumns($allRecords, $columnMap);

        return [
            'printed_at' => Carbon::now()->translatedFormat('d F Y H:i'),
            'company' => strtoupper($company),
            'module' => $module,
            'sub_report' => $subReportKey,
            'label' => (string) ($subConfig['label'] ?? $subReportKey),
            'headers' => array_values($columnMap),
            'rows' => $rows,
            'total_rows' => count($rows),
        ];
    }

    /**
     * Load sub-report dari XML yang dikirim langsung lewat request API.
     *
     * @return array<string, mixed>
     */
    public function loadSubReportFromXmlContents(
        string $company,
        string $module,
        string $subReportKey,
        string $xmlContents,
        string $sourceLabel = 'request xml payload',
    ): array {
        $moduleConfig = $this->resolveModuleConfig($company, $module);
        $subConfig = $this->resolveSubReportConfig($moduleConfig, $company, $module, $subReportKey);

        $recordTag = (string) ($moduleConfig['record_tag'] ?? 'Employees');
        $records = $this->parseRecordsFromXmlContents($xmlContents, $recordTag, $sourceLabel);

        $filter = $subConfig['filter'] ?? null;
        if (is_array($filter)) {
            $records = $this->applyFilter($records, $filter);
        }

        /** @var array<string, string> $columnMap */
        $columnMap = $subConfig['columns'] ?? [];

        return [
            'printed_at' => Carbon::now()->translatedFormat('d F Y H:i'),
            'company' => strtoupper($company),
            'module' => $module,
            'sub_report' => $subReportKey,
            'label' => (string) ($subConfig['label'] ?? $subReportKey),
            'headers' => array_values($columnMap),
            'rows' => $this->projectColumns($records, $columnMap),
            'total_rows' => count($records),
            'source_file' => $sourceLabel,
        ];
    }

    /**
     * Daftar semua modul yang terdaftar untuk sebuah company.
     *
     * @return string[] ['hrm', 'finance', ...]
     */
    public function availableModules(string $company): array
    {
        $config = config("xml_reports.{$company}");
        if (! is_array($config)) {
            return [];
        }

        return array_keys($config);
    }

    /**
     * Daftar sub-report yang tersedia untuk sebuah company + modul.
     *
     * @return array<string, string> ['employee_list' => 'Daftar Karyawan', ...]
     */
    public function availableSubReports(string $company, string $module): array
    {
        $moduleConfig = config("xml_reports.{$company}.{$module}");
        if (! is_array($moduleConfig)) {
            return [];
        }

        $result = [];
        foreach ($moduleConfig['sub_reports'] ?? [] as $key => $cfg) {
            $result[$key] = (string) ($cfg['label'] ?? $key);
        }

        return $result;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function loadModuleRecords(string $company, string $module): array
    {
        $moduleConfig = $this->resolveModuleConfig($company, $module);

        $recordTag = (string) ($moduleConfig['record_tag'] ?? 'Employees');
        $xmlSource = (string) ($moduleConfig['xml_source'] ?? '');

        return $this->loadRecordsFromXml($xmlSource, $recordTag);
    }

    // =========================================================================
    // Load by explicit path
    // =========================================================================

    /**
     * Load dan parse file XML dari storage berdasarkan path eksplisit.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function load(string $relativePath): array
    {
        $relativePath = $this->normalizePath($relativePath);
        $fullPath = $this->baseFolder.'/'.$relativePath;

        if (! $this->fileExists($fullPath)) {
            throw new RuntimeException("File XML tidak ditemukan: {$relativePath}");
        }

        $contents = $this->readContents($fullPath);

        if (! is_string($contents) || trim($contents) === '') {
            throw new RuntimeException("File XML kosong atau tidak dapat dibaca: {$relativePath}");
        }

        return $this->parse($contents, $relativePath);
    }

    /**
     * Load XML dan validasi field wajib.
     *
     * @param  string[]  $requiredFields
     * @return array<string, mixed>
     */
    public function loadAndValidate(string $relativePath, array $requiredFields = []): array
    {
        $data = $this->load($relativePath);
        $missing = array_values(
            array_filter($requiredFields, static fn (string $f): bool => ! array_key_exists($f, $data))
        );

        if (! empty($missing)) {
            throw new RuntimeException(sprintf(
                'File XML "%s" tidak memiliki field: %s',
                $relativePath,
                implode(', ', $missing)
            ));
        }

        return $data;
    }

    // =========================================================================
    // Load by date
    // =========================================================================

    /**
     * Load file XML berdasarkan tanggal dan tipe report.
     *
     * @param  string[]  $requiredFields
     * @return array<string, mixed>
     */
    public function loadByDate(
        string $subFolder,
        string $reportType,
        \DateTimeInterface|string|null $date = null,
        string $dateFormat = 'Y_m_d',
        array $requiredFields = [],
    ): array {
        $carbon = $this->resolveDate($date);
        $resolvedPath = $this->resolveDatePath($subFolder, $reportType, $carbon, $dateFormat);

        return ! empty($requiredFields)
            ? $this->loadAndValidate($resolvedPath, $requiredFields)
            : $this->load($resolvedPath);
    }

    /**
     * Cari file XML yang tersedia dalam rentang tanggal.
     *
     * @return array<string, string>
     */
    public function listAvailableByDateRange(
        string $subFolder,
        string $reportType,
        \DateTimeInterface|string $dateFrom,
        \DateTimeInterface|string $dateTo,
        string $dateFormat = 'Y_m_d',
    ): array {
        $from = $this->resolveDate($dateFrom);
        $to = $this->resolveDate($dateTo);

        if ($from->isAfter($to)) {
            [$from, $to] = [$to, $from];
        }

        $available = [];
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $path = $this->resolveDatePath($subFolder, $reportType, $cursor, $dateFormat);
            if ($this->exists($path)) {
                $available[$cursor->toDateString()] = $path;
            }
            $cursor->addDay();
        }

        return $available;
    }

    /**
     * Ambil file XML terbaru dalam subfolder.
     *
     * @return array<string, mixed>
     */
    public function loadLatest(string $subFolder, string $reportType): array
    {
        $allFiles = $this->list($subFolder);
        $prefix = $reportType.'_';
        $matching = array_values(array_filter(
            $allFiles,
            static fn (string $p): bool => str_contains(basename($p), $prefix)
        ));

        if (empty($matching)) {
            throw new RuntimeException(
                "Tidak ada file XML tipe '{$reportType}' di '{$subFolder}'."
            );
        }

        rsort($matching);

        return $this->load($matching[0]);
    }

    // =========================================================================
    // Utility
    // =========================================================================

    public function exists(string $relativePath): bool
    {
        $relativePath = $this->normalizePath($relativePath);

        return $this->fileExists($this->baseFolder.'/'.$relativePath);
    }

    /**
     * @return string[]
     */
    public function list(string $subFolder = ''): array
    {
        $scanPath = $this->baseFolder.($subFolder !== '' ? '/'.trim($subFolder, '/') : '');
        $files = Storage::disk($this->disk)->files($scanPath);

        if ($files === []) {
            $fallbackRoot = $this->fallbackStoragePath(trim($scanPath, '/'));
            if (is_dir($fallbackRoot)) {
                $fallbackFiles = glob($fallbackRoot.DIRECTORY_SEPARATOR.'*.xml') ?: [];
                $files = array_map(function (string $file): string {
                    $normalized = str_replace('\\', '/', $file);
                    $storageRoot = str_replace('\\', '/', storage_path('app')).'/';

                    return str_starts_with($normalized, $storageRoot)
                        ? substr($normalized, strlen($storageRoot))
                        : $normalized;
                }, $fallbackFiles);
            }
        }

        return array_values(array_map(
            fn (string $file): string => ltrim(substr($file, strlen($this->baseFolder)), '/'),
            array_filter($files, static fn (string $f): bool => str_ends_with(strtolower($f), '.xml'))
        ));
    }

    /**
     * @return array{size: int|false, last_modified: int|false, path: string}
     */
    public function meta(string $relativePath): array
    {
        $relativePath = $this->normalizePath($relativePath);
        $fullPath = $this->baseFolder.'/'.$relativePath;

        if ($this->fileExistsOnDisk($fullPath)) {
            return [
                'path' => $relativePath,
                'size' => Storage::disk($this->disk)->size($fullPath),
                'last_modified' => Storage::disk($this->disk)->lastModified($fullPath),
            ];
        }

        $fallbackPath = $this->fallbackStoragePath($fullPath);

        return [
            'path' => $relativePath,
            'size' => is_file($fallbackPath) ? filesize($fallbackPath) : false,
            'last_modified' => is_file($fallbackPath) ? filemtime($fallbackPath) : false,
        ];
    }

    /**
     * Decode nama field XML (Microsoft DataSet) ke label yang bisa dibaca.
     * Contoh: 'Full_x0020_Name' → 'Full Name'
     */
    public static function decodeFieldName(string $xmlFieldName): string
    {
        return (string) preg_replace_callback(
            '/_x([0-9A-Fa-f]{4})_/',
            static fn (array $m): string => mb_chr((int) hexdec($m[1]), 'UTF-8') ?: '_',
            $xmlFieldName
        );
    }

    // =========================================================================
    // Internal — Sub-report helpers
    // =========================================================================

    /**
     * @return array<int, array<string, string>>
     */
    private function loadRecordsFromXml(string $relativePath, string $recordTag): array
    {
        $relativePath = $this->normalizePath($relativePath);
        $fullPath = $this->baseFolder.'/'.$relativePath;

        if (! $this->fileExists($fullPath)) {
            throw new RuntimeException("File XML sumber tidak ditemukan: {$relativePath}");
        }

        $contents = $this->readContents($fullPath);
        if (! is_string($contents) || trim($contents) === '') {
            throw new RuntimeException("File XML kosong: {$relativePath}");
        }

        return $this->parseRecordsFromXmlContents($contents, $recordTag, $relativePath);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseRecordsFromXmlContents(string $contents, string $recordTag, string $context): array
    {
        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contents, SimpleXMLElement::class, LIBXML_NOCDATA);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        if ($xml === false) {
            $message = $errors !== []
                ? trim((string) ($errors[0]->message ?? ''))
                : 'format XML tidak valid';

            throw new RuntimeException("File XML tidak valid ({$context}): {$message}");
        }

        $records = [];
        /** @var SimpleXMLElement $node */
        foreach ($this->recordNodes($xml, $recordTag) as $node) {
            $row = [];
            foreach ($node->children() as $fieldName => $fieldValue) {
                $row[$fieldName] = trim((string) $fieldValue);
            }
            $records[] = $row;
        }

        return $records;
    }

    /**
     * @return SimpleXMLElement[]
     */
    private function recordNodes(SimpleXMLElement $xml, string $recordTag): array
    {
        $nodes = [];
        $expected = strtolower($recordTag);

        /** @var SimpleXMLElement $node */
        foreach ($xml->children() as $nodeName => $node) {
            if (strtolower((string) $nodeName) === $expected) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveModuleConfig(string $company, string $module): array
    {
        $configKey = "xml_reports.{$company}.{$module}";
        $moduleConfig = config($configKey);

        if (! is_array($moduleConfig)) {
            throw new RuntimeException(
                "Konfigurasi tidak ditemukan: config/xml_reports/{$company}/{$module}.php"
            );
        }

        return $moduleConfig;
    }

    /**
     * @param  array<string, mixed>  $moduleConfig
     * @return array<string, mixed>
     */
    private function resolveSubReportConfig(array $moduleConfig, string $company, string $module, string $subReportKey): array
    {
        $subReports = $moduleConfig['sub_reports'] ?? [];
        if (! isset($subReports[$subReportKey]) || ! is_array($subReports[$subReportKey])) {
            $available = implode(', ', array_keys($subReports));
            throw new RuntimeException(
                "Sub-report '{$subReportKey}' tidak ada di [{$company}/{$module}]. Tersedia: {$available}"
            );
        }

        return $subReports[$subReportKey];
    }

    /**
     * @param  array<int, array<string, string>>  $records
     * @param  array<string, string>  $filter
     * @return array<int, array<string, string>>
     */
    private function applyFilter(array $records, array $filter): array
    {
        return array_values(array_filter(
            $records,
            static function (array $row) use ($filter): bool {
                foreach ($filter as $field => $expected) {
                    if (($row[$field] ?? '') !== $expected) {
                        return false;
                    }
                }

                return true;
            }
        ));
    }

    /**
     * @param  array<int, array<string, string>>  $records
     * @param  array<string, string>  $columnMap  xml_key => label
     * @return array<int, array<string, string>>
     */
    private function projectColumns(array $records, array $columnMap): array
    {
        return array_map(static function (array $row) use ($columnMap): array {
            $projected = [];
            foreach ($columnMap as $xmlKey => $label) {
                $projected[$label] = $row[$xmlKey] ?? '';
            }

            return $projected;
        }, $records);
    }

    private function fileExists(string $fullPath): bool
    {
        return $this->fileExistsOnDisk($fullPath) || is_file($this->fallbackStoragePath($fullPath));
    }

    private function fileExistsOnDisk(string $fullPath): bool
    {
        return Storage::disk($this->disk)->exists($fullPath);
    }

    private function readContents(string $fullPath): ?string
    {
        if ($this->fileExistsOnDisk($fullPath)) {
            $contents = Storage::disk($this->disk)->get($fullPath);

            return is_string($contents) ? $contents : null;
        }

        $fallbackPath = $this->fallbackStoragePath($fullPath);
        if (! is_file($fallbackPath)) {
            return null;
        }

        $contents = file_get_contents($fallbackPath);

        return is_string($contents) ? $contents : null;
    }

    private function fallbackStoragePath(string $fullPath): string
    {
        return storage_path('app/'.ltrim(str_replace('\\', '/', $fullPath), '/'));
    }

    // =========================================================================
    // Internal — General helpers
    // =========================================================================

    private function resolveDate(\DateTimeInterface|string|null $date): Carbon
    {
        if ($date === null) {
            return Carbon::today();
        }
        if ($date instanceof Carbon) {
            return $date->copy();
        }
        if ($date instanceof \DateTimeInterface) {
            return Carbon::instance($date);
        }
        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            throw new RuntimeException("Format tanggal tidak valid: '{$date}'.");
        }
    }

    private function resolveDatePath(string $subFolder, string $reportType, Carbon $date, string $dateFormat): string
    {
        return trim($subFolder, '/').'/'.trim($reportType, '_').'_'.$date->format($dateFormat).'.xml';
    }

    /**
     * @return array<string, mixed>
     */
    private function parse(string $xmlContents, string $context = ''): array
    {
        if (str_starts_with($xmlContents, "\xEF\xBB\xBF")) {
            $xmlContents = substr($xmlContents, 3);
        }

        libxml_use_internal_errors(true);
        $element = simplexml_load_string($xmlContents, SimpleXMLElement::class, LIBXML_NOCDATA);

        if ($element === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            $messages = array_map(static fn (\LibXMLError $e): string => trim($e->message), $errors);
            throw new RuntimeException(sprintf(
                'XML tidak valid%s: %s',
                $context !== '' ? " ({$context})" : '',
                implode('; ', $messages) ?: 'Unknown parse error'
            ));
        }

        libxml_clear_errors();

        return $this->xmlToArray($element);
    }

    /**
     * @return array<string, mixed>
     */
    private function xmlToArray(SimpleXMLElement $element): array
    {
        $result = [];

        foreach ($element->attributes() as $attrName => $attrValue) {
            $result['@'.$attrName] = (string) $attrValue;
        }

        /** @var SimpleXMLElement $child */
        foreach ($element->children() as $childName => $child) {
            $childArray = $this->xmlToArray($child);

            if ($childArray === [] || (count($childArray) === 1 && isset($childArray['_value']))) {
                $childArray = (string) $child;
            }

            if (! isset($result[$childName])) {
                $result[$childName] = $childArray;
            } elseif (is_array($result[$childName]) && isset($result[$childName][0])) {
                $result[$childName][] = $childArray;
            } else {
                $result[$childName] = [$result[$childName], $childArray];
            }
        }

        $textContent = trim((string) $element);
        if ($textContent !== '' && empty($result)) {
            return ['_value' => $textContent];
        }

        return $result;
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        if ($path === '') {
            throw new RuntimeException('Path file XML tidak boleh kosong.');
        }
        if (str_contains($path, '..')) {
            throw new RuntimeException("Path XML mengandung karakter yang tidak diizinkan: {$path}");
        }

        return $path;
    }
}
