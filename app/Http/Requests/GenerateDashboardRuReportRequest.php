<?php

namespace App\Http\Requests;

class GenerateDashboardRuReportRequest extends BaseReportRequest
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
            'report_date' => ['nullable', 'date', 'required_without_all:Periode,periode,end_date,TglAkhir'],
            'Periode' => ['nullable', 'date', 'required_without_all:report_date,periode,end_date,TglAkhir'],
            'periode' => ['nullable', 'date', 'required_without_all:report_date,Periode,end_date,TglAkhir'],
            'end_date' => ['nullable', 'date', 'required_without_all:report_date,Periode,periode,TglAkhir'],
            'TglAkhir' => ['nullable', 'date', 'required_without_all:report_date,Periode,periode,end_date'],
        ];
    }

    public function reportDate(): string
    {
        return (string) $this->input(
            'report_date',
            $this->input(
                'Periode',
                $this->input(
                    'periode',
                    $this->input('end_date', $this->input('TglAkhir', ''))
                )
            )
        );
    }
}
