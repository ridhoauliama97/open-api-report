<?php

namespace App\Services\Ascends\Ru\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class DataPesertaMakanSiangShalatJumatPerDepartemenReportService
{
    private const TITLE = 'Laporan Data Peserta Penerima Makan Siang Shalat Jumat Per Departemen';

    private const RU_DEPARTMENT_ORDER = [
        'PKB & SML' => 1,
        'VKD' => 2,
        'Borongan' => 3,
        'PHI' => 4,
        'PHU & KRUT' => 5,
    ];

    private const GSU_DEPARTMENT_ORDER = [
        'WNB' => 1,
        'WHS' => 2,
        'Broker Kecil & Besar' => 3,
        'Marketing' => 4,
        'Regu A' => 5,
        'Regu B' => 6,
        'Regu C' => 7,
        'Broker Pagi' => 8,
        'Broker Sore' => 9,
        'Broker Malam' => 10,
        'PIN HULU' => 11,
        'PIN HILIR' => 12,
        'SSWE' => 99,
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $company = strtoupper(trim((string) ($filters['company'] ?? $filters['DB_CompanyName'] ?? 'GSU')));
        $requestedPeriod = $this->resolvePeriodFromFilters($filters);
        $scan = $this->scanAttendanceRows($xmlContents, $sourceLabel, $requestedPeriod, $company);
        $period = $requestedPeriod ?? $this->periodFromXmlDates($scan['min_date'], $scan['max_date']);
        $dates = $this->resolveReportDates($period);
        $departments = $this->buildDepartments($scan['departments'], $dates, $company);

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $scan['printed_by'],
            'period' => [
                'start_date' => $period['start']->toDateString(),
                'end_date' => $period['end']->toDateString(),
                'label' => 'Per '.$period['start']->locale('id')->translatedFormat('F Y'),
            ],
            'dates' => array_map(
                static fn (Carbon $date, int $index): array => [
                    'index' => $index + 1,
                    'date' => $date->toDateString(),
                    'label' => ($index + 1).' ('.$date->locale('id')->translatedFormat('d-M-y').')',
                ],
                $dates,
                array_keys($dates)
            ),
            'departments' => $departments,
            'headers' => ['No', 'Nama'],
            'rows' => $departments,
            'total_rows' => array_sum(array_map(static fn (array $department): int => count($department['rows'] ?? []), $departments)),
        ];
    }

    /**
     * @param  array{start: Carbon, end: Carbon}|null  $requestedPeriod
     * @return array{departments: array<string, array<string, mixed>>, min_date: Carbon|null, max_date: Carbon|null, printed_by: string}
     */
    private function scanAttendanceRows(string $xmlContents, string $sourceLabel, ?array $requestedPeriod, string $company): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML Attendance Full kosong.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML Attendance Full tidak valid: {$sourceLabel}");
        }

        $hasAttendanceRecord = false;
        $departments = [];
        $minDate = null;
        $maxDate = null;
        $printedBy = '';

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'attendance') {
                continue;
            }

            $hasAttendanceRecord = true;
            $row = $this->readAttendanceRow($reader);
            if ($row === []) {
                continue;
            }

            if ($printedBy === '') {
                $printedBy = $this->resolvePrintedByFromRow($row);
            }

            $date = $this->parseDate((string) ($row['Date'] ?? ''));
            if ($date !== null) {
                if ($minDate === null || $date->lessThan($minDate)) {
                    $minDate = $date->copy();
                }
                if ($maxDate === null || $date->greaterThan($maxDate)) {
                    $maxDate = $date->copy();
                }
            }

            if (
                $date === null
                || ($requestedPeriod !== null && ! $date->betweenIncluded($requestedPeriod['start'], $requestedPeriod['end']))
            ) {
                continue;
            }

            $this->aggregateDepartmentRow($departments, $row, $date, $company);
        }

        $reader->close();

        if (! $hasAttendanceRecord) {
            throw new RuntimeException('XML Attendance Full tidak memiliki record Attendance.');
        }

        return [
            'departments' => $departments,
            'min_date' => $minDate,
            'max_date' => $maxDate,
            'printed_by' => $printedBy,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function readAttendanceRow(XMLReader $reader): array
    {
        $recordXml = $reader->readOuterXML();
        if (! is_string($recordXml) || trim($recordXml) === '') {
            return [];
        }

        $node = @simplexml_load_string($recordXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($node === false) {
            return [];
        }

        $row = json_decode(json_encode($node), true) ?: [];

        return array_map(
            static fn (mixed $value): string => is_array($value) ? '' : trim((string) $value),
            $row
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $departments
     * @param  array<string, string>  $row
     */
    private function aggregateDepartmentRow(array &$departments, array $row, Carbon $date, string $company): void
    {
        $employeeCode = trim((string) ($row['Employee_x0020_Code'] ?? ''));
        $employeeName = trim((string) ($row['Full_x0020_Name'] ?? ''));
        if (
            $employeeCode === ''
            || $employeeName === ''
            || str_starts_with(strtoupper($employeeCode), 'SPECIAL')
            || ! $this->shouldDisplayRow($row, $date)
        ) {
            return;
        }

        $department = $this->departmentLabel($row, $company);
        if ($department === '') {
            return;
        }

        if (! isset($departments[$department])) {
            $departments[$department] = [
                'department' => $department,
                'pj_penerima' => $this->pjPenerimaLabel($department, $company),
                'employees' => [],
            ];
        }

        if (! isset($departments[$department]['employees'][$employeeCode])) {
            $departments[$department]['employees'][$employeeCode] = [
                'code' => $employeeCode,
                'name' => $employeeName,
                'dates' => [],
            ];
        }

        $departments[$department]['employees'][$employeeCode]['dates'][$date->toDateString()] = true;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function shouldDisplayRow(array $row, Carbon $date): bool
    {
        if (! $this->isFriday($row, $date)) {
            return false;
        }

        return strtolower(trim((string) ($row['Religion'] ?? ''))) === 'islam'
            && strtolower(trim((string) ($row['Sex'] ?? ''))) === 'male';
    }

    /**
     * @param  array<string, string>  $row
     */
    private function isFriday(array $row, Carbon $date): bool
    {
        $day = trim((string) ($row['Day'] ?? ''));
        if ($day !== '') {
            return str_contains(strtolower($day), 'friday');
        }

        return (int) $date->dayOfWeekIso === 5;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function departmentLabel(array $row, string $company): string
    {
        if ($company === 'GSU') {
            return $this->initialDepartmentGsu($this->nameGroupDepartmentGsu($this->groupDepartmentGsu($row)));
        }

        if ($company === 'RU') {
            return $this->initialDepartmentRu($this->nameGroupDepartmentRu($this->groupDepartmentRu($row)));
        }

        return trim((string) ($row['Department_x0020_Name'] ?? ''));
    }

    /**
     * @param  array<string, string>  $row
     */
    private function groupDepartmentRu(array $row): string
    {
        $fullName = trim((string) ($row['Full_x0020_Name'] ?? ''));
        $jobTitle = trim((string) ($row['Job_x0020_Title'] ?? ''));
        $department = trim((string) ($row['Department_x0020_Name'] ?? ''));
        $workgroup = trim((string) ($row['Workgroup'] ?? ''));

        if ($this->containsAny($workgroup, ['Borongan'])) {
            return 'TT';
        }
        if ($this->containsAny($fullName, ['Rafi Prawi'])) {
            return 'AA';
        }
        if ($this->containsAny($jobTitle, [
            'Kru Sanding',
            'Produksi Hilir',
            'Kru Rotary',
            'Operator Moulding',
            'Kru Moulding',
            'Kru Packing',
            'Kru Bahan Daur Ulang',
            'Operator Sanding',
            'Operator Rotary',
            'Teknisi Pisau Mesin Pro',
        ]) || ($this->containsAny($jobTitle, ['Kru Pallet']) && $this->containsAny($department, ['Produksi Akhir']))) {
            return 'AE';
        }
        if ($this->containsAny($jobTitle, [
            'Operator Table Saw',
            'Ka. Dept. Produksi RU',
            'Ka. Dept. Produksi',
            'Operator Finger Joint',
            'Kru Finger Joint',
            'Mandor Produksi Hulu',
            'Operator Double Planner Rip Saw',
            'S4S',
        ]) || ($this->containsAny($jobTitle, ['Supir Forklift']) && $this->containsAny($department, ['Produksi Akhir']))) {
            return 'AD1';
        }
        if ($this->containsAny($jobTitle, [
            'Kru Tulis Kayu Bulat',
            'Kru Grader Sawmill',
            'Sawmill',
            'Bahan Baku',
            'Kru S4S',
            'Kru Kayu Bulat',
            'Staff Sawmill',
            'Ka. Dept. Produksi Fact',
        ]) || ($this->containsAny($jobTitle, ['Supir Forklift']) && $this->containsAny($workgroup, ['Karyawan Kontrak']))) {
            return 'AA';
        }
        if ($this->containsAny($jobTitle, [
            'Supir Forklift',
            'Operator Vacuum',
            'Supir Oto Carry',
            'Kru KD',
            'Kru Vacuum',
            'Mandor Vacuum KD',
            'Ka. Div. Vacuum KD',
        ]) || $this->containsAny($fullName, ['Rahmat Wahyudi', 'Raihan Muktasim', 'Muhammad Ridho'])) {
            return 'AC';
        }
        if ($this->containsAny($jobTitle, ['Borongan Sawmill']) || $this->containsAny($fullName, ['Benjamin'])) {
            return 'TT';
        }

        return 'AD1';
    }

    private function nameGroupDepartmentRu(string $groupDepartment): string
    {
        if (str_contains($groupDepartment, 'AA') || str_contains($groupDepartment, 'AB')) {
            return 'PKB & SML';
        }
        if (str_contains($groupDepartment, 'GG3') || str_contains($groupDepartment, 'AC')) {
            return 'VKD';
        }
        if (str_contains($groupDepartment, 'TT')) {
            return 'Borongan';
        }
        if (str_contains($groupDepartment, 'AE') || str_contains($groupDepartment, 'Ae') || str_contains($groupDepartment, 'ae')) {
            return 'PHI';
        }
        if (str_contains($groupDepartment, 'AD1')
            || str_contains($groupDepartment, 'AD2')
            || str_contains($groupDepartment, 'GG2')
            || str_contains($groupDepartment, 'AF')
        ) {
            return 'PHU & KRUT';
        }

        return '';
    }

    private function initialDepartmentRu(string $nameGroupDepartment): string
    {
        if (str_contains($nameGroupDepartment, 'Bahan Baku')) {
            return 'WNB';
        }
        if (str_contains($nameGroupDepartment, 'Warehouse')) {
            return 'WHS';
        }

        return $nameGroupDepartment;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function groupDepartmentGsu(array $row): string
    {
        $employeeCode = trim((string) ($row['Employee_x0020_Code'] ?? ''));
        $fullName = trim((string) ($row['Full_x0020_Name'] ?? ''));
        $jobTitle = trim((string) ($row['Job_x0020_Title'] ?? ''));
        $department = trim((string) ($row['Department_x0020_Name'] ?? ''));
        $workgroup = trim((string) ($row['Workgroup'] ?? ''));

        if ($this->sameText($fullName, 'alfisyah rizal')) {
            return 'AA';
        }
        if ($this->containsAny($jobTitle, ['Operator Bahan Awal', 'Kru Cuci BB', 'Kru Cuci'])) {
            return 'AA';
        }
        if ($this->containsAny($jobTitle, ['Supir Gudang'])) {
            return 'AC';
        }
        if ($this->containsAny($fullName, ['Jansen Mard'])) {
            return 'AJ3';
        }
        if ($this->containsAny($fullName, ['Buchari'])) {
            return 'PINHI';
        }
        if ($employeeCode === '120589') {
            return 'PINHI';
        }
        if ($this->containsAny($fullName, ['Santi Eti'])
            || $this->containsAny($jobTitle, ['Kru Penggilingan', 'Operator Pencampuran', 'Regu Pencampuran'])
        ) {
            return 'PINHU';
        }
        if ($employeeCode === '120660') {
            return 'AA';
        }
        if ($this->containsAny($jobTitle, ['Kru Ekstrusi BB Grup'])) {
            return 'BRO';
        }
        if ($this->containsAny($fullName, ['Friend Rohot'])
            || $this->containsAny($jobTitle, ['Petugas Kebersihan Lap', 'Kru Ekstrusi BB', 'Kru Penerimaan BB', 'Cleaning', 'Forklift'])
        ) {
            return 'AA';
        }
        if ($this->sameText($department, 'Marketing') || $this->sameText($department, 'Gudang')) {
            return 'AC';
        }
        if ($this->containsAny($workgroup, ['Produksi Regu III'])) {
            return 'AJ3';
        }
        if ($this->containsAny($workgroup, ['Produksi Regu II'])) {
            return 'AJ2';
        }
        if ($this->containsAny($workgroup, ['Produksi Regu I'])) {
            return 'AJ1';
        }
        if ($this->containsAny($jobTitle, ['Mesin Ekstrusi']) || $this->containsAny($workgroup, ['Ekstrusi Kecil', 'Ekstrusi Besar'])) {
            return 'BRO';
        }
        if ($this->containsAny($jobTitle, ['Broker'])) {
            return 'AA';
        }
        if ($this->containsAny($jobTitle, [
            'Operator Giling Bahan',
            'Teknisi Mesin Produksi',
            'Kru Hot Stamping',
            'Ka. Dept. Produksi',
            'Operator Pencampur Bahan',
            'Ka. Regu Pencampur &',
        ])) {
            return 'PINHU';
        }
        if ($this->containsAny($jobTitle, [
            'Regu Assembly',
            'Kebersihan Lap',
            'Packing Kur',
            'Side Seal',
            'Pasang Kunci',
            'Kunci Cov',
            'Kunci Layer',
            'Bor Lob',
            'Plastik Cov',
            'Door Seal',
            'Bottom Foot',
            'Packing',
            'Strapping Band',
            'ADM. Hasil',
            'ADM. Iinput',
        ])) {
            return 'PINHI';
        }

        return '3345';
    }

    private function nameGroupDepartmentGsu(string $groupDepartment): string
    {
        if (str_contains($groupDepartment, 'AA')) {
            return 'Bahan Baku';
        }
        if (str_contains($groupDepartment, 'AC') || str_contains($groupDepartment, '3345')) {
            return 'Warehouse';
        }
        if (str_contains($groupDepartment, 'ZZ11')) {
            return 'PIN HULU';
        }
        if (str_contains($groupDepartment, 'BRO')) {
            return 'Broker Kecil & Besar';
        }
        if (str_contains($groupDepartment, 'AB')) {
            return 'Marketing';
        }
        if (str_contains($groupDepartment, 'AJ3')) {
            return 'Regu C';
        }
        if (str_contains($groupDepartment, 'AJ2')) {
            return 'Regu B';
        }
        if (str_contains($groupDepartment, 'AJ1')) {
            return 'Regu A';
        }
        if (str_contains($groupDepartment, 'AK1')) {
            return 'Broker Pagi';
        }
        if (str_contains($groupDepartment, 'AK2')) {
            return 'Broker Sore';
        }
        if (str_contains($groupDepartment, 'AK3')) {
            return 'Broker Malam';
        }
        if (str_contains($groupDepartment, 'PINHU')) {
            return 'PIN HULU';
        }
        if (str_contains($groupDepartment, 'PINHI')) {
            return 'PIN HILIR';
        }

        return 'SSWE';
    }

    private function initialDepartmentGsu(string $nameGroupDepartment): string
    {
        if (str_contains($nameGroupDepartment, 'Bahan Baku')) {
            return 'WNB';
        }
        if (str_contains($nameGroupDepartment, 'Warehouse')) {
            return 'WHS';
        }

        return $nameGroupDepartment;
    }

    private function pjPenerimaLabel(string $initialDepartment, string $company): string
    {
        if ($company === 'RU') {
            if (str_contains($initialDepartment, 'PKB & SML')) {
                return 'Rafi Prawira & SFD';
            }
            if (str_contains($initialDepartment, 'VKD')) {
                return 'SRO & Taufik Subiakto';
            }
            if (str_contains($initialDepartment, 'PHI')) {
                return 'Edi Sutoyo';
            }
            if (str_contains($initialDepartment, 'PHU & KRUT')) {
                return 'RZA';
            }

            return '';
        }

        if ($company !== 'GSU') {
            return '';
        }

        if (str_contains($initialDepartment, 'WNB')) {
            return 'SUM';
        }
        if (str_contains($initialDepartment, 'WHS')) {
            return 'Eko Herianto';
        }
        if (str_contains($initialDepartment, 'PIN HULU') || str_contains($initialDepartment, 'PIN HILIR')) {
            return 'Marisa';
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $departments
     * @param  array<int, Carbon>  $dates
     * @return array<int, array<string, mixed>>
     */
    private function buildDepartments(array $departments, array $dates, string $company): array
    {
        $departmentOrder = match ($company) {
            'GSU' => self::GSU_DEPARTMENT_ORDER,
            'RU' => self::RU_DEPARTMENT_ORDER,
            default => [],
        };
        uasort($departments, static function (array $left, array $right) use ($departmentOrder): int {
            $leftName = (string) ($left['department'] ?? '');
            $rightName = (string) ($right['department'] ?? '');
            $leftOrder = $departmentOrder[$leftName] ?? 999;
            $rightOrder = $departmentOrder[$rightName] ?? 999;

            return [$leftOrder, $leftName] <=> [$rightOrder, $rightName];
        });

        $result = [];
        foreach ($departments as $department) {
            $employees = array_values($department['employees'] ?? []);
            usort($employees, static function (array $left, array $right): int {
                return strnatcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
            });

            $rows = [];
            foreach ($employees as $employee) {
                $row = [
                    'Nama' => (string) ($employee['name'] ?? ''),
                    'dates' => [],
                ];

                foreach ($dates as $date) {
                    $row['dates'][$date->toDateString()] = [
                        'cek' => '',
                        'terima' => '',
                    ];
                }

                $rows[] = $row;
            }

            $result[] = [
                'department' => (string) ($department['department'] ?? ''),
                'pj_penerima' => (string) ($department['pj_penerima'] ?? ''),
                'rows' => $rows,
                'min_rows' => max(count($rows), 14),
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{start: Carbon, end: Carbon}|null
     */
    private function resolvePeriodFromFilters(array $filters): ?array
    {
        $startDate = trim((string) ($filters['start_date'] ?? $filters['TglAwal'] ?? ''));
        $endDate = trim((string) ($filters['end_date'] ?? $filters['TglAkhir'] ?? ''));
        $month = trim((string) ($filters['month'] ?? $filters['bulan'] ?? ''));
        $year = trim((string) ($filters['year'] ?? $filters['tahun'] ?? ''));

        if ($startDate !== '' || $endDate !== '') {
            $start = $this->parseDate($startDate) ?? $this->parseDate($endDate);
            $end = $this->parseDate($endDate) ?? $this->parseDate($startDate);

            if ($start !== null && $end !== null) {
                if ($end->lessThan($start)) {
                    [$start, $end] = [$end, $start];
                }

                return ['start' => $start->startOfDay(), 'end' => $end->endOfDay()];
            }
        }

        if ($month !== '') {
            $monthNumber = $this->parseMonth($month);
            $yearNumber = is_numeric($year) ? (int) $year : (int) Carbon::now()->year;
            if ($monthNumber !== null) {
                $date = Carbon::create($yearNumber, $monthNumber, 1)->startOfDay();

                return ['start' => $date->copy()->startOfMonth(), 'end' => $date->copy()->endOfMonth()];
            }
        }

        return null;
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    private function periodFromXmlDates(?Carbon $minDate, ?Carbon $maxDate): array
    {
        if ($minDate === null || $maxDate === null) {
            $today = Carbon::today();

            return ['start' => $today->copy()->startOfMonth(), 'end' => $today->copy()->endOfMonth()];
        }

        return [
            'start' => $minDate->copy()->startOfMonth()->startOfDay(),
            'end' => $maxDate->copy()->endOfMonth()->endOfDay(),
        ];
    }

    /**
     * @param  array{start: Carbon, end: Carbon}  $period
     * @return array<int, Carbon>
     */
    private function resolveReportDates(array $period): array
    {
        $dates = [];
        $cursor = $period['start']->copy()->startOfDay();
        $end = $period['end']->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            if ((int) $cursor->dayOfWeekIso === 5) {
                $dates[] = $cursor->copy();
            }

            $cursor->addDay();
        }

        if ($dates === []) {
            $dates[] = $period['start']->copy()->startOfDay();
        }

        return $dates;
    }

    private function parseMonth(string $value): ?int
    {
        $normalized = strtolower(trim($value));
        $months = [
            '1' => 1,
            '01' => 1,
            'jan' => 1,
            'januari' => 1,
            '2' => 2,
            '02' => 2,
            'feb' => 2,
            'februari' => 2,
            '3' => 3,
            '03' => 3,
            'mar' => 3,
            'maret' => 3,
            '4' => 4,
            '04' => 4,
            'apr' => 4,
            'april' => 4,
            '5' => 5,
            '05' => 5,
            'mei' => 5,
            'may' => 5,
            '6' => 6,
            '06' => 6,
            'jun' => 6,
            'juni' => 6,
            '7' => 7,
            '07' => 7,
            'jul' => 7,
            'juli' => 7,
            '8' => 8,
            '08' => 8,
            'agu' => 8,
            'agt' => 8,
            'agustus' => 8,
            '9' => 9,
            '09' => 9,
            'sep' => 9,
            'september' => 9,
            '10' => 10,
            'okt' => 10,
            'oct' => 10,
            'oktober' => 10,
            '11' => 11,
            'nov' => 11,
            'november' => 11,
            '12' => 12,
            'des' => 12,
            'dec' => 12,
            'desember' => 12,
        ];

        return $months[$normalized] ?? null;
    }

    /**
     * @param  array<int, string>  $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        $normalizedHaystack = strtolower($haystack);
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($normalizedHaystack, strtolower($needle))) {
                return true;
            }
        }

        return false;
    }

    private function sameText(string $left, string $right): bool
    {
        return strtolower(trim($left)) === strtolower(trim($right));
    }

    /**
     * @param  array<string, string>  $row
     */
    private function resolvePrintedByFromRow(array $row): string
    {
        foreach (['Created_x0020_By', 'Last_x0020_Modified_x0020_By'] as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function parseDate(string $value): ?Carbon
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
