<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class GenerateKdKeluarMasukReportRequest extends BaseReportRequest
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
            'no_kd' => ['nullable', 'integer', 'min:1'],
            'NoKD' => ['nullable', 'integer', 'min:1'],
            'NoRuangKD' => ['nullable', 'integer', 'min:1'],
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

    public function noKd(): ?int
    {
        $val = $this->input('no_kd', $this->input('NoKD', $this->input('NoRuangKD')));
        if ($val === null || $val === '') {
            return null;
        }

        return (int) $val;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
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

