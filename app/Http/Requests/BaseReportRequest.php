<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseReportRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        if ($this->is('api/*')) {
            throw new HttpResponseException(response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422));
        }

        parent::failedValidation($validator);
    }
}

