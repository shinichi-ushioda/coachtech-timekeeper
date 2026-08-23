<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use \App\Models\Breaks;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;
    /**
     * 自分の勤怠情報が一覧に表示される（ID9）
     */
    public function test_自分の勤怠情報が一覧に表示される(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => today()->toDateString(),
            'clock_in'  => now()->setTime(9, 0)->toDateTimeString(),
            'clock_out' => now()->setTime(18, 0)->toDateTimeString(),
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertSee('09:00');
    }

    /**
     * 勤怠一覧に現在の月が表示される（ID9）
     */
    public function test_勤怠一覧に現在の月が表示される(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => today()->toDateString(),
            'clock_in'  => now()->setTime(9, 0)->toDateTimeString(),
            'clock_out' => now()->setTime(18, 0)->toDateTimeString(),
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertSee(Carbon::now()->format('Y/m'));
    }

    /**
     * 前月の情報が表示される（ID9）
     */
    public function test_前月の情報が表示される(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $subMonth = Carbon::now()->subMonth();

        Carbon::now()->subMonth();

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => $subMonth->startOfMonth()->toDateString(),
            'clock_in'  => $subMonth->setTime(8, 0)->toDateTimeString(),
            'clock_out' => $subMonth->setTime(17, 0)->toDateTimeString(),
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=' . $subMonth->format('Y-m'));

        $response->assertStatus(200);

        $response->assertSee('08:00');

        $response->assertSee($subMonth->format('Y/m'));
    }

    /**
     * 翌月の情報が表示される（ID9）
     */
    public function test_翌月の情報が表示される(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $addMonth = Carbon::now()->addMonth();

        Carbon::now()->addMonth();

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => $addMonth->startOfMonth()->toDateString(),
            'clock_in'  => $addMonth->setTime(7, 0)->toDateTimeString(),
            'clock_out' => $addMonth->setTime(16, 0)->toDateTimeString(),
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=' . $addMonth->format('Y-m'));

        $response->assertStatus(200);

        $response->assertSee('07:00');

        $response->assertSee($addMonth->format('Y/m'));
    }

    /**
     * 詳細画面に遷移できる（ID9）
     */
    public function test_詳細画面に遷移できる(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user);
        // 今日の日付は詳細が開けない設定の為、過去の日付にする
        $workDate = Carbon::now()->subDays(1);

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => $workDate->toDateString(),
            'clock_in'  => $workDate->setTime(9, 0)->toDateTimeString(),
            'clock_out' => $workDate->setTime(18, 0)->toDateTimeString(),
        ]);

        $response = $this->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
    }
}
