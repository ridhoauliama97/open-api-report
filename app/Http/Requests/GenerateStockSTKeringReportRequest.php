<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseReportRequest;

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
            'end_date' => ['required', 'date'],
        ];
    }

    public function endDate(): string
    {
        return (string) $this->input('end_date');
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (!$this->filled('end_date') && $this->filled('TglAkhir')) {
            $this->merge(['end_date' => $this->input('TglAkhir')]);
        }
    }
}



