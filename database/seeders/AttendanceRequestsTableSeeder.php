<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceRequestsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('attendance_requests')->insert([
            [
                'attendance_id'        => 1,
                'break_id'             => 1,
                'requested_clock_in'   => Carbon::today()->setHour(9)->setMinute(30),
                'requested_clock_out'  => Carbon::today()->setHour(18)->setMinute(30),
                'requested_break_in'   => Carbon::today()->setHour(12)->setMinute(10),
                'requested_break_out'  => Carbon::today()->setHour(12)->setMinute(50),
                'status'               => 'pending',
                'reason'               => '出勤時間を誤って入力したため修正をお願いします。',
                'approved_at'          => null,
                'created_at'           => Carbon::now(),
                'updated_at'           => Carbon::now(),
            ],
            [
                'attendance_id'        => 2,
                'break_id'             => 3,
                'requested_clock_in'   => Carbon::yesterday()->setHour(10)->setMinute(15),
                'requested_clock_out'  => Carbon::yesterday()->setHour(19)->setMinute(10),
                'requested_break_in'   => Carbon::yesterday()->setHour(13)->setMinute(0),
                'requested_break_out'  => Carbon::yesterday()->setHour(13)->setMinute(40),
                'status'               => 'approved',
                'reason'               => '休憩時間の入力ミスがあったため修正しました。',
                'approved_at'          => Carbon::now()->subHours(2),
                'created_at'           => Carbon::now(),
                'updated_at'           => Carbon::now(),
            ],
        ]);
    }
}
