<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'break_id',
        'requested_clock_in',
        'requested_clock_out',
        'requested_break_in',
        'requested_break_out',
        'status',
        'reason',
        'approved_at',
    ];

    protected $casts = [
        'requested_clock_in' => 'datetime',
        'requested_clock_out' => 'datetime',
        'requested_breaks' => 'array',   // ★複数休憩をまとめて扱う
        'approved_at' => 'datetime',
    ];

    /**
     * 勤怠（1 → 1 or 0）
     * 1日の修正申請は1回だけという新仕様に対応
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * 休憩（1 → 1 or 0）
     * 休憩の修正申請も1回だけという新仕様に対応
     */
    public function break()
    {
        return $this->belongsTo(Breaks::class);
    }
}
