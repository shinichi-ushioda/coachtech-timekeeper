<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use App\Http\Requests\AdminAttendanceUpdateRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAttendanceController extends Controller
{
    /**
     * 勤怠一覧画面（PG08）その日の全ユーザー
     */
    public function index(Request $request): View
    {
        // 日付指定がなければ今日
        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::today();

        // 一般ユーザーを全件取得
        $users = User::where('admin_status', false)
            ->orderBy('id')
            ->get();

        // その日の勤怠を user_id をキーにして取得
        $attendances = Attendance::with('breaks')
            ->whereDate('work_date', $date->toDateString())
            ->get()
            ->keyBy('user_id');

        return view('admin.attendance_list', [
            'users'       => $users,
            'attendances' => $attendances,
            'date'        => $date,
            'prevDate'    => $date->copy()->subDay()->toDateString(),
            'nextDate'    => $date->copy()->addDay()->toDateString(),
        ]);
    }

    /**
     * スタッフ一覧画面（PG10）
     */
    public function staffList(): View
    {
        $users = User::where('admin_status', false)
            ->orderBy('id')
            ->get();

        return view('admin.staff_list', compact('users'));
    }

    /**
     * スタッフ別勤怠一覧画面（PG11）1ユーザーの1ヶ月分
     */
    public function staff(Request $request, int $id): View
    {
        $user = User::findOrFail($id);

        // 月指定がなければ今月
        $month = $request->input('month')
            ? Carbon::parse($request->input('month') . '-01')
            : Carbon::today()->startOfMonth();

        // その月の勤怠を「日付」をキーにして取得
        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ])
            ->get()
            ->keyBy(fn($item) => $item->work_date->toDateString());

        // 月の全日付を生成（勤怠が無い日も行として表示するため）
        $days = [];
        $cursor = $month->copy()->startOfMonth();
        while ($cursor->lte($month->copy()->endOfMonth())) {
            $days[] = $cursor->copy();
            $cursor->addDay();
        }

        return view('admin.staff_attendance_list', [
            'user'        => $user,
            'attendances' => $attendances,
            'days'        => $days,
            'month'       => $month,
            'prevMonth'   => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth'   => $month->copy()->addMonth()->format('Y-m'),
        ]);
    }

    /**
     * 勤怠が無い日の詳細（空レコードを作成してPG09へ）
     */
    public function showByDate(int $user, string $date): RedirectResponse
    {
        // 当日を含む未来の日付は勤怠を作成・修正できない
        if (Carbon::parse($date)->gte(Carbon::today())) {
            return back()->with('error', '当日以降の日付は修正できません。');
        }

        $attendance = Attendance::firstOrCreate(
            [
                'user_id'   => $user,
                'work_date' => $date,
            ],
            [
                'clock_in'  => null,
                'clock_out' => null,
            ]
        );

        return redirect()->route('admin.attendance.show', ['id' => $attendance->id]);
    }

    /**
     * 勤怠詳細画面（管理者・PG09）
     */
    public function show(int $id): View
    {
        $attendance = Attendance::with(['user', 'breaks'])->findOrFail($id);

        // 承認待ちの修正申請があれば取得（表示の出し分けに使用）
        $request = $attendance->correction()
            ->where('status', 'pending')
            ->latest()
            ->first();

        return view('admin.attendance_detail', [
            'attendance' => $attendance,
            'breaks'     => $attendance->breaks,
            'request'    => $request,
        ]);
    }

    /**
     * 勤怠詳細の更新（管理者による直接修正・PG09 / FN040）
     */
    public function update(AdminAttendanceUpdateRequest $request, int $id): RedirectResponse
    {
        $attendance = Attendance::with('breaks')->findOrFail($id);

        // 当日を含む未来の日付は修正できない
        if ($attendance->work_date->gte(Carbon::today())) {
            return back()->with('error', '当日以降の日付は修正できません。');
        }

        // 出勤・退勤・備考を更新
        $attendance->update([
            'clock_in'  => $request->clock_in,
            'clock_out' => $request->clock_out,
            'comment'   => $request->comment,
        ]);

        // 休憩を作り直す（既存を削除して入力内容で再登録）
        $attendance->breaks()->delete();

        foreach ($request->input('breaks', []) as $break) {
            if (! empty($break['in']) && ! empty($break['out'])) {
                $attendance->breaks()->create([
                    'break_in'  => $break['in'],
                    'break_out' => $break['out'],
                ]);
            }
        }

        return redirect()
            ->route('admin.attendance.show', ['id' => $attendance->id])
            ->with('flashSuccess', '勤怠情報を更新しました');
    }

    /**
     * 修正申請の承認画面（管理者・PG13）
     */
    public function adminCorrectionRequestShow(int $id): View
    {
        $correction = AttendanceCorrection::with(['attendance.user'])->findOrFail($id);
        $isApproved = ($correction->status === 'approved');

        return view('admin.approve', compact('correction', 'isApproved'));
    }

    /**
     * 修正申請の承認処理（管理者・PG13 / FN051）
     */
    public function adminCorrectionRequestApprove(int $id): RedirectResponse
    {
        $correction = AttendanceCorrection::with('attendance')->findOrFail($id);
        $attendance = $correction->attendance;

        // 申請内容を勤怠本体へ反映（出勤・退勤・備考）
        $attendance->update([
            'clock_in'  => $correction->requested_clock_in  ?? $attendance->clock_in,
            'clock_out' => $correction->requested_clock_out ?? $attendance->clock_out,
            'comment'   => $correction->reason,
        ]);

        // 休憩を申請内容で作り直す
        $attendance->breaks()->delete();

        foreach ($correction->requested_breaks ?? [] as $break) {
            if (! empty($break['break_in']) && ! empty($break['break_out'])) {
                $attendance->breaks()->create([
                    'break_in'  => \Carbon\Carbon::parse($break['break_in'])->format('H:i:s'),
                    'break_out' => \Carbon\Carbon::parse($break['break_out'])->format('H:i:s'),
                ]);
            }
        }

        // 申請を承認済みに
        $correction->update([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);

        return redirect()->route('stamp_correction_request.list', ['status' => 'approved']);
    }

    /**
     * スタッフ別勤怠一覧のCSV出力（PG11 / FN045）
     */
    public function exportCsv(Request $request, int $id): StreamedResponse
    {
        $user = User::findOrFail($id);

        // 画面と同じ月の判定
        $month = $request->input('month')
            ? Carbon::parse($request->input('month') . '-01')
            : Carbon::today()->startOfMonth();

        // その月の勤怠を日付キーで取得
        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ])
            ->get()
            ->keyBy(fn($item) => $item->work_date->toDateString());

        // 月の全日付
        $weekMap = ['日', '月', '火', '水', '木', '金', '土'];
        $rows = [];
        $rows[] = ['日付', '出勤', '退勤', '休憩', '合計']; // ヘッダー行

        $cursor = $month->copy()->startOfMonth();
        while ($cursor->lte($month->copy()->endOfMonth())) {
            $attendance = $attendances->get($cursor->toDateString());
            $weekday = $weekMap[$cursor->format('w')];

            $rows[] = [
                $cursor->format('m/d') . '(' . $weekday . ')',
                $attendance?->clock_in ? $attendance->clock_in->format('H:i') : '',
                $attendance?->clock_out ? $attendance->clock_out->format('H:i') : '',
                $attendance ? Attendance::formatMinutes($attendance->totalBreakMinutes()) : '',
                $attendance ? Attendance::formatMinutes($attendance->totalWorkMinutes()) : '',
            ];

            $cursor->addDay();
        }

        // ファイル名（例：西玲奈_2026-06.csv）
        $fileName = $user->name . '_' . $month->format('Y-m') . '.csv';

        // CSVを生成して返す
        $callback = function () use ($rows) {
            $stream = fopen('php://output', 'w');

            // Excelで開いたときの文字化け防止（BOM付きUTF-8）
            fwrite($stream, "\xEF\xBB\xBF");

            foreach ($rows as $row) {
                fputcsv($stream, $row);
            }

            fclose($stream);
        };

        return response()->streamDownload($callback, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
