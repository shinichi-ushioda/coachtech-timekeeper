<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;


class AdminAttendanceUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'admin_status' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function makeAttendance() : Attendance
    {
        $user = User::factory()->create();
        $date = Carbon::now()->subMonth()->startOfMonth();
        return Attendance::create([
            'user_id'   => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in'  => $date->copy()->setTime(9, 0)->toDateTimeString(),
            'clock_out' => $date->copy()->setTime(18, 0)->toDateTimeString(),
        ]);   
    }
    /**
     * 出勤が退勤より後だとエラー（ID13）
     */
    public function test_出勤が退勤より後だとエラー(): void
    {
        $admin = $this->admin();
        $attendance = $this->makeAttendance();

        $response = $this->actingAs($admin)->patch('/admin/attendance/' .$attendance->id,[
            'clock_in'  => '19:00',
            'clock_out' => '18:00',
            'comment'   => 'テスト',
        ]);

        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 休憩開始が不適切だとエラー（ID13）
     */
    public function test_休憩開始が不適切だとエラー(): void
    {
        $admin = $this->admin();
        $attendance = $this->makeAttendance();

        $response = $this->actingAs($admin)->patch('/admin/attendance/' . $attendance->id, [
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'breaks'    => [
                ['in' => '08:00', 'out' => '08:30'],
            ],
            'comment'   => 'テスト',
        ]);

        $response->assertSessionHasErrors([
            'breaks.0.in' => '休憩時間が不適切な値です',
        ]);
    }

    /**
     * 休憩終了が退勤後だとエラー（ID13）
     */
    public function test_休憩終了が退勤後だとエラー(): void
    {
        $admin = $this->admin();
        $attendance = $this->makeAttendance();

        $response = $this->actingAs($admin)->patch('admin/attendance/' . $attendance->id,[
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'breaks'    => [
                ['in' => '12:00', 'out' => '19:00'],
            ],
            'comment'   => 'テスト',
        ]);

        $response->assertSessionHasErrors([
            'breaks.0.out' =>'休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 備考未入力だとエラー（ID13）
     */
    public function test_備考未入力だとエラー(): void
    {
        $admin = $this->admin();
        $attendance = $this->makeAttendance();

        $response = $this->actingAs($admin)->patch('/admin/attendance/' . $attendance->id, [
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'comment'   => '',
        ]);

        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }

    /**
     * 詳細画面に選択した勤怠のデータが表示される（ID13）
     */
    public function test_詳細画面に選択した勤怠のデータが表示される(): void
    {
        $admin = $this->admin();

        $user = User::factory()->create(['name' => '表示 太郎']);
        $date = Carbon::now()->subMonth()->startOfMonth();
        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in'  => $date->copy()->setTime(9, 0)->toDateTimeString(),
            'clock_out' => $date->copy()->setTime(18, 0)->toDateTimeString(),
        ]);
        
        $response = $this->actingAs($admin)->get('/admin/attendance/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('表示 太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }
}