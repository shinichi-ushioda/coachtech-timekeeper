<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RegisteredUserController;
use App\Http\Requests\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Fortify;


Route::middleware(['auth'])->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify_email');})->name('verification.notice');
        

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

    // 出勤
    Route::post('/attendance/clock_in', [AttendanceController::class, 'clockIn'])
    ->name('attendance.clock_in');

    // 退勤
    Route::post('/attendance/clock_out', [AttendanceController::class, 'clockOut'])
    ->name('attendance.clock_out');

    // 休憩開始
    Route::post('/attendance/break_in', [AttendanceController::class, 'breakIn'])
    ->name('attendance.break_in');

    // 休憩終了
    Route::post('/attendance/break_out', [AttendanceController::class, 'breakOut'])
    ->name('attendance.break_out');

});