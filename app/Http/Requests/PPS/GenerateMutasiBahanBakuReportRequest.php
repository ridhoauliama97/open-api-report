<?php

namespace App\Http\Requests\PPS;

use App\Http\Requests\BaseReportRequest;
use Carbon\Carbon;

class GenerateMutasiBahanBakuReportRequest extends BaseReportRequest
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
            'start_date' => ['nullable', 'date', 'required_without:TglAwal'],
            'end_date' => ['nullable', 'date', 'required_without:TglAkhir'],
            'TglAwal' => ['nullable', 'date', 'required_without:start_date'],
            'TglAkhir' => ['nullable', 'date', 'required_without:end_date'],
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function reportDates(): array
    {
        $startDate = $this->reportStartDate();
        $endDate = $this->reportEndDate();

        return [$startDate, $endDate];
    }

    public function reportStartDate(): string
    {
        $date = (string) $this->input('start_date', $this->input('TglAwal', ''));

        return $date !== '' ? $date : Carbon::today()->format('Y-m-d');
    }

    public function reportEndDate(): string
    {
        $date = (string) $this->input('end_date', $this->input('TglAkhir', ''));

        return $date !== '' ? $date : Carbon::today()->format('Y-m-d');
    }
}
