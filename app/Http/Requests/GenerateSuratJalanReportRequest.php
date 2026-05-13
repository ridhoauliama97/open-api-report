<?php

namespace App\Http\Requests;

class GenerateSuratJalanReportRequest extends BaseReportRequest
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
            'no_jual' => ['nullable', 'string', 'max:13', 'required_without_all:NoJual,no_surat_jalan,NoSJ'],
            'NoJual' => ['nullable', 'string', 'max:13', 'required_without_all:no_jual,no_surat_jalan,NoSJ'],
            'no_surat_jalan' => ['nullable', 'string', 'max:13', 'required_without_all:no_jual,NoJual,NoSJ'],
            'NoSJ' => ['nullable', 'string', 'max:13', 'required_without_all:no_jual,NoJual,no_surat_jalan'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->filled('no_jual')) {
            return;
        }

        foreach (['NoJual', 'no_surat_jalan', 'NoSJ'] as $field) {
            if ($this->filled($field)) {
                $this->merge(['no_jual' => $this->input($field)]);

                return;
            }
        }
    }

    public function noJual(): string
    {
        return trim((string) $this->input('no_jual', ''));
    }
}
