<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Breaks;
use App\Models\User;
use \App\Models\AttendanceCorrection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class CorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠と休憩を用意するヘルパー的な準備
     */
    private function makeAttendance(User $user): Attendance
    {
        $date = Carbon::now()->subMonth()->startOfMonth();
        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in'  => $date->copy()->setTime(9, 0)->toDateTimeString(),
            'clock_out' => $date->copy()->setTime(18, 0)->toDateTimeString(),
        ]);
        Breaks::create([
            'attendance_id' => $attendance->id,
            'break_in'      => $date->copy()->setTime(12, 0)->toDateTimeString(),
            'break_out'     => $date->copy()->setTime(13, 0)->toDateTimeString(),
        ]);
        return $attendance;
    }
    /**
     * 出勤時間が退勤時間より後だとエラー（ID11）
     */
    public function test_出勤時間が退勤時間より後だとエラー(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $attendance = $this->makeAttendance($user);

        $response = $this->actingAs($user)->post('/stamp_correction_request/store', [
            'attendance_id'       => $attendance->id,
            'requested_clock_in'  => '19:00',
            'requested_clock_out' => '18:00',
            'reason'              => 'テスト中',
        ]);

        $response->assertSessionHasErrors([
            'requested_clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 休憩開始が不適切だとエラー（ID11）
     */
    public function test_休憩開始が不適切だとエラー(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $attendance = $this->makeAttendance($user);

        // 休憩開始が8:00(出勤9:00)で不適切
        $response = $this->actingAs($user)->post('/stamp_correction_request/store', [
            'attendance_id'  => $attendance->id,
            'requested_clock_in'  => 'o9:00',
            'requested_clock_out' => '18:00',
            'requested_breaks'    => [
                [
                    'in' => '8:00',
                    'out' => '08:30'
                ]
            ],
            'reason'              => 'テスト中',

        ]);

        $response->assertSessionHasErrors([
            'requested_breaks.0.in' => '休憩時間が不適切な値です'
        ]);
    }

    /**
     * 休憩終了が退勤後だとエラー（ID11）
     */
    public function test_休憩終了が退勤後だとエラー(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $attendance = $this->makeAttendance($user);

        $response = $this->actingAs($user)->post('/stamp_correction_request/store', [
            'attendance_id'  => $attendance->id,
            'requested_clock_in'  => 'o9:00',
            'requested_clock_out' => '18:00',
            'requested_breaks'    => [
                [
                    'in' => '12:00',
                    'out' => '19:00'
                ]
            ],
            'reason'              => 'テスト中',
        ]);

        $response->assertSessionHasErrors([
            'requested_breaks.0.out' => '休憩時間もしくは退勤時間が不適切な値です'
        ]);
    }

    /**
     * 備考未入力だとエラー（ID11）
     */
    public function test_備考未入力だとエラー(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $attendance = $this->makeAttendance($user);

        $response = $this->actingAs($user)->post('/stamp_correction_request/store', [
            'attendance_id'  => $attendance->id,
            'requested_clock_in'  => '09:00',
            'requested_clock_out' => '18:00',
            'reason'              => '',
        ]);

        $response->assertSessionHasErrors([
            'reason' => '備考を記入してください',
        ]);
    }
    /**
     * 修正申請が実行される（ID11）
     */
    public function test_修正申請が実行される(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $attendance = $this->makeAttendance($user);

        $response = $this->actingAs($user)->post('/stamp_correction_request/store', [
            'attendance_id'  => $attendance->id,
            'requested_clock_in'  => '10:00',
            'requested_clock_out' => '19:00',
            'reason'              => 'テスト中',
        ]);

        $this->assertDatabaseHas('attendance_corrections', [
            'attendance_id' => $attendance->id,
            'status'        => 'pending',
            'reason'        => 'テスト中',
        ]);
    }

    /**
     * 承認待ちに自分の申請が表示される（ID11）
     */
    public function test_承認待ちに自分の申請が表示される(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $attendance = $this->makeAttendance($user);

        $this->actingAs($user)->post('/stamp_correction_request/store', [
            'attendance_id'  => $attendance->id,
            'requested_clock_in'  => '10:00',
            'requested_clock_out' => '19:00',
            'reason'              => 'テスト中',
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/store');
        $attendance = $this->makeAttendance($user);

        AttendanceCorrection::create([
            'attendance_id'       => $attendance->id,
            'requested_clock_in'  => '10:00',
            'requested_clock_out' => '19:00',
            'reason'              => '承認待ちテスト',
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('承認待ちテスト');
    }

    /**
     * 承認済みに承認された申請が表示される（ID11）
     */
    public function test_承認済みに承認された申請が表示される(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $attendance = $this->makeAttendance($user);

        $dateOnly = Carbon::parse($attendance->work_date)->format('Y-m-d');

        // 承認済みの申請を直接作る
        AttendanceCorrection::create([
            'attendance_id'       => $attendance->id,
            'requested_clock_in'  => '2026-07-01 10:00:00',
            'requested_clock_out' => '2026-07-01 19:00:00',
            'requested_breaks'    => null,
            'reason'              => '承認済みテスト',
            'status'              => 'approved',
            'approved_at'         => now(),
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list?status=approved');

        $response->assertStatus(200);

        $response->assertSee('承認済みテスト');
    }

    /**
     * 申請詳細（勤怠詳細）に遷移できる（ID11）
     */
    public function test_申請から詳細画面に遷移できる(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $attendance = $this->makeAttendance($user);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
    }
}
