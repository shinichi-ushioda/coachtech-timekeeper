<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'requested_breaks',
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
     * 勤怠（多 → 1）
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * 休憩（多 → 1）
     */
    public function break(): BelongsTo
    {
        return $this->belongsTo(Breaks::class);
    }
}
