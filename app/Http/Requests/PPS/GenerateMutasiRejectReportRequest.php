<?php

namespace App\Http\Requests\PPS;

use App\Http\Requests\BaseReportRequest;
use Carbon\Carbon;

class GenerateMutasiRejectReportRequest extends BaseReportRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date', 'required_without_all:StartDate,TglAwal'],
            'end_date' => ['nullable', 'date', 'required_without_all:EndDate,TglAkhir'],
            'StartDate' => ['nullable', 'date', 'required_without_all:start_date,TglAwal'],
            'EndDate' => ['nullable', 'date', 'required_without_all:end_date,TglAkhir'],
            'TglAwal' => ['nullable', 'date', 'required_without_all:start_date,StartDate'],
            'TglAkhir' => ['nullable', 'date', 'required_without_all:end_date,EndDate'],
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function reportDates(): array
    {
        return [$this->reportStartDate(), $this->reportEndDate()];
    }

    public function reportStartDate(): string
    {
        $date = (string) $this->input('start_date', $this->input('StartDate', $this->input('TglAwal', '')));

        return $date !== '' ? $date : Carbon::today()->format('Y-m-d');
    }

    public function reportEndDate(): string
    {
        $date = (string) $this->input('end_date', $this->input('EndDate', $this->input('TglAkhir', '')));

        return $date !== '' ? $date : Carbon::today()->format('Y-m-d');
    }
}
