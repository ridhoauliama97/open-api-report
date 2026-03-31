<?php

namespace App\Http\Requests;

class GenerateProduksiPerSpkReportRequest extends BaseReportRequest
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
        if ($this->isMethod('get') || $this->isMethod('head')) {
            return [
                'no_spk' => ['nullable', 'string', 'max:50'],
            ];
        }

        return [
            'no_spk' => ['required', 'string', 'max:50'],
        ];
    }

    public function noSpk(): string
    {
        return trim((string) $this->input('no_spk', ''));
    }

    protected function prepareForValidation(): void
    {
        $value = $this->input('no_spk', $this->input('NoSPK', ''));

        $this->merge([
            'no_spk' => trim((string) $value),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'no_spk.required' => 'Field No SPK wajib diisi.',
        ];
    }
}
