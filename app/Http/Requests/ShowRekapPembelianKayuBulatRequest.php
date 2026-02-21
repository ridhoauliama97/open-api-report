<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseReportRequest;
use Illuminate\Validation\Validator;

class ShowRekapPembelianKayuBulatRequest extends BaseReportRequest
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
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'TglAwal' => ['nullable', 'date'],
            'TglAkhir' => ['nullable', 'date'],
            'start_year' => ['nullable', 'integer', 'digits:4'],
            'end_year' => ['nullable', 'integer', 'digits:4'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $startDate = $this->input('start_date', $this->input('TglAwal'));
            $endDate = $this->input('end_date', $this->input('TglAkhir'));

            if (!$startDate || !$endDate) {
                return;
            }

            if (strtotime((string) $endDate) < strtotime((string) $startDate)) {
                $validator->errors()->add('end_date', 'Tanggal akhir harus sama atau setelah tanggal awal.');
            }

            $startYear = $this->input('start_year');
            $endYear = $this->input('end_year');

            if ($startYear !== null && $endYear !== null && (int) $endYear < (int) $startYear) {
                $validator->errors()->add('end_year', 'Tahun akhir harus sama atau setelah tahun awal.');
            }
        });
    }
}


