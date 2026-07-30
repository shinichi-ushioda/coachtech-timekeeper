<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * user1 の意図的ダミーデータを生成する。
 *
 * ■過去分：直近5ヶ月 × 各月平日15日 = 75日（すべて通常勤務）
 * ■当月  ：平日17日
 *          通常10 / 残業3 / 遅刻2 / 早退1 / 長時間1
 * ■全レコードに固定休憩 12:00-13:00(1h) を付与（Factory側で自動）
 *
 * 期待値（/attendance/report, 過去6ヶ月＝過去分＋当月）:
 *   総労働時間 744h / 平均 8時間5分 / 残業(8h超過分) 10h
 *   当月 遅刻2 / 早退1 / 長時間1
 */
class AttendancesTableSeeder extends Seeder
{
    public function run(): void
    {
        // 対象ユーザー（メール/IDは実データに合わせて変更可）
        $user1 = User::where('email', 'user1@example.com')->first();

        if (! $user1) {
            $this->command->warn('user1 が見つかりません。UsersTableSeeder を先に実行してください。');
            return;
        }

        $this->seedPastMonths($user1);
        $this->seedCurrentMonth($user1);
    }

    /**
     * 過去分：直近5ヶ月、各月の平日15日を通常勤務で生成（当月は含めない）。
     */
    private function seedPastMonths(User $user1): void
    {
        for ($month = 1; $month <= 5; $month++) {
            $cursor = now()->subMonths($month)->startOfMonth();
            $created = 0;

            while ($created < 15) {
                if ($cursor->isWeekday()) {
                    Attendance::factory()
                        ->regular()
                        ->create([
                            'user_id'   => $user1->id,
                            'work_date' => $cursor->toDateString(),
                        ]);
                    $created++;
                }
                $cursor->addDay();
            }
        }
    }

    /**
     * 当月：平日17日を取得し、パターンを割り当てて生成。
     */
    private function seedCurrentMonth(User $user1): void
    {
        // 割り当てるパターン（合計17）
        $patterns = array_merge(
            array_fill(0, 10, 'regular'),  // 通常   10
            array_fill(0, 3,  'overtime'), // 残業    3
            array_fill(0, 2,  'late'),     // 遅刻    2
            ['early'],                     // 早退    1
            ['longWork'],                  // 長時間  1
        );

        $cursor = now()->startOfMonth();
        $index  = 0;

        while ($index < count($patterns)) {
            if ($cursor->isWeekday()) {
                $state = $patterns[$index];

                Attendance::factory()
                    ->{$state}()
                    ->create([
                        'user_id'   => $user1->id,
                        'work_date' => $cursor->toDateString(),
                    ]);

                $index++;
            }
            $cursor->addDay();
        }
    }
}

