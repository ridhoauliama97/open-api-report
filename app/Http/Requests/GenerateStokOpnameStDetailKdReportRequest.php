<?php

namespace App\Http\Requests;

class GenerateStokOpnameStDetailKdReportRequest extends BaseReportRequest
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
            'no_proc_kd' => ['nullable', 'string', 'max:20', 'required_without:NoProcKD'],
            'NoProcKD' => ['nullable', 'string', 'max:20', 'required_without:no_proc_kd'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (! $this->filled('no_proc_kd') && $this->filled('NoProcKD')) {
            $this->merge(['no_proc_kd' => $this->input('NoProcKD')]);
        }
    }

    public function noProcKd(): string
    {
        return trim((string) $this->input('no_proc_kd', $this->input('NoProcKD', '')));
    }
}
