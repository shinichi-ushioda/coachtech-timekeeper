<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * 勤怠登録画面（状態判定）
     */
    public function index()
    {
        $userId = Auth::id();

        // 今日の勤怠を取得
        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('work_date', Carbon::today())
            ->first();

        // 状態判定
        if (!$attendance || !$attendance->clock_in) {
            $attendanceStatus = 'before_clock_in'; // 出勤前（勤務外）
        } elseif ($attendance && !$attendance->clock_out) {

            // 休憩中かどうか
            $latestBreak = $attendance->breaks()->latest()->first();

            if ($latestBreak && $latestBreak->break_out === null) {
                $attendanceStatus = 'on_break'; // 休憩中
            } else {
                $attendanceStatus = 'after_clock_in'; // 出勤中
            }

        } else {
            $attendanceStatus = 'after_clock_out'; // 退勤済
        }

        return view('layouts.attendance', [
        'attendance' => $attendance,
        'attendanceStatus' => $attendanceStatus, // これが超重要
    ]);
    }

    /**
     * 出勤
     */
    public function clockIn()
    {
        Attendance::create([
            'user_id' => Auth::id(),
            'clock_in' => Carbon::now(),
            'work_date' => Carbon::today(),
        ]);

        return redirect()->route('attendance.index');
    }

    /**
     * 休憩開始
     */
    public function breakIn()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', Carbon::today())
            ->whereNull('clock_out')
            ->first();

        $attendance->breaks()->create([
            'break_in' => Carbon::now(),
        ]);

        return redirect()->route('attendance.index');
    }

    /**
     * 休憩終了（休憩戻）
     */
    public function breakOut()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', Carbon::today())
            ->whereNull('clock_out')
            ->first();

        $break = $attendance->breaks()
            ->whereNull('break_out')
            ->latest()
            ->first();

        $break->update([
            'break_out' => Carbon::now(),
        ]);

        // 休憩終了後は「出勤中」状態に戻る
        return redirect()->route('attendance.index');
    }

    /**
     * 退勤
     */
    public function clockOut()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', Carbon::today())
            ->whereNull('clock_out')
            ->first();

        $attendance->update([
            'clock_out' => Carbon::now(),
        ]);

        return redirect()->route('attendance.index');
    }
}
