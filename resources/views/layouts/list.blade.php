@extends('layouts.default')

@section('content')
<div class="attendance-list-wrapper">

    {{-- タイトル（グレー背景） --}}
    <h1 class="page-title">
         勤怠一覧
    </h1>

    {{-- 月選択（白背景） --}}
    <div class="month-selector">
        <a href="{{ route('attendance.list', ['month' => $prevMonth]) }}" class="month-btn">
            ← 前月
        </a>

        <div class="month-display">
            <i class="bi bi-calendar3"></i>
            <span>{{ \Carbon\Carbon::createFromFormat('Y-m', $currentMonth)->format('Y/m') }}</span>
        </div>

        <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="month-btn">
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
            @foreach($days as $day)
                @php
                    $attendance = $day['attendance'];
                    $date = $day['date'];
                    $weekMap = ['日','月','火','水','木','金','土'];
                    $weekday = $weekMap[$date->format('w')];
                @endphp

                <tr>
                    {{-- 日付表示 --}}
                    <td>{{ $date->format('m/d') }}({{ $weekday }})</td>

                    {{-- 出勤時刻 --}}
                    <td>{{ $attendance?->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</td>

                    {{-- 退勤時刻 --}}
                    <td>{{ $attendance?->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</td>

                    {{-- 休憩時間（コントローラーで計算した値を表示） --}}
                    <td>{{ $day['break_time'] }}</td>

                    {{-- 合計勤務時間（コントローラーで計算した値を表示） --}}
                    <td>{{ $day['work_time'] }}</td>

                    {{-- 詳細ボタン --}}
                    <td>
                         @if($day['attendance'])
                             {{-- 勤務している日は通常のリンク --}}
                             <a href="{{ route('attendance.detail', $day['attendance']->id) }}" class="detail-btn">
                                 詳細
                             </a>
                         @else
                             {{-- 勤務していない日はリンク無効（見た目は同じ） --}}
                             <a href="{{ route('attendance.detail.date', ['date' => $day['date']->format('Y-m-d')]) }}" class="detail-btn">
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
