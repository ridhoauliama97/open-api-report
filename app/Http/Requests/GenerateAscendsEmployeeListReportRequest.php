<?php

namespace App\Http\Requests;

class GenerateAscendsEmployeeListReportRequest extends BaseReportRequest
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
            'xml' => ['nullable', 'string'],
            'xml_file' => ['nullable', 'file', 'max:20480'],
            'preview_pdf' => ['nullable', 'boolean'],
            'report_type' => ['nullable', 'in:list_karyawan,karyawan_per_masa_kerja,data_karyawan_status_kerja,daftar_karyawan_berdasarkan_abjad'],
        ];
    }

    public function xmlPayload(): ?string
    {
        $xml = $this->input('xml');
        if (is_string($xml) && trim($xml) !== '') {
            return $xml;
        }

        $file = $this->file('xml_file');
        if ($file !== null && $file->isValid()) {
            $contents = file_get_contents((string) $file->getRealPath());

            return is_string($contents) && trim($contents) !== '' ? $contents : null;
        }

        $rawBody = trim($this->getContent());
        if (str_starts_with($rawBody, '<')) {
            return $rawBody;
        }

        return null;
    }

    public function xmlSourceLabel(): ?string
    {
        $file = $this->file('xml_file');
        if ($file !== null && $file->isValid()) {
            return 'request upload: '.$file->getClientOriginalName();
        }

        if (is_string($this->input('xml')) && trim((string) $this->input('xml')) !== '') {
            return 'request field: xml';
        }

        if (str_starts_with(trim($this->getContent()), '<')) {
            return 'request raw xml body';
        }

        return null;
    }
}
