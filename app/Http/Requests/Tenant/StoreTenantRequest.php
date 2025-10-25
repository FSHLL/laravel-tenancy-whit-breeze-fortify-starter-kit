<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'string',
                'max:255',
                'unique:tenants,id',
            ],
            'domains' => [
                'required',
                'array',
                'min:1',
            ],
            'domains.*' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9.-]+$/',
                'unique:domains,domain',
            ],
            'data' => [
                'nullable',
                'string',
                'json',
            ],
        ];
    }
}
