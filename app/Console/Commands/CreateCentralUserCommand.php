<?php

namespace App\Console\Commands;

use App\Actions\Fortify\PasswordValidationRules;
use App\Enums\CentralRoles;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateCentralUserCommand extends Command
{
    use PasswordValidationRules;

    protected $signature = 'app:create-central-user {--name= : The name of the central user} {--email= : The email of the central user} {--password= : The password of the central user}';

    protected $description = 'Create a central application user';

    public function handle(): int
    {
        $name = $this->option('name') ?? $this->ask('Enter the name of the central user');
        $email = $this->option('email') ?? $this->ask('Enter the email of the central user');
        $password = $this->option('password') ?? $this->secret('Enter the password of the central user');

        if (config('fortify.lowercase_usernames')) {
            $email = Str::lower($email);
        }

        $errors = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => Arr::except($this->passwordRules(), 3),
            ]
        )->errors();

        if ($errors->isNotEmpty()) {
            $this->error('Validation failed:');
            foreach ($errors->all() as $error) {
                $this->error(" - {$error}");
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'tenant_id' => null,
        ]);

        $user->syncRoles([CentralRoles::SUPER_ADMIN->value]);

        $this->info("Central user {$user->name} created successfully with ID: {$user->id}");

        return self::SUCCESS;
    }
}
