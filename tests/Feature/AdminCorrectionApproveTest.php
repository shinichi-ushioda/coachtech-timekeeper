<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class AdminCorrectionApproveTest extends TestCase
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
     * 過去日の勤怠と、承認待ちの修正申請を作成
     */
    private function makeCorrection(string $status = 'pending'): AttendanceCorrection
    {
        $user = User::factory()->create(['name' => '申請 太郎']);
        $date = Carbon::now()->subMonth()->startOfMonth();
        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in'  => $date->copy()->setTime(9, 0)->toDateTimeString(),
            'clock_out' => $date->copy()->setTime(18, 0)->toDateTimeString(),
        ]);

        return AttendanceCorrection::create([
            'attendance_id'       => $attendance->id,
            'requested_clock_in'  => '2026-07-01 10:00:00',
            'requested_clock_out' => '2026-07-01 19:00:00',
            'requested_breaks'    => null,
            'reason'              => '修正申請テスト',
            'status'              => $status,
            'approved_at'         => $status === 'approved' ? now() : null,
        ]);
    }

    /**
     * 承認待ちの申請が一覧に表示される（ID15）
     */
    public function test_全ユーザーの未承認の修正申請が表示される(): void
    {
        $admin = $this->admin();

        $this->makeCorrection('pending');

        $response = $this->actingAs($admin)
            ->get('/stamp_correction_request/list?status=pending');

        $response->assertStatus(200);
        $response->assertSee('修正申請テスト');
    }

    /**
     * 承認済みの申請が一覧に表示される（ID15）
     */
    public function test_全ユーザーの承認済みの修正申請が表示される(): void
    {
        $admin = $this->admin();

        $this->makeCorrection('approved');

        $response = $this->actingAs($admin)
            ->get('/stamp_correction_request/list?status=approved');

        $response->assertStatus(200);
        $response->assertSee('修正申請テスト');
    }

    /**
     * 申請詳細の内容が正しく表示される（ID15）
     */
    public function test_申請詳細の内容が表示される(): void
    {
        $admin = $this->admin();
        $correction = $this->makeCorrection('pending');

        // 申請詳細（承認画面）を開く
        $response = $this->actingAs($admin)
            ->get('/stamp_correction_request/approve/' . $correction->id);

        $response->assertStatus(200);
        // 申請したユーザー名と理由が表示される
        $response->assertSee('申請 太郎');
        $response->assertSee('修正申請テスト');
    }

    /**
     * 承認処理で申請が承認され、勤怠が更新される（ID15）
     */
    public function test_承認処理で申請が承認され勤怠が更新される(): void
    {
        $admin = $this->admin();
        $correction = $this->makeCorrection('pending');

        // 承認処理（POST）
        $response = $this->actingAs($admin)
            ->post('/stamp_correction_request/approve/' . $correction->id);

        // 申請が承認済みになっている
        $this->assertDatabaseHas('attendance_corrections', [
            'id'     => $correction->id,
            'status' => 'approved',
        ]);

        // 勤怠本体が申請内容（出勤10:00）で更新されている
        $this->assertDatabaseHas('attendances', [
            'id'       => $correction->attendance_id,
            'clock_in' => '2026-07-01 10:00:00',
        ]);
    }
}
