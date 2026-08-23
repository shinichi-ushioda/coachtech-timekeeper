<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Illuminate\Support\Facades\URL;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 会員登録後、認証メールが送信される（ID16）
     */
    public function test_会員登録後、認証メールが送信される(): void
    {
        Notification::fake();

        $userData = [
            'name'                  => 'テスト太郎',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        $user = User::where('email', 'test@example.com')->first();

        $response->assertStatus(302);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * 認証誘導画面に「認証はこちらから」ボタンが表示される（ID16）
     */
    public function test_認証誘導画面に認証ボタンが表示される(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
        
        $response->assertSee('認証はこちらから');
    }

    /**
     * メール認証を完了すると勤怠登録画面に遷移する（ID16）
     */
    public function test_メール認証完了で勤怠画面に遷移する(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $this->assertNotNull($user->fresh()->email_verified_at);

        $response->assertRedirect('/attendance?verified=1');
    }
}
