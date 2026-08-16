<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceReportService
{
    /** 始業時刻（遅刻判定の基準） */
    private const WORK_START = '09:00';

    /** 終業時刻（早退判定の基準） */
    private const WORK_END = '18:00';

    /** 残業判定の基準（分）＝8時間 */
    private const OVERTIME_THRESHOLD = 480;

    /** 長時間労働判定の基準（分）＝10時間 */
    private const LONG_WORK_THRESHOLD = 600;

    /**
     * 対象ユーザーのレポートデータを組み立てる
     *
     * @param  User  $user  対象ユーザー
     * @return array{summary: array, monthlyTrend: Collection, anomalies: array}
     */
    public function build(User $user): array
    {
        // 過去6ヶ月（当月含む）の範囲
        $from = Carbon::today()->startOfMonth()->subMonths(5);
        $to   = Carbon::today()->endOfMonth();

        // N+1防止：休憩をEager Loadで一括取得
        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        return [
            'summary'      => $this->summary($attendances),
            'monthlyTrend' => $this->monthlyTrend($attendances, $from),
            'anomalies'    => $this->anomalies($attendances),
        ];
    }

    /**
     * 基本サマリー（総労働時間・総残業時間・平均労働時間）
     *
     * @param  Collection  $attendances
     * @return array{totalWork: int, totalOvertime: int, averageWork: int}
     */
    private function summary(Collection $attendances): array
    {
        // 労働があった日（出勤・退勤が揃う日）だけ対象
        $worked = $attendances->filter(
            fn(Attendance $a) => $a->clock_in && $a->clock_out
        );

        $totalWork = $worked->sum(fn(Attendance $a) => $this->workMinutes($a));

        $totalOvertime = $worked->sum(function (Attendance $a) {
            $over = $this->workMinutes($a) - self::OVERTIME_THRESHOLD;
            return $over > 0 ? $over : 0;
        });

        $days = $worked->count();
        $averageWork = $days > 0 ? (int) round($totalWork / $days) : 0;

        return [
            'totalWork'     => $totalWork,
            'totalOvertime' => $totalOvertime,
            'averageWork'   => $averageWork,
        ];
    }

    /**
     * 月次推移（過去6ヶ月の労働時間・残業時間を月別に）
     *
     * @param  Collection  $attendances
     * @param  Carbon  $from  集計開始月
     * @return Collection<int, array>
     */
    private function monthlyTrend(Collection $attendances, Carbon $from): Collection
    {
        // 日付の勤怠を「年月」でグループ化
        $grouped = $attendances->groupBy(
            fn(Attendance $a) => Carbon::parse($a->work_date)->format('Y-m')
        );

        // 6ヶ月分の枠を作り、データが無い月は0で埋める
        return collect(range(0, 5))->map(function (int $i) use ($from, $grouped) {
            $month = $from->copy()->addMonths($i);
            $key   = $month->format('Y-m');
            $items = $grouped->get($key, collect());

            $worked = $items->filter(fn(Attendance $a) => $a->clock_in && $a->clock_out);

            $work = $worked->sum(fn(Attendance $a) => $this->workMinutes($a));
            $over = $worked->sum(function (Attendance $a) {
                $o = $this->workMinutes($a) - self::OVERTIME_THRESHOLD;
                return $o > 0 ? $o : 0;
            });

            return [
                'month'    => $month->format('Y-m'),
                'work'     => $work,
                'overtime' => $over,
            ];
        });
    }

    /**
     * 異常検知（当月の遅刻・早退・長時間労働の回数）
     *
     * @param  Collection  $attendances
     * @return array{late: int, early: int, longWork: int}
     */
    private function anomalies(Collection $attendances): array
    {
        $currentMonth = Carbon::today()->format('Y-m');

        // 当月かつ出退勤が揃った日だけ
        $thisMonth = $attendances->filter(function (Attendance $a) use ($currentMonth) {
            return Carbon::parse($a->work_date)->format('Y-m') === $currentMonth
                && $a->clock_in && $a->clock_out;
        });

        // 遅刻：出勤が始業(09:00)より後
        $late = $thisMonth->filter(
            fn(Attendance $a) => $a->clock_in->format('H:i') > self::WORK_START
        )->count();

        // 早退：退勤が終業(18:00)より前
        $early = $thisMonth->filter(
            fn(Attendance $a) => $a->clock_out->format('H:i') < self::WORK_END
        )->count();

        // 長時間労働：労働時間が10時間超
        $longWork = $thisMonth->filter(
            fn(Attendance $a) => $this->workMinutes($a) > self::LONG_WORK_THRESHOLD
        )->count();

        return [
            'late'     => $late,
            'early'    => $early,
            'longWork' => $longWork,
        ];
    }

    /**
     * 1日の実労働時間（分）＝ 退勤 - 出勤 - 休憩合計
     *
     * @param  Attendance  $attendance
     * @return int
     */
    private function workMinutes(Attendance $attendance): int
    {
        if (! $attendance->clock_in || ! $attendance->clock_out) {
            return 0;
        }

        $breakMinutes = $attendance->breaks->sum(function ($break) {
            if (! $break->break_in || ! $break->break_out) {
                return 0;
            }
            return $break->break_in->diffInMinutes($break->break_out);
        });

        return $attendance->clock_in->diffInMinutes($attendance->clock_out) - $breakMinutes;
    }

    /**
     * 分を「H時間M分」表記に変換する
     *
     * @param  int  $minutes
     * @return string
     */
    public static function formatHm(int $minutes): string
    {
        return intdiv($minutes, 60) . 'h' . ($minutes % 60) . 'm';
    }
}
