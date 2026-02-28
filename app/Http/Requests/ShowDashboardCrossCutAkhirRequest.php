<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseReportRequest;
use Illuminate\Validation\Validator;

class ShowDashboardCrossCutAkhirRequest extends BaseReportRequest
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
