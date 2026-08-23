<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Breaks;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;
    /**
     * 詳細画面に名前・日付・打刻が正しく表示される（ID10）
     */
    public function test_詳細画面に正しい情報が表示される(): void
    {
        $user = User::factory()->create([
            'name' => 'テスト 太郎',
            'email_verified_at' => now(),
        ]);

        // 当日より未来のガードを避ける為、前月で勤怠を作成
        $date = Carbon::now()->subMonth()->startOfMonth();
        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in'  => $date->copy()->setTime(9, 0)->toDateTimeString(),
            'clock_out' => $date->copy()->setTime(18, 0)->toDateTimeString(),
        ]);

        // 休憩を作成
        Breaks::create([
            'attendance_id' => $attendance->id,
            'break_in'      => $date->copy()->setTime(12, 0)->toDateTimeString(),
            'break_out'     => $date->copy()->setTime(13, 0)->toDateTimeString(),
        ]);

        // 詳細画面を開く
        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);

        // 1. 名前がログインユーザーの名前になっている
        $response->assertSee('テスト 太郎');

        // 2. 日付が選択した日付になっている
        $response->assertSee($date->format('Y年'));
        $response->assertSee($date->format('n月j日'));

        // 3. 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
        $response->assertSee('9:00');
        $response->assertSee('18:00');

        // 4. 「休憩」にて記されている時間がログインユーザーの打刻と一致している
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
