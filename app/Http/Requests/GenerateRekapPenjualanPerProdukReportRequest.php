<?php

namespace App\Http\Requests;

class GenerateRekapPenjualanPerProdukReportRequest extends BaseReportRequest
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
        if ($this->isMethod('get') || $this->isMethod('head')) {
            return [
                'start_date' => ['nullable', 'date'],
                'TglAwal' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date'],
                'TglAkhir' => ['nullable', 'date'],
            ];
        }

        return [
            'start_date' => ['nullable', 'date', 'required_without:TglAwal'],
            'TglAwal' => ['nullable', 'date', 'required_without:start_date'],
            'end_date' => ['nullable', 'date', 'required_without:TglAkhir', 'after_or_equal:start_date'],
            'TglAkhir' => ['nullable', 'date', 'required_without:end_date', 'after_or_equal:TglAwal'],
        ];
    }

    public function startDate(): string
    {
        return (string) $this->input('start_date', $this->input('TglAwal'));
    }

    public function endDate(): string
    {
        return (string) $this->input('end_date', $this->input('TglAkhir'));
    }
}
