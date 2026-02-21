<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseReportRequest;

class GenerateLabelNyangkutReportRequest extends BaseReportRequest
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
        return [];
    }
}


