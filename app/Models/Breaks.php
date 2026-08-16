<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Breaks extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'break_in',
        'break_out',
    ];

    protected $casts = [
        'break_in' => 'datetime',
        'break_out' => 'datetime',
    ];

    /**
     * 勤怠（多 → 1）
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * 修正申請（1 → 1 or 0）
     * ※「休憩の修正申請は1回だけ」という仕様
     */
    public function correction(): HasOne
    {
        return $this->hasOne(AttendanceCorrection::class);
    }
}
