<?php

namespace App\Services\Ascends\Ru\Hrm;

use Carbon\Carbon;
use RuntimeException;
use XMLReader;

class DaftarLiburCutiBersamaReportService
{
    private const TITLE = 'Daftar Libur Dan Cuti Bersama';

    /**
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload'): array
    {
        $rows = $this->parseXml($xmlContents, $sourceLabel);
        usort($rows, static fn (array $left, array $right): int => ((string) $left['date']) <=> ((string) $right['date']));

        $years = array_values(array_unique(array_filter(array_map(
            static fn (array $row): string => Carbon::parse((string) $row['date'])->format('Y'),
            $rows
        ))));
        $totalCutiBersama = count(array_filter(
            $rows,
            static fn (array $row): bool => str_contains(strtolower((string) $row['name']), 'cuti bersama')
        ));
        $totalLibur = count($rows) - $totalCutiBersama;

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => '',
            'headers' => ['No', 'Tanggal', 'Nama Libur / Cuti Bersama'],
            'rows' => array_map(static function (array $row, int $index): array {
                $date = Carbon::parse((string) $row['date'])->locale('id');

                return [
                    'No' => $index + 1,
                    'Tanggal' => $date->translatedFormat('d-M-y'),
                    'Nama Libur / Cuti Bersama' => (string) $row['name'],
                ];
            }, $rows, array_keys($rows)),
            'total_rows' => count($rows),
            'summary' => [
                'total_cuti_bersama' => $totalCutiBersama,
                'total_libur' => $totalLibur,
                'total' => count($rows),
            ],
            'period' => [
                'label' => count($years) === 1 ? 'Tahun '.$years[0] : '',
            ],
        ];
    }

    /**
     * @return array<int, array{date: string, name: string}>
     */
    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML Holiday kosong.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML Holiday tidak valid: {$sourceLabel}");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'holiday') {
                continue;
            }

            $recordXml = $reader->readOuterXML();
            if (! is_string($recordXml) || trim($recordXml) === '') {
                continue;
            }

            $node = @simplexml_load_string($recordXml, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($node === false) {
                continue;
            }

            $date = trim((string) ($node->Date ?? ''));
            $name = trim((string) ($node->Name ?? ''));
            if ($date === '' && $name === '') {
                continue;
            }

            $rows[] = [
                'date' => $date,
                'name' => $name,
            ];
        }

        $reader->close();

        if ($rows === []) {
            throw new RuntimeException('Data Holiday tidak ditemukan di XML.');
        }

        return $rows;
    }
}
