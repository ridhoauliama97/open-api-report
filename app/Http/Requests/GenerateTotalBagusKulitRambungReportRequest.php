<?php

namespace App\Http\Requests;

class GenerateTotalBagusKulitRambungReportRequest extends BaseReportRequest
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
            'report_date' => ['nullable', 'date', 'required_without_all:TglSawmill,tanggal,date'],
            'TglSawmill' => ['nullable', 'date', 'required_without_all:report_date,tanggal,date'],
            'tanggal' => ['nullable', 'date', 'required_without_all:report_date,TglSawmill,date'],
            'date' => ['nullable', 'date', 'required_without_all:report_date,TglSawmill,tanggal'],
        ];
    }

    public function reportDate(): string
    {
        return (string) $this->input(
            'report_date',
            $this->input('TglSawmill', $this->input('tanggal', $this->input('date', '')))
        );
    }
}
