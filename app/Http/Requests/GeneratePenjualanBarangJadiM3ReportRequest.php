<?php

namespace App\Http\Requests;

class GeneratePenjualanBarangJadiM3ReportRequest extends BaseReportRequest
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
            'no_jual' => ['nullable', 'string', 'max:15', 'required_without:NoJual'],
            'NoJual' => ['nullable', 'string', 'max:15', 'required_without:no_jual'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (! $this->filled('no_jual') && $this->filled('NoJual')) {
            $this->merge(['no_jual' => $this->input('NoJual')]);
        }
    }

    public function noJual(): string
    {
        return trim((string) $this->input('no_jual', ''));
    }
}
