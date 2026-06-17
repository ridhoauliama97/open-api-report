<?php

namespace App\Http\Requests;

class GenerateSpkSawmillReportRequest extends BaseReportRequest
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
            'no_spk' => ['nullable', 'string', 'max:20', 'required_without:NoSPK'],
            'NoSPK' => ['nullable', 'string', 'max:20', 'required_without:no_spk'],
            'id_produk' => ['nullable', 'integer', 'min:1', 'required_without:IdProduk'],
            'IdProduk' => ['nullable', 'integer', 'min:1', 'required_without:id_produk'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (! $this->filled('no_spk') && $this->filled('NoSPK')) {
            $this->merge(['no_spk' => trim((string) $this->input('NoSPK'))]);
        }

        if (! $this->filled('id_produk') && $this->filled('IdProduk')) {
            $this->merge(['id_produk' => $this->input('IdProduk')]);
        }
    }

    public function noSpk(): string
    {
        return trim((string) $this->input('no_spk', ''));
    }

    public function idProduk(): int
    {
        return (int) $this->input('id_produk', 0);
    }
}
