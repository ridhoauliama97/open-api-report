<?php

namespace App\Http\Requests\PPS;

use App\Http\Requests\BaseReportRequest;
use Carbon\Carbon;

class GenerateRekapProduksiCrusherReportRequest extends BaseReportRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_date' => ['nullable', 'date', 'required_without_all:TglAkhir,end_date'],
            'end_date' => ['nullable', 'date', 'required_without_all:TglAkhir,report_date'],
            'TglAkhir' => ['nullable', 'date', 'required_without_all:end_date,report_date'],
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
}
