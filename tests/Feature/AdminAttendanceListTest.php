<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;


class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者ユーザーを作るヘルパー
     */
    private function admin(): User
    {
        return User::factory()->create([
            'admin_status'      => true,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * その日の全ユーザーの勤怠が表示される（ID12）
     */
    public function test_その日の全ユーザーの勤怠が表示される(): void
    {
        $admin = $this->admin();

        $date = Carbon::yesterday();
        $user1 = User::factory()->create(['name' => 'テスト太郎']);
        $user2 = User::factory()->create(['name' => 'テスト花子']);

        Attendance::create([
            'user_id' => $user1->id,
            'work_date' => $date->toDateString(),
            'clock_in' => $date->copy()->setTime(9, 0)->toDateTimeString(),
            'clock_out' => $date->copy()->setTime(18, 0)->toDateTimeString(),
        ]);

        Attendance::create([
            'user_id' => $user2->id,
            'work_date' => $date->toDateString(),
            'clock_in' => $date->copy()->setTime(10, 0)->toDateTimeString(),
            'clock_out' => $date->copy()->setTime(19, 0)->toDateTimeString(),
        ]);

        // 管理者としてログインし、その日の勤怠一覧を開く
        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=' . $date->toDateString());

        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
        $response->assertSee('テスト花子');
    }

    /**
     * 管理者一覧に現在の日付が表示される（ID12）
     */
    public function test_管理者一覧に現在の日付が表示される(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);

        $response->assertSee(Carbon::now()->format('Y/m/d'));
    }

    /**
     * 前日の勤怠が表示される（ID12）
     */
    public function test_前日の勤怠が表示される(): void
    {
        $admin = $this->admin();

        $prevDate = Carbon::yesterday();

        $user = User::factory()->create(['name' => '前日太郎']);
        Attendance::create([
            'user_id'  => $user->id,
            'work_date' => $prevDate->toDateString(),
            'clock_in'  => $prevDate->copy()->setTime(8, 0)->toDateTimeString(),
            'clock_out'  => $prevDate->copy()->setTime(17, 0)->toDateTImeString(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=' . $prevDate->toDateString());

        $response->assertStatus(200);

        $response->assertSee('前日太郎');

        $response->assertSee($prevDate->format('Y/m/d'));
    }

    /**
     * 翌日の勤怠が表示される（ID12）
     */
    public function test_翌日の勤怠が表示される(): void
    {
        $admin = $this->admin();

        $nextDate = Carbon::tomorrow();

        $user = User::factory()->create(['name' => '翌日花子']);
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $nextDate->toDateString(),
            'clock_in'  => $nextDate->copy()->setTime(9, 0)->toDateString(),
            'clock_out'  => $nextDate->copy()->setTime(18, 0)->toDateTimeString(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=' . $nextDate->toDateString());

        $response->assertStatus(200);
        $response->assertSee('翌日花子');
        $response->assertSee($nextDate->format('Y/m/d'));
    }
}
