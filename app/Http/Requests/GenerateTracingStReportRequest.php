<?php

namespace App\Http\Requests;

class GenerateTracingStReportRequest extends BaseReportRequest
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
            'no_produk' => ['nullable', 'string', 'max:20', 'required_without:NoProduk'],
            'NoProduk' => ['nullable', 'string', 'max:20', 'required_without:no_produk'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (! $this->filled('no_produk') && $this->filled('NoProduk')) {
            $this->merge(['no_produk' => $this->input('NoProduk')]);
        }
    }

    public function noProduk(): string
    {
        return trim((string) $this->input('no_produk', ''));
    }
}
