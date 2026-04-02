<?php

namespace App\Http\Requests;

class GenerateRangkumanBongkarSusunReportRequest extends BaseReportRequest
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
            'date' => ['nullable', 'date', 'required_without:TglAwal'],
            'start_date' => ['nullable', 'date', 'required_without_all:TglAwal,date'],
            'TglAwal' => ['nullable', 'date', 'required_without_all:start_date,date'],
        ];
    }

    public function reportDate(): string
    {
        return (string) $this->input('date', $this->input('start_date', $this->input('TglAwal', '')));
    }
}
