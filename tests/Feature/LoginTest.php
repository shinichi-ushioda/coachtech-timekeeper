<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * メールアドレス未入力でエラーになる（ID2）
     */
    public function test_メールアドレス未入力でエラーになる(): void
    {
        $response = $this->post('/login', [
            'email'    => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * パスワード未入力でエラーになる（ID2）
     */
    public function test_パスワード未入力でエラーになる(): void
    {
        $response = $this->post('/login', [
            'email'    => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * 登録情報と一致しない場合エラーになる（ID2）
     */
    public function test_登録情報と一致しない場合エラーになる(): void
    {
        $user = User::factory()->create([
            'email'    => 'test@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        //実行：間違ったパスワードでログイン
        $response = $this->post('/login', [
            'email'    => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        //検証：認証失敗のメッセージが出るかどうか
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);
    }
}
