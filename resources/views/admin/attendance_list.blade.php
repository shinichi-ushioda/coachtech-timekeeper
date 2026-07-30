@extends('layouts.default')

@section('title', '勤怠一覧')

@section('content')
<div class="attendance-list-wrapper">

    {{-- タイトル --}}
    <h2 class="page-title">
        {{ $date->format('Y年n月j日') }}の勤怠
    </h2>

    {{-- 日付選択 --}}
    <div class="month-selector">
        <a href="{{ route('admin.attendance.list', ['date' => $prevDate]) }}" class="month-btn">
            ← 前日
        </a>

        <div class="month-display">
            <i class="bi bi-calendar3"></i>
            <span>{{ $date->format('Y/m/d') }}</span>
        </div>

        <a href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}" class="month-btn">
            翌日 →
        </a>
    </div>

    {{-- 勤怠一覧テーブル --}}
    <table class="attendance-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>

        <tbody>
            @foreach($users as $user)
            @php
            $attendance = $attendances->get($user->id);
            @endphp

            <tr>
                {{-- 名前 --}}
                <td>{{ $user->name }}</td>

                {{-- 出勤時刻 --}}
                <td>{{ $attendance?->clock_in ? $attendance->clock_in->format('H:i') : '' }}</td>

                {{-- 退勤時刻 --}}
                <td>{{ $attendance?->clock_out ? $attendance->clock_out->format('H:i') : '' }}</td>

                {{-- 休憩時間 --}}
                <td>{{ $attendance ? \App\Models\Attendance::formatMinutes($attendance->totalBreakMinutes()) : '' }}</td>

                {{-- 合計勤務時間 --}}
                <td>{{ $attendance ? \App\Models\Attendance::formatMinutes($attendance->totalWorkMinutes()) : '' }}</td>

                {{-- 詳細ボタン --}}
                <td>
                    @if($attendance)
                         <a href="/admin/attendance/{{ $attendance->id }}" class="detail-btn">
                             詳細
                         </a>
                    @else
                         <a href="{{ route('admin.attendance.showByDate', ['user' => $user->id, 'date' => $date->format('Y-m-d')]) }}" class="detail-btn">
                              詳細
                         </a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
@endsection