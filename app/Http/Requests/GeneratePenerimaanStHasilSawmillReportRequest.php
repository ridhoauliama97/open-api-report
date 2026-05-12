<?php

namespace App\Http\Requests;

class GeneratePenerimaanStHasilSawmillReportRequest extends BaseReportRequest
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
            'no_pen_st' => ['nullable', 'string', 'max:13', 'required_without:NoPenST'],
            'NoPenST' => ['nullable', 'string', 'max:13', 'required_without:no_pen_st'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (! $this->filled('no_pen_st') && $this->filled('NoPenST')) {
            $this->merge(['no_pen_st' => $this->input('NoPenST')]);
        }
    }

    public function noPenSt(): string
    {
        return trim((string) $this->input('no_pen_st', ''));
    }
}
