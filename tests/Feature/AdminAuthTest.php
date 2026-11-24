<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $password = 'Admin@Hassan2026!';

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('admin-login:admin@hassanmotors.pk|127.0.0.1');

        $this->withHeaders([
            'referer' => 'http://localhost:3000',
            'origin' => 'http://localhost:3000',
            'accept' => 'application/json',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Hassan Motors Admin',
            'email' => 'admin@hassanmotors.pk',
            'password' => Hash::make($this->password),
        ]);
    }

    /**
     * Unauthenticated users cannot access /api/v1/admin/me.
     */
    public function test_unauthenticated_request_to_me_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/v1/admin/me');

        $response->assertStatus(401);
    }

    /**
     * Login validates required fields.
     */
    public function test_login_validation_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/v1/admin/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Login fails with invalid password using generic message.
     */
    public function test_login_fails_with_incorrect_credentials(): void
    {
        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@hassanmotors.pk',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Successful login sets session and returns user resource.
     */
    public function test_admin_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@hassanmotors.pk',
            'password' => $this->password,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged in successfully.',
                'data' => [
                    'id' => $this->admin->id,
                    'email' => 'admin@hassanmotors.pk',
                    'name' => 'Hassan Motors Admin',
                ],
            ]);

        $this->assertAuthenticatedAs($this->admin);
    }

    /**
     * Authenticated admin can access /api/v1/admin/me.
     */
    public function test_authenticated_admin_can_access_me_endpoint(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->getJson('/api/v1/admin/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->admin->id,
                    'email' => 'admin@hassanmotors.pk',
                ],
            ]);
    }

    /**
     * Admin can logout and session is invalidated.
     */
    public function test_admin_can_logout_and_session_is_invalidated(): void
    {
        $loginResponse = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@hassanmotors.pk',
            'password' => $this->password,
        ]);

        $loginResponse->assertStatus(200);
        $this->assertAuthenticatedAs($this->admin);

        $logoutResponse = $this->postJson('/api/v1/admin/logout');
        $logoutResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        // Subsequent /me call must be unauthorized
        $this->getJson('/api/v1/admin/me')->assertStatus(401);
    }

    /**
     * Rate limiter blocks excessive failed login attempts.
     */
    public function test_login_rate_limiting_triggers_after_repeated_failures(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/admin/login', [
                'email' => 'admin@hassanmotors.pk',
                'password' => 'WrongPass',
            ])->assertStatus(422);
        }

        // 6th attempt should be rate limited (429)
        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@hassanmotors.pk',
            'password' => 'WrongPass',
        ]);

        $response->assertStatus(429);
    }

    /**
     * Admin can change password with valid current password.
     */
    public function test_admin_can_change_password(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/api/v1/admin/change-password', [
                'current_password' => $this->password,
                'password' => 'NewSecurePassword2026!',
                'password_confirmation' => 'NewSecurePassword2026!',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->admin->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword2026!', $this->admin->password));
    }

    /**
     * Change password rejects invalid current password.
     */
    public function test_change_password_rejects_incorrect_current_password(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/api/v1/admin/change-password', [
                'current_password' => 'WrongCurrentPassword',
                'password' => 'NewSecurePassword2026!',
                'password_confirmation' => 'NewSecurePassword2026!',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    /**
     * No public registration route exists.
     */
    public function test_no_public_registration_endpoint_exists(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'password' => 'password',
        ])->assertStatus(404);

        $this->postJson('/api/v1/admin/register', [
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'password' => 'password',
        ])->assertStatus(404);
    }
}
