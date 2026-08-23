<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

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

    /**
     * date（work_date を Y-m-d で返す）
     */
    public function getDateAttribute(): string
    {
        return Carbon::parse($this->work_date)->format('Y-m-d');
    }

    /**
     * total_break_time（休憩合計を H:i で返す）
     */
    public function getTotalBreakTimeAttribute(): string
    {
        $seconds = 0;
        foreach ($this->breaks as $break) {
            if ($break->break_in && $break->break_out) {
                $seconds += abs(Carbon::parse($break->break_in)
                    ->diffInSeconds(Carbon::parse($break->break_out)));
            }
        }
        return sprintf('%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
    }

    /**
     * total_time（労働時間 = 退勤 - 出勤 - 休憩合計 を H:i で返す）
     */
    public function getTotalTimeAttribute(): string
    {
        if (! $this->clock_in || ! $this->clock_out) {
            return '00:00';
        }

        $worked = abs(Carbon::parse($this->clock_in)
            ->diffInSeconds(Carbon::parse($this->clock_out)));

        $breakSeconds = 0;
        foreach ($this->breaks as $break) {
            if ($break->break_in && $break->break_out) {
                $breakSeconds += abs(Carbon::parse($break->break_in)
                    ->diffInSeconds(Carbon::parse($break->break_out)));
            }
        }

        $net = max(0, $worked - $breakSeconds);
        return sprintf('%02d:%02d', intdiv($net, 3600), intdiv($net % 3600, 60));
    }
}
