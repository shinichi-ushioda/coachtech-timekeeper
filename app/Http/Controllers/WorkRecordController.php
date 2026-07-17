<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;

class WorkRecordController extends Controller
{
        /**
     * 勤怠一覧画面（PG04）
     */
    public function list(Request $request)
    {
        $userId = Auth::id();

        // 現在の月 or クエリパラメータの月
        $currentMonth = $request->query('month', Carbon::now()->format('Y-m'));

        // Carbon オブジェクトに変換
        $monthObj = Carbon::createFromFormat('Y-m', $currentMonth);

        // 月初〜月末
        $start = $monthObj->copy()->startOfMonth();
        $end   = $monthObj->copy()->endOfMonth();

        // この月の勤怠を取得（休憩データ breaks も一緒に取得し、 work_date を確実に Y-m-d 形式の文字列にしてキーにする）
        $attendances = Attendance::with('breaks') // 休憩時間計算のためにリレーションをロード
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->orderBy('work_date')
            ->get()
            ->keyBy(function($item) {
                // $item->work_date が Carbon オブジェクトでも文字列でも、確実に 'Y-m-d' の文字列にする
                return Carbon::parse($item->work_date)->format('Y-m-d');
            });

        // 月の日付一覧を作成（勤務していない日も表示するため）
        $days = [];
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $date = $cursor->format('Y-m-d');
            
            // マップからデータを取得
            $attendance = $attendances->get($date);

            // 【追加】ここで「休憩合計時間」と「合計勤務時間」を計算してBladeに渡すと楽になります
            $totalBreakMinutes = 0;
            $workTimeStr = '';
            $breakTimeStr = '';

            if ($attendance) {
                // 休憩時間の計算（修正版）
                foreach ($attendance->breaks as $break) {
                     if ($break->break_in && $break->break_out) {
                         // 変数名を「休憩開始」「休憩終了」として明確にする
                         $breakIn = Carbon::parse($break->break_in);
                         $breakOut = Carbon::parse($break->break_out);
        
                         // 休憩開始（基準）から、休憩終了までの差分（分）を足していく
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
                    
                     // 【修正】出勤時刻を基準にして、退勤時刻までの総滞在時間（分）を確実に「数値（int）」として取得
                     $totalStayMinutes = (int)$clockIn->diffInMinutes($clockOut);
                    
                     // 総滞在時間から休憩時間を引く
                     $totalWorkMinutes = $totalStayMinutes - (int)$totalBreakMinutes;
                     if ($totalWorkMinutes < 0) {
                        $totalWorkMinutes = 0;
                     }

                     // H:i 形式の文字列に変換
                     $workTimeStr = sprintf('%02d:%02d', floor($totalWorkMinutes / 60), $totalWorkMinutes % 60);
                }

            }

            $days[] = [
                'date' => $cursor->copy(),
                'attendance' => $attendance,
                'break_time' => $breakTimeStr, // Blade側で {{ $day['break_time'] }} で出せる
                'work_time' => $workTimeStr,   // Blade側で {{ $day['work_time'] }} で出せる
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
    public function detail($id)
    {
         // 勤怠データを取得
         $attendance = Attendance::findOrFail($id);

         // 休憩一覧
         $breaks = $attendance->breaks()->orderBy('break_in')->get();

         // 修正申請（1件のみ）
         $request = $attendance->correction;

         return view('layouts.detail', [
             'attendance' => $attendance,
             'breaks' => $breaks,
             'request' => $request,
         ]);
    }

    // 中継用の処理
    public function detailByDate($date)
    {
         // ログイン中のユーザーの、その日付のデータを検索する
         // もしデータが無ければ、その場でデータベースに新しいレコード（空の勤怠）を自動作成する
         $attendance = Attendance::firstOrCreate([
             'user_id' => auth()->id(),
             'work_date'    => $date,
         ], [
        // データベースの設計に合わせて、未打刻状態の初期値を設定（以下は例です）
             'check_in'  => null, 
             'check_out' => null,
        ]);

         // 自動作成された（または既存の）データの「id」を使って、設計書通りの詳細画面へリダイレクト
         return redirect()->route('attendance.detail', ['id' => $attendance->id]);
    }

    /**
     * 申請一覧画面（PG06）
     */
    public function correctionRequestList(Request $request)
    {
         $userID = Auth::id();

         //タブの状態（pending / approved）
         $status = $request->input('status', 'pending');

         //状態に応じて修正申請を取得
         $correctionRequests = AttendanceCorrection::with('attendance')
         ->where('status', $status) 
         ->whereHas('attendance', function($query) use ($userID){
            $query->where('user_id', $userID);
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
public function correctionRequestStore(Request $request)
{
    // 1. 【超重要】バリデーションを実行し、フォームからのデータを確実にコントローラーに認識させます
    $request->validate([
        'attendance_id' => 'required',
        'reason'        => 'required|string',
    ]);

    // 2. 【仕様の強制】すでに同じ勤怠IDで「承認待ち（pending）」の申請がないかチェック
    $exists = AttendanceCorrection::where('attendance_id', $request->attendance_id)
        ->where('status', 'pending')
        ->exists();

    if ($exists) {
        return back()->with('error', '既に修正申請が提出されているため、再申請はできません。');
    }

    // 3. 送られてきた休憩データを配列にまとめる処理
    $formattedBreaks = [];

    // 既存の休憩データ（変更分）の取り出し
    $requestedBreaks = $request->input('requested_breaks', []);
    if (is_array($requestedBreaks)) {
        foreach ($requestedBreaks as $break) {
            if (isset($break['in']) && isset($break['out']) && $break['in'] !== '' && $break['out'] !== '') {
                $formattedBreaks[] = [
                    'break_in'  => $break['in'],
                    'break_out' => $break['out'],
                ];
            }
        }
    }

    // 新しく追加された休憩枠（requested_breaks_new）の取り出し
    $newBreak = $request->input('requested_breaks_new', []);
    if (isset($newBreak['in']) && isset($newBreak['out']) && $newBreak['in'] !== '' && $newBreak['out'] !== '') {
        $formattedBreaks[] = [
            'break_in'  => $newBreak['in'],
            'break_out' => $newBreak['out'],
        ];
    }

    // 4. データベースへ保存する（create）
    AttendanceCorrection::create([
        'attendance_id'        => $request->attendance_id,
        'requested_clock_in'   => $request->requested_clock_in,
        'requested_clock_out'  => $request->requested_clock_out,
        
        // 配列にデータがあればJSONに変換、なければNULLにして保存
        'requested_breaks' => count($formattedBreaks) > 0 ? $formattedBreaks : null,
        
        'reason'               => $request->reason,
        'status'               => 'pending',
    ]);

    // 5. メッセージ付きで元の画面に戻す
    return back()->with('message', '※承認待ちのため修正はできません。');
}

}