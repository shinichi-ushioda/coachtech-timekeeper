<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * メール未入力でエラーになる（ID3）
     */
    public function test_管理者ログインでメール未入力はエラーになる(): void
    {
        $response = $this->post('/admin/login', [
            'email'   => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * パスワード未入力でエラーになる（ID3）
     */
    public function test_管理者ログインでパスワード未入力はエラーになる(): void
    {
        $response = $this->post('/admin/login', [
            'email'     => 'admin@example.com',
            'password'  => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * 登録情報と一致しない場合エラーになる（ID3）
     */
    public function test_管理者ログインで登録情報と一致しない場合エラーになる(): void
    {
        User::factory()->create([
            'email'        => 'admin@example.com',
            'password'     => bcrypt('correct-password'),
            'admin_status' => 'true',
        ]);

        $response = $this->post('/admin/login', [
            'email'     => 'admin@example.com',
            'password'  => 'wrong-password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
