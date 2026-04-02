<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'admin@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
                 ->assertJsonStructure(['token', 'user'])
                 ->assertJsonPath('user.email', 'admin@test.com');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'admin@test.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized()
                 ->assertJsonPath('message', '帳號或密碼錯誤');
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'notexist@test.com',
            'password' => 'password123',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_requires_email(): void
    {
        $response = $this->postJson('/api/login', ['password' => 'password123']);

        $response->assertUnprocessable()
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_valid_email_format(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_password(): void
    {
        $response = $this->postJson('/api/login', ['email' => 'admin@test.com']);

        $response->assertUnprocessable()
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_authenticated_user_can_get_own_profile(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/me');

        $response->assertOk()->assertJsonStructure(['id', 'name', 'email']);
    }

    public function test_unauthenticated_user_cannot_access_me(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertUnauthorized();
    }

    public function test_user_can_logout(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/logout');

        $response->assertOk()->assertJsonPath('message', '已登出');
    }
}
