<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use \App\Models\Breaks;
use Tests\TestCase;
use Carbon\Carbon;


class AttendanceTest extends TestCase
{
    use RefreshDatabase;
    /**
     * 打刻画面に現在の日付が表示される（ID4）
     */
    public function test_打刻画面に現在の日付が表示される(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Carbon::setTestNow(today()->setTime(10, 30));

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);

        $weekMap = ['日', '月', '火', '水', '木', '金', '土'];

        $today = Carbon::now();
        $expectedDate = $today->format('Y年n月j日') . '(' . $weekMap[$today->dayOfWeek] . ')';

        $response->assertSee($expectedDate);

        Carbon::setTestNow();
    }

    /**
     * 出勤中のステータスが表示される（ID5）
     */
    public function test_出勤中のステータスが表示される(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(),]);

        // 今日の勤怠を出勤中（出勤済み・退勤なし）で作る
        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => today()->toDateString(),
            'clock_in'  => now()->toDateTimeString(),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /**
     * 休憩中のステータスが表示される（ID5）
     */
    public function test_休憩中ステータスが表示される(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => today()->toDateString(),
            'clock_in'  => now()->toDateTimeString(),
            'clock_out' => null,
        ]);

        Breaks::create([
            'attendance_id' => $attendance->id,
            'break_in'      => now()->toDateTimeString(),
            'break_out'     => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /**
     * 退勤済ステータスが表示される（ID5）
     */
    public function test_退勤済ステータスが表示される(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => today()->toDateString(),
            'clock_in'  => today()->setTime(9, 0)->toDateTimeString(),
            'clock_out' => today()->setTime(18, 0)->toDateTimeString(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }

    /**
     * 出勤処理でステータスが出勤中になる（ID6）
     */
    public function test_出勤処理でステータスが出勤中になる(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/attendance')->assertSee('出勤');

        $this->actingAs($user)->post('/attendance/clock_in');

        $this->actingAs($user)->get('/attendance')->assertSee('出勤中');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * 退勤済のユーザーには出勤ボタンが表示されない（ID6）
     */
    public function test_退勤済のユーザーには出勤ボタンが表示されない(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => today()->toDateString(),
            'clock_in'  => now()->setTime(9, 0)->toDateTimeString(),
            'clock_out' => now()->setTime(18, 0)->toDateTimeString(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('退勤済');
        $response->assertDontSee('出勤</button>');
    }

    /**
     * 出勤時刻が勤怠一覧で確認できる（ID6）
     */
    public function test_出勤時刻が勤怠一覧で確認できる(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        // テストの再現性を保つため、日付と時刻を固定する
        $now = Carbon::create(2026, 8, 18, 10, 30, 0);
        Carbon::setTestNow($now);

        $this->actingAs($user)->post('/attendance/clock_in');

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);

        $attendance = Attendance::where('user_id', $user->id)->first();
        $this->assertEquals(
            '2026-08-18 10:30:00',
            Carbon::parse($attendance->clock_in)->format('Y-m-d H:i:s')
        );

        Carbon::setTestNow();
    }

    /**
     * 休憩入でステータスが休憩中になる（ID7）
     */
    public function test_休憩入でステータスが休憩中になる(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->post('/attendance/clock_in');

        $this->actingAs($user)->post('/attendance/break_in');

        $this->actingAs($user)->get('/attendance')->assertSee('休憩中');
    }

    /**
     * 休憩戻でステータスが出勤中に戻る（ID7）
     */
    public function test_休憩戻でステータスが出勤中に戻る(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->post('/attendance/clock_in');

        $this->actingAs($user)->post('/attendance/break_in');

        $this->actingAs($user)->post('/attendance/break_out');

        $this->actingAs($user)->get('/attendance')->assertSee('出勤中');
    }

    /**
     * 休憩は1日に複数回できる（ID7）
     */
    public function test_休憩は1日に複数回できる(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->post('/attendance/clock_in');

        $this->actingAs($user)->post('/attendance/break_in');

        $this->actingAs($user)->post('/attendance/break_out');

        $this->actingAs($user)->post('/attendance/break_in');

        $this->actingAs($user)->post('/attendance/break_out');

        $attendance = Attendance::where('user_id', $user->id)->first();
        $this->assertEquals(2, $attendance->breaks()->count());
    }

    /**
     * 休憩時刻が正確に記録される（ID7）
     */
    public function test_休憩時刻が正確に記録される(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Carbon::setTestNow(today()->setTime(10, 30));

        $this->actingAs($user)->post('/attendance/clock_in');

        $this->actingAs($user)->post('/attendance/break_in');

        $this->actingAs($user)->post('/attendance/break_out');

        $attendance = Attendance::where('user_id', $user->id)->first();

        $this->assertEquals(
            today()->setTime(10, 30)->format('Y-m-d H:i:s'),
            Carbon::parse($attendance->clock_in)->format('Y-m-d H:i:s')
        );

        Carbon::setTestNow();
    }

    /**
     * 退勤ボタンを押したらステータスが退勤済になる（ID8）
     */
    public function test_退勤ボタンを押したらステータスが退勤済になる(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->post('/attendance/clock_in');

        $this->actingAs($user)->post('/attendance/clock_out');

        $this->actingAs($user)->get('/attendance')->assertSee('退勤済');
    }

    /**
     * 退勤時刻が正確に記録される（ID8）
     */
    public function test_退勤時刻が正確に記録される(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        // テストの為時刻は固定
        Carbon::setTestNow(today()->setTime(18, 0, 0));

        $this->actingAs($user)->post('/attendance/clock_in');

        $this->actingAs($user)->post('/attendance/clock_out');

        $attendance = Attendance::where('user_id', $user->id)->first();

        $this->assertEquals(
            today()->setTime(18, 0, 0)->format('Y-m-d H:i:s'),
            Carbon::parse($attendance->clock_out)->format('Y-m-d H:i:s')
        );

        Carbon::setTestNow();
    }
}
