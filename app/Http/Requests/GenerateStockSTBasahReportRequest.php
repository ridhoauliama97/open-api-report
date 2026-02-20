<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateStockSTBasahReportRequest extends FormRequest
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
        if (!$this->filled('end_date') && $this->filled('TglAkhir')) {
            $this->merge(['end_date' => $this->input('TglAkhir')]);
        }
    }
}
