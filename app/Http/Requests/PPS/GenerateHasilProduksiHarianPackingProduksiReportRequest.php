<?php

namespace App\Http\Requests\PPS;

use Illuminate\Foundation\Http\FormRequest;

class GenerateHasilProduksiHarianPackingProduksiReportRequest extends FormRequest
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
            'no_packing' => ['required', 'string', 'max:50'],
            'preview_pdf' => ['nullable'],
        ];
    }

    public function noPacking(): string
    {
        return trim((string) $this->input('no_packing'));
    }
}
