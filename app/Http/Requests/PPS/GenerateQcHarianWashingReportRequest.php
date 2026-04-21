<?php

namespace App\Http\Requests\PPS;

use App\Http\Requests\BaseReportRequest;
use Carbon\Carbon;

class GenerateQcHarianWashingReportRequest extends BaseReportRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_date' => ['nullable', 'date', 'required_without:Tanggal'],
            'Tanggal' => ['nullable', 'date', 'required_without:report_date'],
        ];
    }

    public function reportDate(): string
    {
        $date = (string) $this->input('report_date', $this->input('Tanggal', ''));

        return $date !== '' ? $date : Carbon::today()->format('Y-m-d');
    }
}
