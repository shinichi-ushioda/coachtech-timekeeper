<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BreaksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('breaks')->insert([
            [
                'attendances_id' => 1,
                'break_in'       => Carbon::now()->subHours(3),
                'break_out'      => Carbon::now()->subHours(2),
                'created_at'     => Carbon::now(),
                'updated_at'     => Carbon::now(),
            ],
            [
                'attendances_id' => 1,
                'break_in'       => Carbon::now()->subHours(1),
                'break_out'      => Carbon::now()->subMinutes(30),
                'created_at'     => Carbon::now(),
                'updated_at'     => Carbon::now(),
            ],
            [
                'attendances_id' => 2,
                'break_in'       => Carbon::now()->subHours(4),
                'break_out'      => Carbon::now()->subHours(3),
                'created_at'     => Carbon::now(),
                'updated_at'     => Carbon::now(),
            ],
        ]);
    }
}
