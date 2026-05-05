<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class GeneratePenerimaanKayuBulatIntTonReportRequest extends BaseReportRequest
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
            'no_kayu_bulat' => ['nullable', 'string', 'max:20'],
            'NoKayuBulat' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function noKayuBulat(): string
    {
        return trim((string) $this->input('no_kayu_bulat', $this->input('NoKayuBulat')));
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->noKayuBulat() === '') {
                $validator->errors()->add('NoKayuBulat', 'Nomor kayu bulat wajib diisi.');
            }
        });
    }
}
