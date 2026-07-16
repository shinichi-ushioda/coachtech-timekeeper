<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceCorrectionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // テスト実行時の「今日」と「昨日」をベースにする
        $today = Carbon::now();
        $yesterday = Carbon::yesterday();

        DB::table('attendance_corrections')->insert([
            [
                'attendance_id' => 1,
                // 今日の日付の 09:30:00 と 18:30:00 に設定
                'requested_clock_in' => $today->copy()->setTime(9, 30, 0),
                'requested_clock_out' => $today->copy()->setTime(18, 30, 0),
                
                // 休憩時間も今日の「12:10〜12:50」として動的にJSON化
                'requested_breaks' => json_encode([
                    [
                        'break_in'  => $today->copy()->setTime(12, 10, 0)->toDateTimeString(),
                        'break_out' => $today->copy()->setTime(12, 50, 0)->toDateTimeString()
                    ]
                ]),
                'reason' => '出勤時間を誤って入力したため修正をお願いします。',
                'status' => 'pending',
                'approved_at' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'attendance_id' => 2,
                // 昨日の日付の 10:15:00 と 19:10:00 に設定
                'requested_clock_in' => $yesterday->copy()->setTime(10, 15, 0),
                'requested_clock_out' => $yesterday->copy()->setTime(19, 10, 0),
                
                // 昨日の「13:00〜13:40」として動的にJSON化
                'requested_breaks' => json_encode([
                    [
                        'break_in'  => $yesterday->copy()->setTime(13, 0, 0)->toDateTimeString(),
                        'break_out' => $yesterday->copy()->setTime(13, 40, 0)->toDateTimeString()
                    ]
                ]),
                'reason' => '休憩時間の入力ミスがあったため修正しました。',
                'status' => 'approved',
                // 承認日時は今から2時間前などに動的設定
                'approved_at' => Carbon::now()->subHours(2),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }
}
