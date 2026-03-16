<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class GenerateStHidupKeringReportRequest extends BaseReportRequest
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
            'hari' => ['nullable', 'integer', 'min:0'],
            'Hari' => ['nullable', 'integer', 'min:0'],
            'mode' => ['nullable', 'string', 'in:INCLUDE,EXCLUDE'],
            'Mode' => ['nullable', 'string', 'in:INCLUDE,EXCLUDE'],
        ];
    }

    public function hari(): int
    {
        return (int) $this->input('hari', $this->input('Hari', 90));
    }

    public function mode(): string
    {
        $mode = (string) $this->input('mode', $this->input('Mode', 'INCLUDE'));
        $mode = strtoupper(trim($mode));

        return in_array($mode, ['INCLUDE', 'EXCLUDE'], true) ? $mode : 'INCLUDE';
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->hari() < 0) {
                $validator->errors()->add('hari', 'Hari harus >= 0.');
            }
        });
    }
}
