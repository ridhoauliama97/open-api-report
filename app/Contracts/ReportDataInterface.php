<?php

namespace App\Contracts;

interface ReportDataInterface
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchData(array $params): array;

    /**
     * @param  array<string, mixed>  $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchSubData(array $params): array;

    public function getReportTitle(): string;
}
