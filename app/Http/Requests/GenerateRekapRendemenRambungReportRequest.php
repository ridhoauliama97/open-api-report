<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class GenerateRekapRendemenRambungReportRequest extends BaseReportRequest
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
            'year' => ['nullable', 'integer', 'between:1900,2999', 'required_without:Tahun'],
            'Tahun' => ['nullable', 'integer', 'between:1900,2999', 'required_without:year'],
            'month' => ['nullable', 'integer', 'between:1,12', 'required_without:Bulan'],
            'Bulan' => ['nullable', 'integer', 'between:1,12', 'required_without:month'],
        ];
    }

    public function year(): string
    {
        return (string) $this->input('year', $this->input('Tahun'));
    }

    public function month(): string
    {
        return (string) $this->input('month', $this->input('Bulan'));
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(static function (Validator $validator): void {});
    }
}
