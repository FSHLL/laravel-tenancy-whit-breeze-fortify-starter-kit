<?php

namespace Tests\Feature\Http\Controllers\User;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ShowUserTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Tenant $tenant;

    private User $authenticatedUser;

    private User $targetUser;

    private string $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['id' => 'test-tenant']);

        tenancy()->initialize($this->tenant);

        $this->tenant->domains()->create(['domain' => $this->tenant->id]);

        $this->authenticatedUser = User::factory()->create();
        $this->targetUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $this->route = "http://{$this->tenant->id}.localhost/users/{$this->targetUser->id}";
    }

    public function test_authenticated_user_can_view_user_details(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertViewIs('users.show')
            ->assertViewHas('user', $this->targetUser);
    }

    public function test_unauthenticated_user_cannot_view_user_details(): void
    {
        $this->get($this->route)
            ->assertRedirect(route('login'));
    }

    public function test_show_user_displays_basic_information(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee($this->targetUser->name)
            ->assertSee($this->targetUser->email)
            ->assertSee('#'.$this->targetUser->id)
            ->assertSee($this->targetUser->created_at->toDayDateTimeString())
            ->assertSee($this->targetUser->updated_at->toDayDateTimeString())
            ->assertSee('Back to List')
            ->assertSee(route('users.index'))
            ->assertSee('Edit')
            ->assertSee(route('users.edit', $this->targetUser))
            ->assertSee('User Information')
            ->assertSee('Security Status')
            ->assertSee('Danger Zone');
    }

    public function test_show_user_displays_email_verification_status(): void
    {
        // Test verified user
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($this->authenticatedUser)
            ->get(str_replace($this->targetUser->id, $verifiedUser->id, $this->route))
            ->assertOk()
            ->assertSee('Verified');

        // Test unverified user
        $unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($this->authenticatedUser)
            ->get(str_replace($this->targetUser->id, $unverifiedUser->id, $this->route))
            ->assertOk()
            ->assertSee('Not verified');
    }

    public function test_show_user_displays_two_factor_authentication_status(): void
    {
        // Test user with 2FA enabled
        $userWith2FA = User::factory()->create([
            'two_factor_secret' => 'some-secret',
        ]);

        $this->actingAs($this->authenticatedUser)
            ->get(str_replace($this->targetUser->id, $userWith2FA->id, $this->route))
            ->assertOk()
            ->assertSee('Enabled');

        // Test user with 2FA disabled
        $userWithout2FA = User::factory()->create([
            'two_factor_secret' => null,
        ]);

        $this->actingAs($this->authenticatedUser)
            ->get(str_replace($this->targetUser->id, $userWithout2FA->id, $this->route))
            ->assertOk()
            ->assertSee('Disabled');
    }

    public function test_show_user_with_nonexistent_user_returns_404(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get(str_replace($this->targetUser->id, 99999, $this->route))
            ->assertNotFound();
    }

    public function test_show_user_displays_security_icons(): void
    {
        $this->actingAs($this->authenticatedUser)
            ->get($this->route)
            ->assertOk()
            ->assertSee('User Information')
            ->assertSee('Security Status')
            ->assertSee('General details about this user')
            ->assertSee('Account verification and security settings');
    }
}
