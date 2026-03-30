<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class GenerateStockHidupPerNoSpkReportRequest extends BaseReportRequest
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
            'TglAkhir' => ['nullable', 'date'],
            'tgl_akhir' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $date = $this->tanggalAkhir();

            if ($date === '') {
                $validator->errors()->add('TglAkhir', 'Tanggal akhir wajib diisi.');
            }
        });
    }

    public function tanggalAkhir(): string
    {
        return (string) $this->input('TglAkhir', $this->input('tgl_akhir', now()->toDateString()));
    }
}
