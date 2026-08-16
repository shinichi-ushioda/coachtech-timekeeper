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
     * 当月：当月初日から「昨日まで」の平日に勤怠を生成する。
     *       ※当日を含む未来には勤怠を作らない。
     *
     * 日数が17日に満たない場合でも、遅刻・早退・長時間などの
     * 異常パターンを優先的に配置し、残りを通常勤務で埋める。
     */
    private function seedCurrentMonth(User $user1): void
    {
        // 異常パターンを先頭に置く（少ない日数でも確実に入るように）
        $priorityPatterns = array_merge(
            ['longWork'],                  // 長時間  1
            array_fill(0, 2, 'late'),      // 遅刻    2
            ['early'],                     // 早退    1
            array_fill(0, 3, 'overtime'),  // 残業    3
            array_fill(0, 10, 'regular'),  // 通常   10
        );

        // 当月初日から「昨日まで」の平日を集める（当日・未来は除外）
        $weekdays  = [];
        $cursor    = now()->startOfMonth();
        $yesterday = now()->subDay()->startOfDay();

        while ($cursor->lte($yesterday)) {
            if ($cursor->isWeekday()) {
                $weekdays[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

        // 集めた平日の日数分だけ、先頭からパターンを割り当てる
        foreach ($weekdays as $i => $date) {
            $state = $priorityPatterns[$i] ?? 'regular';

            Attendance::factory()
                ->{$state}()
                ->create([
                    'user_id'   => $user1->id,
                    'work_date' => $date,
                ]);
        }
    }
}

