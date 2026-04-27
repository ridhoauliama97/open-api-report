<?php

namespace App\Http\Requests\PPS;

use App\Http\Requests\BaseReportRequest;
use Carbon\Carbon;

class GenerateStockBrokerV2ReportRequest extends BaseReportRequest
{
    protected function prepareForValidation(): void
    {
        $date = (string) $this->input('end_date', $this->input('TglAkhir', ''));
        $warehouse = trim((string) $this->input('warehouse', $this->input('Warehouse', '')));

        if ($date === '') {
            $date = Carbon::today()->format('Y-m-d');
        }

        if ($warehouse === '') {
            $warehouse = 'ALL';
        }

        $this->merge([
            'end_date' => $this->input('end_date', $date),
            'TglAkhir' => $this->input('TglAkhir', $date),
            'warehouse' => $this->input('warehouse', $warehouse),
            'Warehouse' => $this->input('Warehouse', $warehouse),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'end_date' => ['nullable', 'date', 'required_without:TglAkhir'],
            'TglAkhir' => ['nullable', 'date', 'required_without:end_date'],
            'warehouse' => ['nullable', 'string', 'required_without:Warehouse'],
            'Warehouse' => ['nullable', 'string', 'required_without:warehouse'],
            'warehouse_name' => ['nullable', 'string'],
            'WarehouseName' => ['nullable', 'string'],
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

    public function warehouse(): string
    {
        return trim((string) $this->input(
            'warehouse',
            $this->input('Warehouse', $this->input('warehouse_name', $this->input('WarehouseName', 'ALL'))),
        ));
    }
}
