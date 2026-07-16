@extends('layouts.default')

@section('content')
<div class="attendance-container-wrapper">
  <div class="attendance-container">

    {{-- ① 状態ラベル --}}
    <div class="status-label">
        @if($attendanceStatus === 'before_clock_in')
            <span class="label-gray">勤務外</span>
        @elseif($attendanceStatus === 'after_clock_in')
            <span class="label-green">出勤中</span>
        @elseif($attendanceStatus === 'on_break')
            <span class="label-orange">休憩中</span>
        @elseif($attendanceStatus === 'after_clock_out')
            <span class="label-gray">退勤済</span>
        @endif
    </div>
    
    @php
         $weekMap = ['日', '月', '火', '水', '木', '金', '土'];
         $weekday = $weekMap[now()->format('w')];
    @endphp

    {{-- ② 年月日 --}}
    <h2 class="attendance-date">{{ now()->format('Y年n月j日') }}({{ $weekday }})</h2>

    {{-- ③ 時刻表示（条件分岐を削除し、JS用のIDを付与） --}}
    <div class="time-display">
        <p id="realtime-clock">{{ now()->format('H:i') }}</p>
    </div>

    {{-- ④ ボタン群 --}}
    <div class="attendance-actions">

        {{-- 出勤前 --}}
        @if($attendanceStatus === 'before_clock_in')
            <form action="{{ route('attendance.clock_in') }}" method="POST">
                @csrf
                <button class="btn btn-primary">出勤</button>
            </form>
        @endif

        {{-- 出勤後（休憩前） --}}
        @if($attendanceStatus === 'after_clock_in')
            <div class="button-row">
                <form action="{{ route('attendance.clock_out') }}" method="POST">
                    @csrf
                    <button class="btn btn-danger">退勤</button>
                </form>

                <form action="{{ route('attendance.break_in') }}" method="POST">
                    @csrf
                    <button class="btn btn-warning">休憩入</button>
                </form>
            </div>
        @endif

        {{-- 休憩中 --}}
        @if($attendanceStatus === 'on_break')
            <form action="{{ route('attendance.break_out') }}" method="POST">
                @csrf
                <button class="btn btn-success">休憩戻</button>
            </form>
        @endif

        {{-- 退勤後 --}}
        @if($attendanceStatus === 'after_clock_out')
            <p class="finish-message">お疲れ様でした。</p>
        @endif

    </div>
  </div>
</div>

{{-- ⑤ リアルタイムで時計を動かすJavaScriptを追加 --}}
<script>
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        
        const clockElement = document.getElementById('realtime-clock');
        if (clockElement) {
            clockElement.textContent = `${hours}:${minutes}`;
        }
    }

    // 1秒ごとに現在時刻をチェックして更新
    setInterval(updateClock, 1000);
</script>
@endsection
