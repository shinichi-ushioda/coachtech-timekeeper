<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    // ユーザーとのリレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 休憩（breaks）とのリレーション
    public function breaks()
    {
        return $this->hasMany(Breaks::class);
    }

    // 修正申請（attendance_request）とのリレーション ※1日の修正申請は1回のみでルール化したのでhasOneとする。
    public function attendanceRequest()
    {
        return $this->hasOne(AttendanceRequest::class);
    }
}
