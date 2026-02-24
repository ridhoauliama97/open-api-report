<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseReportRequest;
use Illuminate\Validation\Validator;

class GeneratePenerimaanStSawmillKgReportRequest extends BaseReportRequest
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
            'start_date' => ['nullable', 'date', 'required_without:TglAwal'],
            'end_date' => ['nullable', 'date', 'required_without:TglAkhir'],
            'TglAwal' => ['nullable', 'date', 'required_without:start_date'],
            'TglAkhir' => ['nullable', 'date', 'required_without:end_date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (!$this->filled('start_date') && $this->filled('TglAwal')) {
            $this->merge(['start_date' => $this->input('TglAwal')]);
        }

        if (!$this->filled('end_date') && $this->filled('TglAkhir')) {
            $this->merge(['end_date' => $this->input('TglAkhir')]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $startDate = $this->startDate();
            $endDate = $this->endDate();

            if ($startDate !== '' && $endDate !== '' && strtotime($endDate) < strtotime($startDate)) {
                $validator->errors()->add('end_date', 'Tanggal akhir harus sama atau setelah tanggal awal.');
            }
        });
    }

    public function startDate(): string
    {
        return (string) $this->input('start_date', '');
    }

    public function endDate(): string
    {
        return (string) $this->input('end_date', '');
    }
}

