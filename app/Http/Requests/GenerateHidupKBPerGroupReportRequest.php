<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseReportRequest;

class GenerateHidupKBPerGroupReportRequest extends BaseReportRequest
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
        return [];
    }
}


