<?php

namespace App\Services\Ascends\Shared\Hrm\CustomReports;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class VerifikasiLemburReportService
{
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $parsed = $this->parseRows($xmlContents, $sourceLabel);
        $allRows = $parsed['data_rows'];

        if ($allRows === []) {
            throw new RuntimeException('Data verifikasi lembur tidak ditemukan pada XML.');
        }

        $period = $this->resolvePeriod($allRows, $filters);

        $this->sortRows($allRows);

        $grouped = $this->groupByEmployee($allRows);

        return [
            'title' => 'Laporan Verifikasi Lembur',
            'headerTitle' => 'Laporan Verifikasi Lembur',
            'subtitle' => 'Dari '.$period['start'].' s/d '.$period['end'],
            'grouped_rows' => $grouped,
            'total_entries' => count($allRows),
            'period' => $period,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => '',
        ];
    }

    /**
     * @return array{data_rows: array<int, array<string, string>>}
     */
    private function parseRows(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML wajib dikirim.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA)) {
            throw new RuntimeException("File XML tidak valid ({$sourceLabel}).");
        }

        $dataRows = [];

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'table') {
                continue;
            }

            $nodeXml = $reader->readOuterXml();
            if (! is_string($nodeXml) || trim($nodeXml) === '') {
                continue;
            }

            $node = simplexml_load_string($nodeXml);
            if ($node === false) {
                continue;
            }

            $row = [];
            foreach ($node->children() as $key => $value) {
                $row[$key] = trim((string) $value);
            }

            if (($row['EmployeeID'] ?? '') !== '') {
                $dataRows[] = $row;
            }
        }

        $reader->close();

        return [
            'data_rows' => $dataRows,
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  array<string, mixed>  $filters
     * @return array{start: string, end: string, label: string}
     */
    private function resolvePeriod(array $rows, array $filters): array
    {
        $startDate = trim((string) ($filters['StartDate'] ?? $filters['start_date'] ?? ''));
        $endDate = trim((string) ($filters['EndDate'] ?? $filters['end_date'] ?? ''));

        if ($startDate !== '' && $endDate !== '') {
            try {
                $start = Carbon::parse($startDate);
                $end = Carbon::parse($endDate);

                $startLabel = $start->locale('id')->translatedFormat('d-M-y');
                $endLabel = $end->locale('id')->translatedFormat('d-M-y');

                return [
                    'start' => $startLabel,
                    'end' => $endLabel,
                    'label' => 'Dari '.$startLabel.' s/d '.$endLabel,
                ];
            } catch (Throwable) {
            }
        }

        $dates = array_filter(array_map(
            fn (array $row): ?Carbon => $this->parseDate($row['Date'] ?? ''),
            $rows
        ));

        if ($dates === []) {
            $today = Carbon::today();
            $todayLabel = $today->locale('id')->translatedFormat('d-M-y');

            return [
                'start' => $todayLabel,
                'end' => $todayLabel,
                'label' => 'Dari '.$todayLabel.' s/d '.$todayLabel,
            ];
        }

        $min = min($dates);
        $max = max($dates);

        $minLabel = $min->locale('id')->translatedFormat('d-M-y');
        $maxLabel = $max->locale('id')->translatedFormat('d-M-y');

        return [
            'start' => $minLabel,
            'end' => $maxLabel,
            'label' => 'Dari '.$minLabel.' s/d '.$maxLabel,
        ];
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function sortRows(array &$rows): void
    {
        usort($rows, static function (array $a, array $b): int {
            $nameCmp = strcmp(
                strtolower(trim((string) ($a['FullName'] ?? ''))),
                strtolower(trim((string) ($b['FullName'] ?? '')))
            );
            if ($nameCmp !== 0) {
                return $nameCmp;
            }

            $dateA = trim((string) ($a['Date'] ?? ''));
            $dateB = trim((string) ($b['Date'] ?? ''));

            return strcmp($dateA, $dateB);
        });
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array{employee_name: string, job_title: string, rows: array<int, array<string, mixed>>}>
     */
    private function groupByEmployee(array $rows): array
    {
        $groups = [];
        $currentName = null;
        $currentGroup = null;

        foreach ($rows as $raw) {
            $name = trim((string) ($raw['FullName'] ?? ''));
            $jobTitle = trim((string) ($raw['JobTitle'] ?? ''));

            if ($name !== $currentName) {
                if ($currentGroup !== null) {
                    $groups[] = $currentGroup;
                }
                $currentName = $name;
                $currentGroup = [
                    'employee_name' => $name,
                    'job_title' => $jobTitle,
                    'rows' => [],
                ];
            }

            $currentGroup['rows'][] = $this->buildDisplayRow($raw);
        }

        if ($currentGroup !== null) {
            $groups[] = $currentGroup;
        }

        return $groups;
    }

    /**
     * @param  array<string, string>  $raw
     * @return array<string, mixed>
     */
    private function buildDisplayRow(array $raw): array
    {
        $signIn = $this->parseDate($raw['SignIn'] ?? '');
        $signOut = $this->parseDate($raw['SignOut'] ?? '');
        $jamKerjaAktual = trim((string) ($raw['JamKerjaAktual'] ?? ''));
        $jamKerjaFormatted = trim((string) ($raw['JamKerjaFormatted'] ?? ''));
        $actualHours = trim((string) ($raw['ActualHours'] ?? ''));
        $menit = trim((string) ($raw['_x002B__x0020__x002F__x0020_-_x0020_Menit'] ?? ''));
        $shiftName = trim((string) ($raw['ShiftName'] ?? ''));
        $tipeLembur = trim((string) ($raw['OvertimeTypeName'] ?? ''));
        $date = $this->parseDate($raw['Date'] ?? '');

        return [
            'date' => $date ? $date->format('d/m/Y') : '-',
            'job_title' => trim((string) ($raw['JobTitle'] ?? '')),
            'sign_in' => $signIn ? $signIn->locale('id')->translatedFormat('d-M-y H:i') : '-',
            'sign_out' => $signOut ? $signOut->locale('id')->translatedFormat('d-M-y H:i') : '-',
            'shift_name' => $shiftName !== '' ? $shiftName : '-',
            'actual_hours' => $actualHours !== '' ? (int) round((float) $actualHours) : '-',
            'jam_kerja_formatted' => $jamKerjaFormatted !== '' ? $jamKerjaFormatted : '-',
            'jam_kerja_aktual' => $jamKerjaAktual !== '' ? $jamKerjaAktual : '-',
            'plus_minus_menit' => $menit !== '' ? (int) round((float) $menit) : '-',
            'tipe_lembur' => $tipeLembur !== '' ? $tipeLembur : '-',
        ];
    }
}
