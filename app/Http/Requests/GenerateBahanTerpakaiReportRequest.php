<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseReportRequest;

class GenerateBahanTerpakaiReportRequest extends BaseReportRequest
{
    /**
     * Determine whether the current user is authorized for this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return the validation rules for this request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date', 'required_without:TglAwal'],
            'start_date' => ['nullable', 'date', 'required_without_all:TglAwal,date'],
            'TglAwal' => ['nullable', 'date', 'required_without_all:start_date,date'],
        ];
    }
}


