<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\WorkRecordController;
use App\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\AdminAuthController;
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
    // 勤怠一覧画面（管理者）などをここに追加していく
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

});