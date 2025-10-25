<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function rules(): array
    {
        return [
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
                Rule::unique('domains', 'domain')->ignore($this->tenant->id, 'tenant_id'),
            ],
            'data' => [
                'nullable',
                'string',
                'json',
            ],
        ];
    }
}
