<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseReportRequest;
use Illuminate\Validation\Validator;

class GenerateSupplierIntelReportRequest extends BaseReportRequest
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
        $parameterCount = (int) config('reports.supplier_intel.parameter_count', 0);
        $requiresDateRange = $parameterCount >= 2;
        $singleParameterName = strtolower((string) config('reports.supplier_intel.single_parameter_name', 'TglAkhir'));
        $requiresSingleStartDate = $parameterCount === 1 && in_array($singleParameterName, ['tglawal', 'start_date'], true);
        $requiresSingleEndDate = $parameterCount === 1 && !$requiresSingleStartDate;

        return [
            'start_date' => [
                'nullable',
                'date',
                ...($requiresDateRange || $requiresSingleStartDate ? ['required_without:TglAwal'] : []),
            ],
            'end_date' => [
                'nullable',
                'date',
                ...($requiresDateRange || $requiresSingleEndDate ? ['required_without:TglAkhir'] : []),
            ],
            'TglAwal' => [
                'nullable',
                'date',
                ...($requiresDateRange || $requiresSingleStartDate ? ['required_without:start_date'] : []),
            ],
            'TglAkhir' => [
                'nullable',
                'date',
                ...($requiresDateRange || $requiresSingleEndDate ? ['required_without:end_date'] : []),
            ],
        ];
    }

    public function startDate(): string
    {
        return (string) $this->input('start_date', $this->input('TglAwal'));
    }

    public function endDate(): string
    {
        return (string) $this->input('end_date', $this->input('TglAkhir'));
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $parameterCount = (int) config('reports.supplier_intel.parameter_count', 0);
            if ($parameterCount < 2) {
                return;
            }

            $startDate = $this->startDate();
            $endDate = $this->endDate();

            if ($startDate === '' || $endDate === '') {
                return;
            }

            if (strtotime($endDate) < strtotime($startDate)) {
                $validator->errors()->add('end_date', 'Tanggal akhir harus sama atau setelah tanggal awal.');
            }
        });
    }
}




