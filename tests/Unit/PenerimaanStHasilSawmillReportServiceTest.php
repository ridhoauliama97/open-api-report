<?php

namespace Tests\Unit;

use App\Services\PenerimaanStHasilSawmillReportService;
use ReflectionClass;
use Tests\TestCase;

class PenerimaanStHasilSawmillReportServiceTest extends TestCase
{
    public function test_length_column_labels_use_raw_sp_length_values(): void
    {
        $service = new PenerimaanStHasilSawmillReportService;

        $rows = $this->invokePrivate($service, 'normalizeMainRows', [[
            (object) [
                'NamaGrade' => 'STD',
                'Tebal' => 16,
                'Lebar' => 29,
                'IdTblLebar' => 'mm',
                'Panjang' => 2.5,
                'IdPanjang' => 'feet',
                'JmlhBatang' => 9,
                'IsLocal' => 0,
                'Hasil' => 0.1,
            ],
            (object) [
                'NamaGrade' => 'STD',
                'Tebal' => 16,
                'Lebar' => 29,
                'IdTblLebar' => 'mm',
                'Panjang' => 3.0,
                'IdPanjang' => 'feet',
                'JmlhBatang' => 12,
                'IsLocal' => 0,
                'Hasil' => 0.2,
            ],
        ]]);

        $columns = $this->invokePrivate($service, 'buildLengthColumns', [$rows]);

        $this->assertSame(['2.5', '3'], array_column($columns, 'label'));
        $this->assertSame(['2.5', '3'], array_column($columns, 'key'));
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    private function invokePrivate(object $object, string $method, array $arguments): mixed
    {
        $reflection = new ReflectionClass($object);
        $reflectedMethod = $reflection->getMethod($method);
        $reflectedMethod->setAccessible(true);

        return $reflectedMethod->invokeArgs($object, $arguments);
    }
}
