<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CustomResetPassword;
use Tests\TestCase;

class RecaptchaTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    private function withSecret(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);
    }

    public function test_login_without_token_is_rejected(): void
    {
        $this->withSecret();
        Http::fake();
        $user = User::factory()->create();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors('recaptcha_token');
        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_login_with_failed_verification_is_rejected(): void
    {
        $this->withSecret();
        Http::fake([self::VERIFY_URL => Http::response(['success' => false])]);
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email, 'password' => 'password', 'recaptcha_token' => 'abc',
        ]);

        $response->assertSessionHasErrors('recaptcha_token');
        $this->assertGuest();
    }

    public function test_login_with_successful_verification_authenticates(): void
    {
        $this->withSecret();
        Http::fake([self::VERIFY_URL => Http::response(['success' => true])]);
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email, 'password' => 'password', 'recaptcha_token' => 'abc',
        ]);

        $this->assertAuthenticatedAs($user);
        Http::assertSent(fn ($r) => $r->url() === self::VERIFY_URL
            && $r['secret'] === 'test-secret' && $r['response'] === 'abc');
    }

    public function test_google_connection_failure_fails_closed(): void
    {
        $this->withSecret();
        Http::fake(fn () => throw new ConnectionException('timeout'));
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email, 'password' => 'password', 'recaptcha_token' => 'abc',
        ]);

        $response->assertSessionHasErrors('recaptcha_token');
        $this->assertGuest();
    }

    public function test_google_http_error_fails_closed(): void
    {
        $this->withSecret();
        Http::fake([self::VERIFY_URL => Http::response('boom', 500)]);
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email, 'password' => 'password', 'recaptcha_token' => 'abc',
        ])->assertSessionHasErrors('recaptcha_token');
        $this->assertGuest();
    }

    public function test_register_with_failed_captcha_creates_no_user(): void
    {
        $this->withSecret();
        Http::fake([self::VERIFY_URL => Http::response(['success' => false])]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'new@example.com',
            'password' => 'Str0ng-Passw0rd!x',
            'password_confirmation' => 'Str0ng-Passw0rd!x',
            'recaptcha_token' => 'abc',
        ]);

        $response->assertSessionHasErrors('recaptcha_token');
        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
        $this->assertGuest();
    }

    public function test_register_with_successful_captcha_creates_user(): void
    {
        $this->withSecret();
        Http::fake([self::VERIFY_URL => Http::response(['success' => true])]);

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'new@example.com',
            'password' => 'Str0ng-Passw0rd!x',
            'password_confirmation' => 'Str0ng-Passw0rd!x',
            'recaptcha_token' => 'abc',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    public function test_forgot_password_with_failed_captcha_sends_nothing(): void
    {
        $this->withSecret();
        Notification::fake();
        Http::fake([self::VERIFY_URL => Http::response(['success' => false])]);
        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email, 'recaptcha_token' => 'abc']);

        $response->assertSessionHasErrors('recaptcha_token');
        Notification::assertNothingSent();
    }

    public function test_forgot_password_with_successful_captcha_sends_notification(): void
    {
        $this->withSecret();
        Notification::fake();
        Http::fake([self::VERIFY_URL => Http::response(['success' => true])]);
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email, 'recaptcha_token' => 'abc']);

        Notification::assertSentTo($user, CustomResetPassword::class);
    }

    public function test_without_secret_login_works_without_token(): void
    {
        config(['services.recaptcha.secret_key' => null]);
        Http::fake();
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
        Http::assertNothingSent();
    }

    public function test_logout_is_not_affected(): void
    {
        $this->withSecret();
        Http::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect();

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_two_factor_challenge_is_not_affected(): void
    {
        $this->withSecret();
        Http::fake();

        $response = $this->post('/two-factor-challenge', ['code' => '123456']);

        // Fortify rejects the bogus code itself; the point is that no recaptcha check ran.
        $response->assertSessionDoesntHaveErrors('recaptcha_token');
        Http::assertNothingSent();
    }

    public function test_percent_encoded_login_path_cannot_bypass_check(): void
    {
        $this->withSecret();
        Http::fake();
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $this->post('/%6cogin', ['email' => $user->email, 'password' => 'password']);

        $this->assertGuest();
    }
}
