<?php

namespace App\Http\Requests\PPS;

use App\Http\Requests\BaseReportRequest;

class GenerateHasilProduksiHarianPasangKunciProduksiReportRequest extends BaseReportRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'no_produksi' => ['required', 'string', 'max:50'],
            'preview_pdf' => ['nullable'],
        ];
    }

    public function noProduksi(): string
    {
        return trim((string) $this->input('no_produksi'));
    }
}
