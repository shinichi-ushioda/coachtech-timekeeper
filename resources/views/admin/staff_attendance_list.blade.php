@extends('layouts.default')

@section('title', 'スタッフ別勤怠一覧')

@section('content')
<div class="attendance-list-wrapper">

    {{-- タイトル --}}
    <h2 class="page-title">
        {{ $user->name }}さんの勤怠
    </h2>

    {{-- フラッシュメッセージ --}}
    @if(session('error'))
        <p class="flash-error">{{ session('error') }}</p>
    @endif

    @if(session('message'))
        <p class="flash-message">{{ session('message') }}</p>
    @endif

    {{-- 月選択 --}}
    <div class="month-selector">
        <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $prevMonth]) }}" class="month-btn">
            ← 前月
        </a>

        <div class="month-display">
            <i class="bi bi-calendar3"></i>
            <span>{{ $month->format('Y/m') }}</span>
        </div>

        <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $nextMonth]) }}" class="month-btn">
            翌月 →
        </a>
    </div>

    {{-- 勤怠一覧テーブル --}}
    <table class="attendance-table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>

        <tbody>
            @php
            $weekMap = ['日','月','火','水','木','金','土'];
            @endphp

            @foreach($days as $day)
            @php
            $attendance = $attendances->get($day->toDateString());
            $weekday = $weekMap[$day->format('w')];
            @endphp

            <tr>
                {{-- 日付 --}}
                <td>{{ $day->format('m/d') }}({{ $weekday }})</td>

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
                    <a href="{{ route('admin.attendance.showByDate', ['user' => $user->id, 'date' => $day->format('Y-m-d')]) }}" class="detail-btn">
                        詳細
                    </a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- CSV出力ボタン --}}
    <div class="csv-action">
        <a href="{{ route('admin.attendance.staff.csv', ['id' => $user->id, 'month' => $month->format('Y-m')]) }}"
            class="csv-btn">
            CSV出力
        </a>
    </div>
</div>
@endsection