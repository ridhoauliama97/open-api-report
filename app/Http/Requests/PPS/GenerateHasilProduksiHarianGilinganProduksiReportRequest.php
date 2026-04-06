<?php

namespace App\Http\Requests\PPS;

use App\Http\Requests\BaseReportRequest;

class GenerateHasilProduksiHarianGilinganProduksiReportRequest extends BaseReportRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_produksi' => ['required', 'string', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (!$this->filled('no_produksi') && $this->filled('NoProduksi')) {
            $this->merge(['no_produksi' => $this->input('NoProduksi')]);
        }
    }

    public function noProduksi(): string
    {
        return trim((string) $this->input('no_produksi'));
    }
}
