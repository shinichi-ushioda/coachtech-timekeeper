<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class AdminStaffTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'admin_status'      => true,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * スタッフ一覧に全一般ユーザーの氏名とメールが表示される（ID14）
     */
    public function test_スタッフ一覧に全ユーザーの氏名とメールが表示される(): void
    {
        $admin = $this->admin();

        $user1 = User::factory()->create(['name' => '田中一郎', 'email' => 'tanaka@example.com']);
        $user2 = User::factory()->create(['name' => '佐藤花子', 'email' => 'sato@example.com']);

        $response = $this->actingAs($admin)->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertSee('田中一郎');
        $response->assertSee('tanaka@example.com');
        $response->assertSee('佐藤花子');
        $response->assertSee('sato@example.com');
    }

    /**
     * 選択したユーザーの勤怠情報が表示される（ID14）
     */
    public function test_選択したユーザーの勤怠が表示される(): void
    {
        $admin = $this->admin();

        // 一般ユーザーと、その勤怠（今月）を作る
        $user = User::factory()->create(['name' => '勤怠 太郎']);
        $date = Carbon::now()->startOfMonth();
        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in'  => $date->copy()->setTime(9, 0)->toDateTimeString(),
            'clock_out' => $date->copy()->setTime(18, 0)->toDateTimeString(),
        ]);

        // そのユーザーのスタッフ別勤怠画面を開く
        $response = $this->actingAs($admin)->get('/admin/attendance/staff/' . $user->id);

        $response->assertStatus(200);
        // ユーザー名と勤怠（出勤時刻）が表示される
        $response->assertSee('勤怠 太郎');
        $response->assertSee('09:00');
    }

    /**
     * 前月の勤怠が表示される（ID14）
     */
    public function test_前月の勤怠が表示される(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => '勤怠 太郎']);

        // 前月の勤怠を作る
        $prevMonth = Carbon::now()->subMonth()->startOfMonth();
        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => $prevMonth->toDateString(),
            'clock_in'  => $prevMonth->copy()->setTime(8, 0)->toDateTimeString(),
            'clock_out' => $prevMonth->copy()->setTime(17, 0)->toDateTimeString(),
        ]);

        // 前月を指定して開く
        $response = $this->actingAs($admin)
            ->get('/admin/attendance/staff/' . $user->id . '?month=' . $prevMonth->format('Y-m'));

        $response->assertStatus(200);
        // 前月の勤怠（08:00）が表示される
        $response->assertSee('08:00');
    }

    /**
     * 翌月の勤怠が表示される（ID14）
     */
    public function test_翌月の勤怠が表示される(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => '勤怠 太郎']);

        // 前月の勤怠を作る
        $nextMonth = Carbon::now()->addMonth()->startOfMonth();
        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => $nextMonth->toDateString(),
            'clock_in'  => $nextMonth->copy()->setTime(7, 0)->toDateTimeString(),
            'clock_out' => $nextMonth->copy()->setTime(16, 0)->toDateTimeString(),
        ]);

        // 前月を指定して開く
        $response = $this->actingAs($admin)
            ->get('/admin/attendance/staff/' . $user->id . '?month=' . $nextMonth->format('Y-m'));

        $response->assertStatus(200);
        // 前月の勤怠（08:00）が表示される
        $response->assertSee('07:00');
    }

    /**
     * 勤怠詳細画面に遷移できる（ID14）
     */
    public function test_勤怠詳細画面に遷移できる(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => '勤怠 太郎']);

        // 過去日の勤怠を作る（詳細画面は過去日で開く）
        $date = Carbon::now()->subMonth()->startOfMonth();
        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in'  => $date->copy()->setTime(9, 0)->toDateTimeString(),
            'clock_out' => $date->copy()->setTime(18, 0)->toDateTimeString(),
        ]);

        // 管理者用の勤怠詳細に遷移
        $response = $this->actingAs($admin)->get('/admin/attendance/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('勤怠 太郎');
    }
}
