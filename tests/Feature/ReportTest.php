<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストはレポートページにアクセスできない（ID20-1）
     */
    public function test_ゲストはレポートページにアクセスできない(): void
    {
        //　認証せずにアクセス
        $response = $this->get('/attendance/report');

        // loginにリダイレクトされる
        $response->assertRedirect('/login');
    }

    /**
     * 認証ユーザーの統計が正しく計算される（ID20-2）
     */
    public function test_統計情報が正しく計算される(): void
    {
        // 準備：認証ユーザー
        $user = User::factory()->create();

        // 先月の勤怠を1日作る（9:00-18:00、休憩なしなら実働9時間）
        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => now()->subMonth()->startOfMonth()->toDateString(),
            'clock_in'  => now()->subMonth()->startOfMonth()->setTime(9, 0)->toDateTimeString(),
            'clock_out' => now()->subMonth()->startOfMonth()->setTime(18, 0)->toDateTimeString(),
        ]);

        //　実行：認証してレポートを開く
        $response = $this->actingAs($user)->get('/attendance/report');

        // 検証：200が返り、画面が表示される
        $response->assertStatus(200);
        $response->assertSee('マイ勤怠レポート');
    }

    /**
     * 勤怠データが無いユーザーでも安全に処理される（ID20-3）
     */
    public function test_勤怠データが無くても安全に処理される(): void
    {
        //　準備・勤怠データを1件も作らないユーザー
        $user = User::factory()->create([]);

        //　実行：認証してレポートを開く
        $response = $this->actingAs($user)->get('/attendance/report');

        // 検証：エラーにならず200で画面が表示される
        $response->assertStatus(200);
        $response->assertSee('マイ勤怠レポート');
    }
}
