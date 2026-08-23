<?php

namespace Database\Factories;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Breaks;
use Carbon\Carbon;

/**
 * @extends Factory<Model>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'user_id'   => User::factory(),
            'work_date' => now()->toDateString(),
            'clock_in'  => '09:00', // configure() で work_date と結合
            'clock_out' => '18:00',
        ];
    }

    public function configure(): static
    {
        return $this
            // 時刻文字列 + work_date -> datetime
            ->afterMaking(function (Attendance $attendance) {
                $date = Carbon::parse($attendance->work_date)->toDateString();
                // モデルのdatetimeキャストで clock_in/out は既に日時化されているため、
                // 時刻部分(H:i:s)だけを取り出して work_date と結合する（日付の二重化を防ぐ）
                $in  = Carbon::parse($attendance->clock_in)->format('H:i:s');
                $out = Carbon::parse($attendance->clock_out)->format('H:i:s');
                $attendance->clock_in  = Carbon::parse("{$date} {$in}");
                $attendance->clock_out = Carbon::parse("{$date} {$out}");
            })
            // 固定休憩 12:00-13:00 を付与
            ->afterCreating(function (Attendance $attendance) {
                $date = Carbon::parse($attendance->work_date)->toDateString();
                Breaks::create([
                    'attendance_id' => $attendance->id,
                    'break_in'  => Carbon::parse("{$date} 12:00"),
                    'break_out' => Carbon::parse("{$date} 13:00"),
                ]);
            });
    }

    /** 通常勤務 9:00-18:00（休憩1h -> 実働8h） */
    public function regular(): static
    {
        return $this->state(fn() => [
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
        ]);
    }

    /** 残業 9:00-20:00（実働10h / 8h超過分2h） */
    public function overtime(): static
    {
        return $this->state(fn() => [
            'clock_in'  => '09:00',
            'clock_out' => '20:00',
        ]);
    }

    /** 遅刻 9:30-18:00（始業09:00超過） */
    public function late(): static
    {
        return $this->state(fn() => [
            'clock_in'  => '09:30',
            'clock_out' => '18:00',
        ]);
    }

    /** 早退 9:00-17:00（終業18:00より前） */
    public function early(): static
    {
        return $this->state(fn() => [
            'clock_in'  => '09:00',
            'clock_out' => '17:00',
        ]);
    }

    /** 長時間労働 8:00-21:00（実働12h / 1日10時間超） */
    public function longWork(): static
    {
        return $this->state(fn() => [
            'clock_in'  => '08:00',
            'clock_out' => '21:00',
        ]);
    }
}
