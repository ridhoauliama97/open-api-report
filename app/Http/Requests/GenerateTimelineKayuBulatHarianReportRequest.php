<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class GenerateTimelineKayuBulatHarianReportRequest extends BaseReportRequest
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
        $parameterCount = (int) config('reports.timeline_kayu_bulat_harian.parameter_count', 2);
        $requiresDateRange = $parameterCount >= 2;

        return [
            'start_date' => ['nullable', 'date', ...($requiresDateRange ? ['required_without:TglAwal'] : [])],
            'end_date' => ['nullable', 'date', ...($requiresDateRange ? ['required_without:TglAkhir'] : [])],
            'TglAwal' => ['nullable', 'date', ...($requiresDateRange ? ['required_without:start_date'] : [])],
            'TglAkhir' => ['nullable', 'date', ...($requiresDateRange ? ['required_without:end_date'] : [])],
        ];
    }

    public function startDate(): string
    {
        $start = (string) $this->input('start_date', $this->input('TglAwal', ''));
        if ($start !== '') {
            return $start;
        }

        return $this->endDate();
    }

    public function endDate(): string
    {
        return (string) $this->input('end_date', $this->input('TglAkhir'));
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $parameterCount = (int) config('reports.timeline_kayu_bulat_harian.parameter_count', 2);
            if ($parameterCount <= 0) {
                return;
            }

            $startDate = $this->startDate();
            $endDate = $this->endDate();

            if ($parameterCount === 1) {
                if ($endDate === '') {
                    $validator->errors()->add('end_date', 'Tanggal wajib diisi.');
                }

                return;
            }

            if ($startDate === '' || $endDate === '') {
                return;
            }

            if (strtotime($endDate) < strtotime($startDate)) {
                $validator->errors()->add('end_date', 'Tanggal akhir harus sama atau setelah tanggal awal.');
            }
        });
    }
}
