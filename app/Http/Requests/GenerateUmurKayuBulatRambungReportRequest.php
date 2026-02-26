<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class GenerateUmurKayuBulatRambungReportRequest extends BaseReportRequest
{
    /**
     * Determine whether the current user is authorized for this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return the validation rules for this request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date', 'required_without:TglAwal'],
            'end_date' => ['nullable', 'date', 'required_without:TglAkhir'],
            'TglAwal' => ['nullable', 'date', 'required_without:start_date'],
            'TglAkhir' => ['nullable', 'date', 'required_without:end_date'],
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
        });
    }
}

