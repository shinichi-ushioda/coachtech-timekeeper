<?php

namespace App\Http\Controllers;

use App\Services\AttendanceReportService;
use Illuminate\Contracts\View\View;

class AttendanceReportController extends Controller
{
    /**
     * マイ勤怠レポート画面（PG14 / FN052）
     *
     * @param  AttendanceReportService  $service
     * @return View
     */
    public function index(AttendanceReportService $service): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $report = $service->build($user);

        return view('layouts.attendance_report', $report);
    }
}
