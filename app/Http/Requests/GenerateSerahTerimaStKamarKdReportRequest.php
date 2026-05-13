<?php

namespace App\Http\Requests;

class GenerateSerahTerimaStKamarKdReportRequest extends BaseReportRequest
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
            'no_proc_kd' => ['required', 'string', 'max:13'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'no_proc_kd' => 'No.Proses KD',
        ];
    }
}
