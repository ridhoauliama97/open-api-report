<?php

namespace App\Http\Requests\PPS;

use App\Http\Requests\BaseReportRequest;
use Carbon\Carbon;

class GenerateStockBonggolanV2ReportRequest extends BaseReportRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'end_date' => ['nullable', 'date', 'required_without:TglAkhir'],
            'TglAkhir' => ['nullable', 'date', 'required_without:end_date'],
            'warehouse_name' => ['nullable', 'string', 'required_without:WarehouseName'],
            'WarehouseName' => ['nullable', 'string', 'required_without:warehouse_name'],
        ];
    }

    public function reportDates(): array
    {
        $date = $this->reportDate();

        return [$date, $date];
    }

    public function reportDate(): string
    {
        $date = (string) $this->input('end_date', $this->input('TglAkhir', $this->input('report_date', '')));

        return $date !== '' ? $date : Carbon::today()->format('Y-m-d');
    }

    public function warehouseName(): string
    {
        return trim((string) $this->input('warehouse_name', $this->input('WarehouseName', '')));
    }
}
