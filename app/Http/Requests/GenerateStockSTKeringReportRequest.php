<?php

namespace App\Http\Requests;

class GenerateStockSTKeringReportRequest extends BaseReportRequest
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
            'end_date' => ['required_without:job_id', 'date'],
            'job_id' => ['nullable', 'string'],
        ];
    }

    public function endDate(): string
    {
        return (string) $this->input('end_date');
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (! $this->filled('end_date') && $this->filled('TglAkhir')) {
            $this->merge(['end_date' => $this->input('TglAkhir')]);
        }
    }
}
