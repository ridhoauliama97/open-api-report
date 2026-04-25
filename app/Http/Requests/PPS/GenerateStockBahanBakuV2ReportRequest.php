<?php

namespace App\Http\Requests\PPS;

use App\Http\Requests\BaseReportRequest;
use Carbon\Carbon;

class GenerateStockBahanBakuV2ReportRequest extends BaseReportRequest
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
            'warehouse' => ['nullable', 'string', 'required_without:Warehouse'],
            'Warehouse' => ['nullable', 'string', 'required_without:warehouse'],
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
        return trim((string) $this->input('warehouse', $this->input('Warehouse', 'ALL')));
    }
}
