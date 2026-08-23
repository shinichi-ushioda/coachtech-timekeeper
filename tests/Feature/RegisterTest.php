<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 名前が未入力の場合、バリデーションメッセージが表示される（ID1）
     */
    public function test_名前未入力でエラーになる(): void
    {
        $response = $this->post('/register', [
            'name'                  => '',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 要件の文言（FN003）と一致するか確認
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    /**
     * メールアドレス未入力でエラーになる（ID1）
     */
    public function test_メールアドレス未入力でエラーになる(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'テスト太郎',
            'email'                 => '',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // ★ デバッグ：レスポンスの状態とエラーを確認
        dump($response->status());
        dump(session('errors'));

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * パスワード未入力でエラーになる（ID1）
     */
    public function test_パスワード未入力でエラーになる(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'テスト太郎',
            'email'                 => 'test@example.com',
            'password'              => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }


    /**
     * メール形式でない場合にエラーになる（ID1）
     */
    public function test_メール形式でないとエラーになる(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'テスト太郎',
            'email'                 => 'test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスはメール形式で入力してください',
        ]);
    }

    /**
     * パスワードが8文字未満でエラーになる（ID1）
     */
    public function test_パスワードが8文字未満でエラーになる(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'テスト太郎',
            'email'                 => 'test@example.com',
            'password'              => 'pass123',
            'password_confirmation' => 'pass123',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    /**
     * 確認用パスワードが一致しないとエラーになる（ID1）
     */
    public function test_確認用パスワードが一致しないとエラーになる(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'テスト太郎',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password456',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }
}
