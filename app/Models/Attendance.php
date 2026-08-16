<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 休憩（breaks）とのリレーション
    public function breaks(): HasMany
    {
        return $this->hasMany(Breaks::class, 'attendance_id');
    }

    // 修正申請（attendance_）とのリレーション ※1日の修正申請は1回のみでルール化したのでhasOneとする。
    public function correction(): HasOne
    {
        return $this->hasOne(AttendanceCorrection::class);
    }

    /**
    * 休憩時間の合計（分）
    */
    public function totalBreakMinutes(): int
    {
         return $this->breaks->sum(function ($break) {
             if (! $break->break_in || ! $break->break_out) {
                 return 0;
             }
        return $break->break_in->diffInMinutes($break->break_out);
        });
    }

    /**
    * 実労働時間の合計（分）＝ 退勤 - 出勤 - 休憩
    */
    public function totalWorkMinutes(): int
    {
         if (! $this->clock_in || ! $this->clock_out) {
             return 0;
         }

         return $this->clock_in->diffInMinutes($this->clock_out) - $this->totalBreakMinutes();
    }

    /**
     * 分を "H:MM" 形式に変換（0分のときは空文字）
    */
    public static function formatMinutes(int $minutes): string
    {
         if ($minutes <= 0) {
             return '';
         }

         return floor($minutes / 60) . ':' . str_pad($minutes % 60, 2, '0', STR_PAD_LEFT);
    }
}
