<?php

namespace Tests\Feature\Console\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateCentralUserCommandTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private string $command = 'app:create-central-user';

    public function test_command_can_create_central_user_with_options(): void
    {
        $name = $this->faker->name;
        $email = $this->faker->unique()->safeEmail;
        $password = 'SecurePassword123!';

        $this->artisan($this->command, [
            '--name' => $name,
            '--email' => $email,
            '--password' => $password,
        ])
            ->expectsOutput("Central user {$name} created successfully with ID: 1")
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
            'tenant_id' => null,
        ]);

        $user = User::withoutCentralApp()->where('email', $email)->first();
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_command_can_create_central_user_with_interactive_input(): void
    {
        $name = $this->faker->name;
        $email = $this->faker->unique()->safeEmail;
        $password = 'SecurePassword123!';

        $this->artisan($this->command)
            ->expectsQuestion('Enter the name of the central user', $name)
            ->expectsQuestion('Enter the email of the central user', $email)
            ->expectsQuestion('Enter the password of the central user', $password)
            ->expectsOutput("Central user {$name} created successfully with ID: 1")
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
            'tenant_id' => null,
        ]);
    }

    public function test_command_validates_required_name(): void
    {
        $email = $this->faker->unique()->safeEmail;
        $password = 'SecurePassword123!';

        $this->artisan($this->command, [
            '--name' => '',
            '--email' => $email,
            '--password' => $password,
        ])
            ->expectsOutput('Validation failed:')
            ->expectsOutput(' - The name field is required.')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', [
            'email' => $email,
        ]);
    }

    public function test_command_validates_required_email(): void
    {
        $name = $this->faker->name;
        $password = 'SecurePassword123!';

        $this->artisan($this->command, [
            '--name' => $name,
            '--email' => '',
            '--password' => $password,
        ])
            ->expectsOutput('Validation failed:')
            ->expectsOutput(' - The email field is required.')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', [
            'name' => $name,
        ]);
    }

    public function test_command_validates_email_format(): void
    {
        $name = $this->faker->name;
        $password = 'SecurePassword123!';

        $this->artisan($this->command, [
            '--name' => $name,
            '--email' => 'invalid-email',
            '--password' => $password,
        ])
            ->expectsOutput('Validation failed:')
            ->expectsOutput(' - The email field must be a valid email address.')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', [
            'name' => $name,
        ]);
    }

    public function test_command_validates_unique_email(): void
    {
        $existingUser = User::factory()->create();
        $name = $this->faker->name;
        $password = 'SecurePassword123!';

        $this->artisan($this->command, [
            '--name' => $name,
            '--email' => $existingUser->email,
            '--password' => $password,
        ])
            ->expectsOutput('Validation failed:')
            ->expectsOutput(' - The email has already been taken.')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', [
            'name' => $name,
        ]);
    }

    public function test_command_validates_password_minimum_length(): void
    {
        $name = $this->faker->name;
        $email = $this->faker->unique()->safeEmail;

        $this->artisan($this->command, [
            '--name' => $name,
            '--email' => $email,
            '--password' => '123',
        ])
            ->expectsOutput('Validation failed:')
            ->expectsOutput(' - The password field must be at least 8 characters.')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', [
            'email' => $email,
        ]);
    }

    public function test_command_validates_name_maximum_length(): void
    {
        $longName = str_repeat('a', 256);
        $email = $this->faker->unique()->safeEmail;
        $password = 'SecurePassword123!';

        $this->artisan($this->command, [
            '--name' => $longName,
            '--email' => $email,
            '--password' => $password,
        ])
            ->expectsOutput('Validation failed:')
            ->expectsOutput(' - The name field must not be greater than 255 characters.')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', [
            'email' => $email,
        ]);
    }

    public function test_command_validates_email_maximum_length(): void
    {
        $name = $this->faker->name;
        $longEmail = str_repeat('a', 250).'@example.com';
        $password = 'SecurePassword123!';

        $this->artisan($this->command, [
            '--name' => $name,
            '--email' => $longEmail,
            '--password' => $password,
        ])
            ->expectsOutput('Validation failed:')
            ->expectsOutput(' - The email field must not be greater than 255 characters.')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', [
            'name' => $name,
        ]);
    }

    public function test_command_converts_email_to_lowercase_when_configured(): void
    {
        config(['fortify.lowercase_usernames' => true]);

        $name = $this->faker->name;
        $email = 'TEST@EXAMPLE.COM';
        $password = 'SecurePassword123!';

        $this->artisan($this->command, [
            '--name' => $name,
            '--email' => $email,
            '--password' => $password,
        ])
            ->expectsOutput("Central user {$name} created successfully with ID: 1")
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => 'test@example.com',
            'tenant_id' => null,
        ]);
    }

    public function test_command_preserves_email_case_when_not_configured(): void
    {
        config(['fortify.lowercase_usernames' => false]);

        $name = $this->faker->name;
        $email = 'Test@Example.Com';
        $password = 'SecurePassword123!';

        $this->artisan($this->command, [
            '--name' => $name,
            '--email' => $email,
            '--password' => $password,
        ])
            ->expectsOutput("Central user {$name} created successfully with ID: 1")
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => 'Test@Example.Com',
            'tenant_id' => null,
        ]);
    }

    public function test_command_creates_user_with_null_tenant_id(): void
    {
        $name = $this->faker->name;
        $email = $this->faker->unique()->safeEmail;
        $password = 'SecurePassword123!';

        $this->artisan($this->command, [
            '--name' => $name,
            '--email' => $email,
            '--password' => $password,
        ])
            ->assertExitCode(0);

        $user = User::withoutCentralApp()->where('email', $email)->first();
        $this->assertNull($user->tenant_id);
    }

    public function test_command_handles_multiple_validation_errors(): void
    {
        $existingUser = User::factory()->create();

        $this->artisan($this->command, [
            '--name' => '',
            '--email' => $existingUser->email,
            '--password' => '123',
        ])
            ->expectsOutput('Validation failed:')
            ->expectsOutput(' - The name field is required.')
            ->expectsOutput(' - The email has already been taken.')
            ->expectsOutput(' - The password field must be at least 8 characters.')
            ->assertExitCode(1);
    }

    public function test_command_displays_success_message_with_user_details(): void
    {
        $name = 'John Doe';
        $email = 'john@example.com';
        $password = 'SecurePassword123!';

        $this->artisan($this->command, [
            '--name' => $name,
            '--email' => $email,
            '--password' => $password,
        ])
            ->expectsOutput('Central user John Doe created successfully with ID: 1')
            ->assertExitCode(0);
    }
}
