<?php

namespace App\Services\Ascends\Shared\Hrm;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class DataPesertaMakanSiangIbadahAulaPerDepartemenReportService
{
    private const TITLE = 'Data Peserta Penerima Makan Siang Ibadah Di Aula Per Departemen';

    private const DEPARTMENT_ORDER = [
        'PHI' => 1,
        'PHU 1' => 2,
        'PHU 2' => 3,
        'KRUT' => 4,
        'PBB' => 5,
        'SML' => 6,
        'SGR' => 7,
        'SMG' => 8,
        'VKD' => 9,
    ];

    private const GSU_DEPARTMENT_ORDER = [
        'Broker Shift A / Shift B' => 1,
        'Marketing & FA' => 2,
        'PIN HULU / PIN HILIR' => 3,
        'PIN HULU REGU A' => 4,
        'PIN HULU REGU B' => 5,
        'PIN HULU REGU C' => 6,
        'WHS' => 7,
        'WNB' => 8,
        'Broker Kecil & Besar' => 9,
        'Produksi Inject Regu A' => 10,
        'Produksi Inject Regu B' => 11,
        'Produksi Inject Regu C' => 12,
        'SSWE' => 99,
    ];

    private const GROUP_BY_NAME = [
        'susi susanti l panjaitan' => 'KRUT',
        'windiar wati zagoto' => 'KRUT',
        'tin meilysa s' => 'PHU 2',
        'dense malau' => 'PHU 2',
        'daniel yaso zalukhu' => 'SML',
        'helderia br panjaitan' => 'SGR',
        'nur aini' => 'SMG',
        'adi putra simbolon' => 'PBB',
        'ameylia saragih' => 'PBB',
        'anna berlian manihuruk' => 'PBB',
        'cintauli malau' => 'PBB',
        'desy br situmorang' => 'PBB',
        'dokmenlius lase' => 'PBB',
        'farida bancin' => 'PBB',
        'fredico mikael marshel' => 'PBB',
        'h rinaldy aritonang' => 'PBB',
        'henni br.ginting' => 'PBB',
        'janter hutapea' => 'PBB',
        'julianti br sianipar' => 'PBB',
        'mega clara sinurat' => 'PBB',
        'nikita feby mangunsong' => 'PBB',
        'nisa lovika manullang' => 'PBB',
        'pebriyanti veronika br simatupang siburian' => 'PBB',
        'riris siburian' => 'PBB',
        'rosa oktavia manik' => 'PBB',
        'temaziduhu gulo' => 'PBB',
        'titi pingki tarihoran' => 'PBB',
        'tongam simanjuntak' => 'PBB',
    ];

    private const PERSON_ORDER = [
        'alfian josua hamonangan sitorus' => 1,
        'bavo berhold harianja' => 2,
        'difa alamsah' => 3,
        'nasip maruli tua simangunsong' => 4,
        'predianto simanjuntak' => 5,
        'priska situmorang' => 6,
        'rosianna manalu' => 7,
        'sri putri monalisa' => 8,
        'lusi mariana br sianturi' => 101,
        'mariana simarmata' => 102,
        'seven juarman larosa' => 103,
        'yazuwar mendrofa' => 104,
        'dense malau' => 201,
        'tin meilysa s' => 202,
        'susi susanti l panjaitan' => 301,
        'windiar wati zagoto' => 302,
        'adi putra simbolon' => 401,
        'ameylia saragih' => 402,
        'anna berlian manihuruk' => 403,
        'cintauli malau' => 404,
        'desy br situmorang' => 405,
        'dokmenlius lase' => 406,
        'farida bancin' => 407,
        'fredico mikael marshel' => 408,
        'h rinaldy aritonang' => 409,
        'henni br.ginting' => 410,
        'janter hutapea' => 411,
        'julianti br sianipar' => 412,
        'mega clara sinurat' => 413,
        'nikita feby mangunsong' => 414,
        'nisa lovika manullang' => 415,
        'pebriyanti veronika br simatupang siburian' => 416,
        'riris siburian' => 417,
        'rosa oktavia manik' => 418,
        'temaziduhu gulo' => 419,
        'titi pingki tarihoran' => 420,
        'tongam simanjuntak' => 421,
        'daniel yaso zalukhu' => 501,
        'eddi situmorang' => 601,
        'fitri rama ulan manullang' => 602,
        'helderia br panjaitan' => 603,
        'jupen sihotang' => 604,
        'leonardo panggabean' => 605,
        'norta sekettang' => 606,
        'roma hutabarat' => 607,
        'saridayanti br purba' => 608,
        'ati mahita zai' => 701,
        'atoziduhu hura' => 702,
        'dina ria amanda br kembaren' => 703,
        'gabriela telasonika manalu' => 704,
        'henrizal gultom' => 705,
        'johan nainggolan' => 706,
        'juster e sinaga' => 707,
        'lambas sihite' => 708,
        'lilis roma uli br pandiangan' => 709,
        'maniati hia' => 710,
        'monika manalu' => 711,
        'nur aini' => 712,
        'radot manik' => 713,
        'rahman david tampubolon' => 714,
        'ririn melinda br tarigan' => 715,
        'roma jeki manullang' => 716,
        'ronal herianto sinaga' => 717,
        'ronauli sitompul' => 718,
        'sibeston sitorus' => 719,
        'aferlius gulo' => 801,
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $company = strtoupper(trim((string) ($filters['company'] ?? $filters['DB_CompanyName'] ?? 'RU')));
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
        $religion = strtolower(trim((string) ($row['Religion'] ?? '')));
        if ($religion !== 'kristen') {
            return false;
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

        return $this->initialDepartment($this->nameGroupDepartment($row));
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
        $scheduledShift = trim((string) ($row['Scheduled_x0020_Shift'] ?? ''));

        if ($this->sameText($fullName, 'alfisyah rizal')) {
            return 'AA';
        }
        if ($this->containsAny($jobTitle, ['Kru Gudang', 'Regu Sparep'])) {
            return 'AC';
        }
        if ($this->containsAny($fullName, ['Fitri Yanti Hu', 'Srina Waru'])) {
            return 'PIHUR1';
        }
        if ($this->containsAny($fullName, ['Larisma EP Sibu', 'Therty R'])) {
            return 'PIHUR2';
        }
        if ($this->containsAny($fullName, ['Paska Lid', 'Cindi Okta', 'Maniati H', 'Relina'])) {
            return 'PIHUR3';
        }
        if ($this->containsAny($jobTitle, ['Operator Assembly']) || $this->containsAny($fullName, ['Jesica Alviul'])) {
            return 'PINHI';
        }
        if ($this->containsAny($fullName, ['Jeprianto Tar', 'Erwinson Sar'])) {
            return 'ZZ';
        }
        if ($this->containsAny($fullName, ['Marsaulina Tobing'])
            || $this->containsAny($department, ['Washing & Broker'])
            || $this->containsAny($jobTitle, ['Broker'])
        ) {
            return 'AK';
        }
        if ($this->sameText($department, 'Marketing') || $this->sameText($department, 'Sales') || $this->containsAny($department, ['Finance &'])) {
            return 'AB';
        }
        if ($this->containsAny($fullName, ['Johannes'])) {
            return 'AC';
        }
        if ($this->containsAny($jobTitle, ['Produksi Broker'])) {
            return 'AA';
        }
        if ($this->containsAny($fullName, ['Rizky Wahand'])) {
            return 'SMG';
        }
        if ($this->containsAny($fullName, ['Alenta Br. S', 'Angraini', 'Nirmala Sa', 'Bebby Valent', 'Uci Rahmadan', 'Asima Afrianti', 'Ulfa Hayatu', 'Santi Eti', 'Ria Ade Per', 'Maysarah'])
            || in_array($employeeCode, ['120589', '120162'], true)
            || $this->containsAny($jobTitle, [
                'Operator Kunci',
                'Produksi Hulu',
                'Teknisi Mesin Prod',
                'Spv. Admin Produks',
                'HOT STAMPING',
                'Operator Giling Bahan',
                'Kru Strike Film',
                'Pencampur & Giling Baha',
                'Operator Pencampur Baha',
                'Long & Short Span',
                'Admin Produks',
                'Kru Kunci Layer',
                'Kru Kunci Cover',
                'Kru Door Seal',
                'Strapping Band',
                'Kru Packing Lemari',
                'Kru Packing Kursi',
                'Operator Packing Kursi',
                'Operator Packing Lemari',
                'Plastik Cover',
            ])
        ) {
            return 'PINHI';
        }
        if ($employeeCode === '120660'
            || $this->containsAny($fullName, ['Ardiansyah Prata', 'Friend Rohot', 'Filijisuk', 'Chandra Pad', 'Edi Kurnia'])
            || ($this->containsAny($jobTitle, ['Kru Ekstrusi BB']) && $this->containsAny($workgroup, ['Normal Shift']))
            || $this->containsAny($jobTitle, ['bahan baku', 'washing', 'Cleaning', 'forklift', 'Spv. Cuci', 'Kru Cuci', 'Kru Penerima BB', 'Kru Penerimaan BB'])
        ) {
            return 'AA';
        }
        if ($this->containsAny($jobTitle, ['KRU GUDANG', 'Kru Setting', 'Admin Gudang BS', 'Supir', 'Kernet', 'Gudang Spare Part', 'Gudang Barang Jadi'])
            || $this->sameText($department, 'gudang')
        ) {
            return 'AC';
        }
        if ($this->containsAny($workgroup, ['Produksi Regu III'])) {
            return 'PIHUR3';
        }
        if ($this->containsAny($workgroup, ['Produksi Regu II'])) {
            return 'PIHUR2';
        }
        if ($this->containsAny($workgroup, ['Produksi Regu I'])) {
            return 'PIHUR1';
        }
        if (($this->containsAny($workgroup, ['Ekstrusi Kecil', 'Ekstrusi Besar']) && $this->containsAny($scheduledShift, ['Shift III', 'Shift 3']))) {
            return 'AK3';
        }
        if (($this->containsAny($workgroup, ['Ekstrusi Kecil', 'Ekstrusi Besar']) && $this->containsAny($scheduledShift, ['Shift II', 'Shift 2']))) {
            return 'AK2';
        }
        if (($this->containsAny($workgroup, ['Ekstrusi Kecil', 'Ekstrusi Besar']) && $this->containsAny($scheduledShift, ['Shift I', 'Shift 1']))) {
            return 'AK1';
        }
        if (! $this->sameText($department, 'Produksi')) {
            return 'AK';
        }
        if ($this->containsAny($jobTitle, ['Produksi Hulu', 'FILM PIN', 'LONG &', 'GILING BAH', 'HELPER TEK', 'HELPER PEN', 'ADM. PROD', 'KA. DIV. PROD', 'TEKNISI PRO', 'KEBERSIHAN LAP', 'PACKING KUR', 'SIDE SEAL', 'PASANG KUNCI', 'KUNCI COV', 'KUNCI LAYER', 'BOR LOB', 'PLASTIK COV', 'DOOR SEAL', 'BOTTOM FOOT', 'PACKING', 'STRAPPING BAND', 'ADM. HASIL', 'ADM. INPUT'])) {
            return 'PINHI';
        }

        return 'ZZ';
    }

    private function nameGroupDepartmentGsu(string $groupDepartment): string
    {
        if (str_contains($groupDepartment, 'AA')) {
            return 'Bahan Baku';
        }
        if (str_contains($groupDepartment, 'AB')) {
            return 'Marketing & FA';
        }
        if (str_contains($groupDepartment, 'AC') || str_contains($groupDepartment, '3345')) {
            return 'Warehouse';
        }
        if (str_contains($groupDepartment, 'ZZ11')) {
            return 'PIN HULU / PIN HILIR';
        }
        if (str_contains($groupDepartment, 'BRO')) {
            return 'Broker Kecil & Besar';
        }
        if (str_contains($groupDepartment, 'AJ3')) {
            return 'Produksi Inject Regu C';
        }
        if (str_contains($groupDepartment, 'AJ2')) {
            return 'Produksi Inject Regu B';
        }
        if (str_contains($groupDepartment, 'AJ1')) {
            return 'Produksi Inject Regu A';
        }
        if (str_contains($groupDepartment, 'AK')) {
            return 'Broker Shift A / Shift B';
        }
        if (str_contains($groupDepartment, 'PINHU') || str_contains($groupDepartment, 'PINHI')) {
            return 'PIN HULU / PIN HILIR';
        }
        if (str_contains($groupDepartment, 'PIHUR1')) {
            return 'PIN HULU REGU A';
        }
        if (str_contains($groupDepartment, 'PIHUR2')) {
            return 'PIN HULU REGU B';
        }
        if (str_contains($groupDepartment, 'PIHUR3')) {
            return 'PIN HULU REGU C';
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

    /**
     * @param  array<string, string>  $row
     */
    private function nameGroupDepartment(array $row): string
    {
        $nameGroup = self::GROUP_BY_NAME[strtolower(trim((string) ($row['Full_x0020_Name'] ?? '')))] ?? null;
        if ($nameGroup !== null) {
            return $nameGroup;
        }

        $specialGroup = $this->specialGroupDepartment($row);
        if ($specialGroup !== null) {
            return $specialGroup;
        }

        $division = trim((string) ($row['Division_x0020_Name'] ?? ''));
        $subDivision = trim((string) ($row['Sub-Division_x0020_Name'] ?? ''));
        $department = trim((string) ($row['Department_x0020_Name'] ?? ''));
        $workgroup = trim((string) ($row['Workgroup'] ?? ''));
        $jobTitle = trim((string) ($row['Job_x0020_Title'] ?? ''));

        if (str_contains(strtolower($department), 'penerimaan bahan baku')) {
            return 'SMG';
        }

        if (strtoupper($division) === 'STICK ST' && str_contains(strtolower($jobTitle), 'kru stick borongan')) {
            return 'SGR';
        }

        if (strtoupper($division) === 'BAND SAW') {
            return 'SMG';
        }

        if (strtoupper($division) === 'PHU' && strtoupper($subDivision) === 'S4S') {
            return 'Hulu 1';
        }

        if (strtoupper($division) === 'PHU') {
            return 'Hulu 2';
        }

        if (strtoupper($division) === 'PHI') {
            return 'Hilir';
        }

        if (str_contains(strtolower($department), 'sawmill')) {
            return 'Sawmil';
        }

        if (str_contains(strtolower($department), 'vacuum')) {
            return 'Vacuum';
        }

        return $department;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function specialGroupDepartment(array $row): ?string
    {
        $employeeCode = trim((string) ($row['Employee_x0020_Code'] ?? ''));
        $fullName = trim((string) ($row['Full_x0020_Name'] ?? ''));
        $jobTitle = strtolower(trim((string) ($row['Job_x0020_Title'] ?? '')));
        $name = strtolower($fullName);

        if (str_contains($jobTitle, 'tally kayu bulat')
            || str_contains($name, 'pardomuan sihombi')
            || str_contains($name, 'wahyu affandi')
            || str_contains($employeeCode, '131655')
        ) {
            return 'PBB';
        }

        if (str_contains($name, 'ayu lestari gul')) {
            return 'PHI';
        }

        if (str_contains($name, 'relina')) {
            return 'PHU 1';
        }

        if (str_contains($name, 'ruswanto') || str_contains($jobTitle, 'kru grader supp')) {
            return 'SMG';
        }

        return null;
    }

    private function initialDepartment(string $nameGroupDepartment): string
    {
        if (isset(self::DEPARTMENT_ORDER[$nameGroupDepartment])) {
            return $nameGroupDepartment;
        }

        if (str_contains($nameGroupDepartment, 'Sawmil')) {
            return 'SML';
        }
        if (str_contains($nameGroupDepartment, 'Kayu Bulat')) {
            return 'PBB';
        }
        if (str_contains($nameGroupDepartment, 'Vacuum & KD (Shif')) {
            return 'VKD Shift II';
        }
        if (str_contains($nameGroupDepartment, 'Vacuum')) {
            return 'VKD';
        }
        if (str_contains($nameGroupDepartment, 'Hilir (Shift')) {
            return 'PHI Shift II';
        }
        if (str_contains($nameGroupDepartment, 'Hilir')) {
            return 'PHI';
        }
        if (str_contains($nameGroupDepartment, 'Hulu 2 (Shift')) {
            return 'PHU 2 Shift II';
        }
        if (str_contains($nameGroupDepartment, 'Hulu 1')) {
            return 'PHU 1';
        }
        if (str_contains($nameGroupDepartment, 'Hulu 2')) {
            return 'PHU 2';
        }
        if (str_contains($nameGroupDepartment, 'KRUT')) {
            return 'KRUT';
        }
        if (str_contains($nameGroupDepartment, 'FJ')) {
            return 'Finger Joint B / Finger Joint A';
        }

        return $nameGroupDepartment;
    }

    private function pjPenerimaLabel(string $initialDepartment, string $company): string
    {
        if ($company === 'GSU') {
            if (str_contains($initialDepartment, 'WNB')) {
                return 'EYS, SPY';
            }
            if (str_contains($initialDepartment, 'WHS')) {
                return 'FLO';
            }
            if (str_contains($initialDepartment, 'PIN HULU')) {
                return 'Elisabeth';
            }
            if (str_contains($initialDepartment, 'PIN HILIR')) {
                return 'Alenta';
            }

            return '';
        }

        if (str_contains($initialDepartment, 'PKB')) {
            return '';
        }
        if (str_contains($initialDepartment, 'VKD')) {
            return 'Sihardel';
        }
        if (str_contains($initialDepartment, 'PHI')) {
            return 'Difa Alamsah';
        }
        if (str_contains($initialDepartment, 'KRUT')) {
            return 'LRU';
        }
        if (str_contains($initialDepartment, 'PHU 1')) {
            return 'Yazuwar';
        }
        if (str_contains($initialDepartment, 'PHU 2')) {
            return 'Tin Meilysa';
        }
        if (str_contains($initialDepartment, 'SMG')) {
            return '';
        }
        if (str_contains($initialDepartment, 'SGR')) {
            return 'Martin Yosa';
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
        $departmentOrder = $company === 'GSU' ? self::GSU_DEPARTMENT_ORDER : self::DEPARTMENT_ORDER;
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
                $leftName = (string) ($left['name'] ?? '');
                $rightName = (string) ($right['name'] ?? '');
                $leftOrder = self::PERSON_ORDER[strtolower($leftName)] ?? 9999;
                $rightOrder = self::PERSON_ORDER[strtolower($rightName)] ?? 9999;

                return [$leftOrder, $leftName] <=> [$rightOrder, $rightName];
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
