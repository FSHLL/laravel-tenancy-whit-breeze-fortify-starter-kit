<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends StoreUserRequest
{
    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($userId),
            ],
            'password' => ['nullable', ...Arr::except($this->passwordRules(), 0)],
            'roles' => ['sometimes', 'array'],
            'roles.*' => [Rule::exists('roles', 'name')->where('tenant_id', tenant('id'))],
        ];
    }
}
