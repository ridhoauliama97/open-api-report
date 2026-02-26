<?php

namespace App\Http\Requests;

class GenerateHasilOutputRacipHarianReportRequest extends BaseReportRequest
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
            'end_date' => ['nullable', 'date', 'required_without:TglAkhir'],
            'TglAkhir' => ['nullable', 'date', 'required_without:end_date'],
        ];
    }

    public function endDate(string $defaultDate): string
    {
        return (string) $this->input('end_date', $this->input('TglAkhir', $defaultDate));
    }
}
