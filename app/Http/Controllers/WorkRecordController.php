<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;
use App\Http\Requests\StampCorrectionRequest;

class WorkRecordController extends Controller
{
    /**
     * 勤怠一覧画面（PG04）
     */
    public function list(Request $request): View
    {
        $userId = Auth::id();

        // 現在の月 or クエリパラメータの月
        $currentMonth = $request->query('month', Carbon::now()->format('Y-m'));

        // Carbon オブジェクトに変換
        $monthObj = Carbon::createFromFormat('Y-m', $currentMonth);

        // 月初〜月末
        $start = $monthObj->copy()->startOfMonth();
        $end   = $monthObj->copy()->endOfMonth();

        // この月の勤怠を取得（休憩データ breaks も一緒に取得）
        $attendances = Attendance::with('breaks')
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->orderBy('work_date')
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->work_date)->format('Y-m-d');
            });

        // 月の日付一覧を作成（勤務していない日も表示するため）
        $days = [];
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $date = $cursor->format('Y-m-d');

            $attendance = $attendances->get($date);

            $totalBreakMinutes = 0;
            $workTimeStr = '';
            $breakTimeStr = '';

            if ($attendance) {
                // 休憩時間の計算
                foreach ($attendance->breaks as $break) {
                    if ($break->break_in && $break->break_out) {
                        $breakIn = Carbon::parse($break->break_in);
                        $breakOut = Carbon::parse($break->break_out);
                        $totalBreakMinutes += $breakIn->diffInMinutes($breakOut);
                    }
                }


                // 休憩時間を H:i 形式に
                if ($totalBreakMinutes > 0) {
                    $breakTimeStr = sprintf('%02d:%02d', floor($totalBreakMinutes / 60), $totalBreakMinutes % 60);
                } else if ($attendance->clock_in) {
                    $breakTimeStr = '00:00';
                }

                // 実働時間の計算（退勤している場合のみ）
                if ($attendance->clock_in && $attendance->clock_out) {
                    $clockIn = Carbon::parse($attendance->clock_in);
                    $clockOut = Carbon::parse($attendance->clock_out);

                    $totalStayMinutes = (int)$clockIn->diffInMinutes($clockOut);
                    $totalWorkMinutes = $totalStayMinutes - (int)$totalBreakMinutes;
                    if ($totalWorkMinutes < 0) {
                        $totalWorkMinutes = 0;
                    }

                    $workTimeStr = sprintf('%02d:%02d', floor($totalWorkMinutes / 60), $totalWorkMinutes % 60);
                }
            }

            $days[] = [
                'date' => $cursor->copy(),
                'attendance' => $attendance,
                'break_time' => $breakTimeStr,
                'work_time' => $workTimeStr,
            ];
            $cursor->addDay();
        }

        return view('layouts.list', [
            'days' => $days,
            'currentMonth' => $currentMonth,
            'prevMonth' => $monthObj->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $monthObj->copy()->addMonth()->format('Y-m'),
        ]);
    }


    /**
     * 勤怠詳細画面（PG05）
     */
    public function detail(int $id): View|RedirectResponse
    {
        // 勤怠データ・休憩・修正申請をまとめて取得
        $attendance = Attendance::with(['breaks', 'correction'])->findOrFail($id);

        // 当日を含む未来の日付は詳細を開けない
        if ($attendance->work_date->gte(Carbon::today())) {
            return redirect()
                ->route('attendance.list')
                ->with('error', '当日以降の日付は登録できません。');
        }

        $breaks = $attendance->breaks()->orderBy('break_in')->get();
        $request = $attendance->correction;

        return view('layouts.detail', [
            'attendance' => $attendance,
            'breaks' => $breaks,
            'request' => $request,
        ]);
    }

    /**
     * 中継用の処理（勤怠が無い日の詳細）
     */
    public function detailByDate(string $date): RedirectResponse
    {
        // 当日を含む未来の日付は勤怠を作成・修正できない
        if (Carbon::parse($date)->gte(Carbon::today())) {
            return back()->with('error', '当日以降の日付は修正申請はできません。');
        }

        // その日付のデータを検索し、無ければ空の勤怠レコードを作成する
        $attendance = Attendance::firstOrCreate([
            'user_id' => auth()->id(),
            'work_date'    => $date,
        ], [
            'clock_in'  => null,
            'clock_out' => null,
        ]);

        return redirect()->route('attendance.detail', ['id' => $attendance->id]);
    }

    /**
     * 申請一覧画面（PG06 / PG12）
     * 同じパス /stamp_correction_request/list を、ログインユーザーの種別で分岐する
     */
    public function correctionRequestList(Request $request): View
    {
        $user = Auth::user();

        // タブの状態（pending / approved）
        $status = $request->input('status', 'pending');

        // 管理者：全ユーザーの申請を表示
        if ($user->admin_status) {
            $requests = AttendanceCorrection::with(['attendance.user'])
                ->where('status', $status)
                ->join('attendances', 'attendance_corrections.attendance_id', '=', 'attendances.id')
                ->orderBy('attendances.work_date', 'asc')
                ->select('attendance_corrections.*')
                ->get();

            return view('admin.correction_request_list', compact('requests', 'status'));
        }

        // 一般ユーザー：自分の申請のみ表示
        $correctionRequests = AttendanceCorrection::with('attendance')
            ->where('status', $status)
            ->whereHas('attendance', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->join('attendances', 'attendance_corrections.attendance_id', '=', 'attendances.id')
            ->orderBy('attendances.work_date', 'asc')
            ->select('attendance_corrections.*')
            ->get();

        return view('layouts.correction_request_list', compact('correctionRequests', 'status'));
    }

    /**
     * 修正申請の保存（勤怠詳細画面からの POST）
     */
    public function correctionRequestStore(StampCorrectionRequest $request): RedirectResponse
    {
        // 対象勤怠が当日を含む未来なら申請不可
        $attendance = Attendance::findOrFail($request->attendance_id);
        if ($attendance->work_date->gte(Carbon::today())) {
            return back()->with('error', '当日以降の日付は修正申請はできません。');
        }

        // すでに同じ勤怠IDで「承認待ち（pending）」の申請がないかチェック
        $exists = AttendanceCorrection::where('attendance_id', $request->attendance_id)
            ->where('status', 'pending')
            ->exists();

        if ($exists) {
            return back()->with('error', '既に修正申請が提出されているため、再申請はできません。');
        }

        // 送られてきた休憩データを配列にまとめる処理
        $formattedBreaks = [];

        foreach ($request->input('requested_breaks', []) as $break) {
            if (! empty($break['in']) && ! empty($break['out'])) {
                $formattedBreaks[] = [
                    'break_in'  => $break['in'],
                    'break_out' => $break['out'],
                ];
            }
        }

        $newBreak = $request->input('requested_breaks_new', []);
        if (!empty($newBreak['in']) && !empty($newBreak['out'])) {
            $formattedBreaks[] = [
                'break_in'  => $newBreak['in'],
                'break_out' => $newBreak['out'],
            ];
        }

        AttendanceCorrection::create([
            'attendance_id'       => $request->attendance_id,
            'requested_clock_in'  => $request->requested_clock_in,
            'requested_clock_out' => $request->requested_clock_out,
            'requested_breaks'    => count($formattedBreaks) > 0 ? $formattedBreaks : null,
            'reason'              => $request->reason,
            'status'              => 'pending',
        ]);

        return back()->with('message', '修正申請を送信しました。');
    }
}
