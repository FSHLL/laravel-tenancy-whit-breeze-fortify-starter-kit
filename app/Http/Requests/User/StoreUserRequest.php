<?php

namespace App\Http\Requests\User;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Fortify;

class StoreUserRequest extends FormRequest
{
    use PasswordValidationRules;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ];
    }

    protected function prepareForValidation(): void
    {
        if (config('fortify.lowercase_usernames') && $this->has(Fortify::username())) {
            $this->merge([
                Fortify::username() => Str::lower($this->{Fortify::username()}),
            ]);
        }
    }
}
