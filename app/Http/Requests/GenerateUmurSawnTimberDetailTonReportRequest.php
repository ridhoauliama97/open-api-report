<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class GenerateUmurSawnTimberDetailTonReportRequest extends BaseReportRequest
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
            'Umur1' => ['required', 'integer', 'min:0'],
            'Umur2' => ['required', 'integer', 'min:0'],
            'Umur3' => ['required', 'integer', 'min:0'],
            'Umur4' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (!$this->filled('Umur1') || !$this->filled('Umur2') || !$this->filled('Umur3') || !$this->filled('Umur4')) {
                return;
            }

            $umur1 = (int) $this->input('Umur1');
            $umur2 = (int) $this->input('Umur2');
            $umur3 = (int) $this->input('Umur3');
            $umur4 = (int) $this->input('Umur4');

            if ($umur2 < $umur1) {
                $validator->errors()->add('Umur2', 'Umur2 harus lebih besar atau sama dengan Umur1.');
            }

            if ($umur3 < $umur2) {
                $validator->errors()->add('Umur3', 'Umur3 harus lebih besar atau sama dengan Umur2.');
            }

            if ($umur4 < $umur3) {
                $validator->errors()->add('Umur4', 'Umur4 harus lebih besar atau sama dengan Umur3.');
            }
        });
    }

    /**
     * @return array{Umur1:int,Umur2:int,Umur3:int,Umur4:int}
     */
    public function umurParameters(): array
    {
        return [
            'Umur1' => (int) $this->input('Umur1'),
            'Umur2' => (int) $this->input('Umur2'),
            'Umur3' => (int) $this->input('Umur3'),
            'Umur4' => (int) $this->input('Umur4'),
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $aliases = [
            'Umur1' => ['umur1', 'umur_1'],
            'Umur2' => ['umur2', 'umur_2'],
            'Umur3' => ['umur3', 'umur_3'],
            'Umur4' => ['umur4', 'umur_4'],
        ];

        $merge = [];
        foreach ($aliases as $target => $keys) {
            if ($this->filled($target)) {
                continue;
            }

            foreach ($keys as $key) {
                if ($this->filled($key)) {
                    $merge[$target] = $this->input($key);
                    break;
                }
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
