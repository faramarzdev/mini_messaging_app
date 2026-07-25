<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_register()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson(route('register'), $userData);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'user' => ['id', 'name'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@doe.com',
        ]);
    }

    #[Test]
    public function user_can_login()
    {
        $password = 'password123';
        $user = User::factory()->create([
            'email' => 'john@doe.com',
            'password' => Hash::make($password),
        ]);

        $credentials = [
            'email' => 'john@doe.com',
            'password' => $password,
        ];

        $response = $this->postJson(route('login'), $credentials);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'user' => ['id', 'name'],
                'token',
            ]);
    }

    #[Test]
    public function user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token');
        $plainTextToken = $token->plainTextToken;

        // Verify token exists
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);

        // Logout
        $response = $this->withToken($plainTextToken)
            ->postJson(route('logout'));

        $response->assertStatus(Response::HTTP_OK);

        // Verify token is deleted from database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    #[Test]
    public function user_can_get_own_profile()
    {
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson(route('me'));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                ],
            ]);
    }

    #[Test]
    public function user_can_request_password_reset()
    {
        Notification::fake();
        $user = User::factory()->create();

        $response = $this->postJson(route('forgot-password'), [
            'email' => $user->email,
        ]);
        $response->assertStatus(Response::HTTP_OK);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    #[Test]
    public function user_can_reset_password_with_valid_token()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $response = $this->postJson(route('reset-password'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertStatus(Response::HTTP_OK);
    }

    #[Test]
    public function reset_fails_with_invalid_token()
    {
        // invalid token for requester user, yet correct structure
        $invalidToken = Password::createToken(User::factory()->create());

        $user = User::factory()->create();
        $response = $this->postJson(route('reset-password'), [
            'token' => $invalidToken,
            'email' => $user->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

    }

    #[Test]
    public function reset_actually_changes_password_in_db()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $newPassword = 'password123';
        $response = $this->postJson(route('reset-password'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);
        $response->assertStatus(Response::HTTP_OK);
        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
    }
}
