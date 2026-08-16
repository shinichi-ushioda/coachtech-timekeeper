<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{

    /**
     * 勤怠を更新できるのは、その勤怠の持ち主だけ
     */
    public function update(User $user, Attendance $attendance): bool
    {
        return $user->id === $attendance->user_id;
    }

    /**
     * 勤怠を削除できるのは、その勤怠の持ち主だけ
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->id === $attendance->user_id;
    }
}
