<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseReportRequest;
use Illuminate\Validation\Validator;

class GeneratePerbandinganKbMasukPeriode1Dan2ReportRequest extends BaseReportRequest
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
            'period_1_start_date' => ['required', 'date'],
            'period_1_end_date' => ['required', 'date'],
            'period_2_start_date' => ['required', 'date'],
            'period_2_end_date' => ['required', 'date'],
        ];
    }

    public function period1StartDate(): string
    {
        return (string) $this->input('period_1_start_date');
    }

    public function period1EndDate(): string
    {
        return (string) $this->input('period_1_end_date');
    }

    public function period2StartDate(): string
    {
        return (string) $this->input('period_2_start_date');
    }

    public function period2EndDate(): string
    {
        return (string) $this->input('period_2_end_date');
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $mapping = [
            'TglAwalPeriode1' => 'period_1_start_date',
            'TglAkhirPeriode1' => 'period_1_end_date',
            'TglAwalPeriode2' => 'period_2_start_date',
            'TglAkhirPeriode2' => 'period_2_end_date',
        ];

        $merged = [];
        foreach ($mapping as $legacyKey => $canonicalKey) {
            if (!$this->filled($canonicalKey) && $this->filled($legacyKey)) {
                $merged[$canonicalKey] = $this->input($legacyKey);
            }
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $period1Start = $this->input('period_1_start_date');
            $period1End = $this->input('period_1_end_date');
            $period2Start = $this->input('period_2_start_date');
            $period2End = $this->input('period_2_end_date');

            if ($period1Start && $period1End && strtotime((string) $period1End) < strtotime((string) $period1Start)) {
                $validator->errors()->add('period_1_end_date', 'Tanggal akhir periode 1 harus sama atau setelah tanggal awal periode 1.');
            }

            if ($period2Start && $period2End && strtotime((string) $period2End) < strtotime((string) $period2Start)) {
                $validator->errors()->add('period_2_end_date', 'Tanggal akhir periode 2 harus sama atau setelah tanggal awal periode 2.');
            }
        });
    }
}


