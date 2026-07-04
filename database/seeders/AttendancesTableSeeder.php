<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('attendances')->insert([
            [
                'user_id'     => 1,
                'work_date'   => Carbon::today(),
                'clock_in'    => Carbon::today()->setHour(9)->setMinute(0),
                'clock_out'   => Carbon::today()->setHour(18)->setMinute(0),
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ],
            [
                'user_id'     => 2,
                'work_date'   => Carbon::today()->subDay(),
                'clock_in'    => Carbon::today()->subDay()->setHour(10)->setMinute(0),
                'clock_out'   => Carbon::today()->subDay()->setHour(19)->setMinute(0),
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ],
            [
                'user_id'     => 3, // 管理者ユーザー
                'work_date'   => Carbon::today()->subDays(2),
                'clock_in'    => Carbon::today()->subDays(2)->setHour(8)->setMinute(30),
                'clock_out'   => Carbon::today()->subDays(2)->setHour(17)->setMinute(30),
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ],
        ]);
    }
}


