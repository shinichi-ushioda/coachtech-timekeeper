<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\WorkRecordController;
use App\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Requests\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Fortify;

// 認証誘導画面は auth のみ（verified を付けない）
Route::middleware(['auth'])->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify_email');})->name('verification.notice');
});

// 管理者ログイン画面の表示（画面表示のみ自前）
Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
// 認証処理は Fortify のコントローラーに任せる
Route::post('/admin/login', [AuthenticatedSessionController::class, 'store']);

Route::middleware(['auth', 'admin'])->group(function () {
    // 勤怠一覧画面（PG08）
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.list');
    
    // スタッフ一覧画面（PG10）
    Route::get('/admin/staff/list', [AdminAttendanceController::class, 'staffList'])->name('admin.staff.list');

    // スタッフ別勤怠一覧画面（PG11）
    Route::get('/admin/attendance/staff/{id}', [AdminAttendanceController::class, 'staff'])->name('admin.attendance.staff');

    // 勤怠が無い日用の中継ルート（管理者・PG09へ）
    Route::get('/admin/attendance/date/{user}/{date}', [AdminAttendanceController::class, 'showByDate'])
        ->name('admin.attendance.showByDate');

    // スタッフ別勤怠のCSV出力（PG11 / FN045） 応用要件
    Route::get('/admin/attendance/staff/{id}/csv', [AdminAttendanceController::class, 'exportCsv'])
        ->name('admin.attendance.staff.csv');

    // 勤怠詳細画面（管理者・PG09）
    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');

    // 勤怠詳細の更新（管理者による直接修正・PG09）
    Route::patch('/admin/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');

    // 申請詳細画面（管理者用）
    Route::get('/admin/stamp_correction_request/{attendance_correct_request}',[AdminAttendanceController::class, 'adminCorrectionRequestShow'])->name('adminstamp_correction_request.show');

    // 申請承認処理（管理者用）
    Route::post('/admin/stamp_correction_request/{attendance_correct_request}/approve',[AdminAttendanceController::class, 'adminCorrectionRequestApprove'])->name('admin.stamp_correction_request.approve');

    // 修正申請承認画面（管理者・PG13）表示
    Route::get(
        '/stamp_correction_request/approve/{attendance_correct_request}',[AdminAttendanceController::class, 'adminCorrectionRequestShow'])->name('admin.requests.show');

    // 修正申請の承認処理（PG13のボタン送信先）
    Route::post(
        '/stamp_correction_request/approve/{attendance_correct_request}',[AdminAttendanceController::class, 'adminCorrectionRequestApprove'])->name('admin.requests.approve');
});

// メール認証済みでないと入れない画面群
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

    //勤怠登録画面（出勤）
    Route::post('/attendance/clock_in', [AttendanceController::class, 'clockIn'])
    ->name('attendance.clock_in');

    //勤怠登録画面（退勤）
    Route::post('/attendance/clock_out', [AttendanceController::class, 'clockOut'])
    ->name('attendance.clock_out');

    //勤怠登録画面（休憩開始）
    Route::post('/attendance/break_in', [AttendanceController::class, 'breakIn'])
    ->name('attendance.break_in');

    //勤怠登録画面（休憩終了）
    Route::post('/attendance/break_out', [AttendanceController::class, 'breakOut'])
    ->name('attendance.break_out');

    //勤怠一覧画面
    Route::get('/attendance/list', [WorkRecordController::class, 'list'])->name('attendance.list');

    //勤怠詳細画面（PG05）
    Route::get('/attendance/detail/{id}', [WorkRecordController::class, 'detail'])->name('attendance.detail');

    //勤怠詳細画面（勤務していない日用のURL）
    Route::get('/attendance/detail/date/{date}', [WorkRecordController::class, 'detailByDate'])->name('attendance.detail.date');

    //申請一覧画面
    Route::get('/stamp_correction_request/list', [WorkRecordController::class, 'correctionRequestList'])->name('stamp_correction_request.list');
    
    // 修正申請の保存（勤怠詳細画面からの POST）
    Route::post('/stamp_correction_request/store',[WorkRecordController::class, 'correctionRequestStore'])->name('stamp_correction_request.store');

    // マイ勤怠レポート画面（PG12）
    Route::get('/attendance/report', [AttendanceReportController::class, 'index'])->name('attendance.report');
});