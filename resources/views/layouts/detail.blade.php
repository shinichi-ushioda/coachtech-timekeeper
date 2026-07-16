@extends('layouts.default')

@section('title', '勤怠詳細')

@section('content')
<div class="attendance-detail-container">

    {{-- ページタイトル --}}
    <h2 class="page-title">勤怠詳細</h2>
    
    {{-- 白い背景のカード --}}
    <div class="detail-card">
        <table class="detail-table">

            {{-- 名前 --}}
            <tr>
                <th>名前</th>
                <td>
                    <span class="text-display">{{ Auth::user()->name }}</span>
                </td>
            </tr>

            {{-- 日付 --}}
            <tr>
                <th>日付</th>
                <td>
                    <div class="date-container">
                         <span class="date-year">{{ $attendance->work_date->format('Y年') }}</span>
                         <span class="date-day">{{ $attendance->work_date->format('n月j日') }}</span>
                    </div>
                </td>
            </tr>

            {{-- 出勤・退勤 --}}
<tr>
    <th>出勤・退勤</th>
    <td class="time-range">

        @php
            // 出勤時間の表示ロジック
            $clockIn = $request && $request->requested_clock_in
                ? \Carbon\Carbon::parse($request->requested_clock_in)->format('H:i')
                : ($attendance->clock_in ? $attendance->clock_in->format('H:i') : '');

            // 退勤時間の表示ロジック
            $clockOut = $request && $request->requested_clock_out
                ? \Carbon\Carbon::parse($request->requested_clock_out)->format('H:i')
                : ($attendance->clock_out ? $attendance->clock_out->format('H:i') : '');
        @endphp

        @if(!$request || $request->status !== 'pending')
            <input type="time" name="requested_clock_in" form="correctionForm"
                   class="input-time" value="{{ $clockIn }}">
            <span class="wave">～</span>
            <input type="time" name="requested_clock_out" form="correctionForm"
                   class="input-time" value="{{ $clockOut }}">
        @else
            <span class="text-display">
                {{ $clockIn }} ～ {{ $clockOut }}
            </span>
        @endif

    </td>
</tr>


            {{-- 休憩一覧 --}}
            @foreach($breaks as $index => $break)
                <tr>
                    <th>休憩{{ $index + 1 }}</th>
                    <td class="time-range">
                        @if(!$request || $request->status !== 'pending')
                            <input type="time" name="requested_breaks[{{ $index }}][in]" form="correctionForm"
                                   class="input-time"
                                   value="{{ $break->break_in ? $break->break_in->format('H:i') : '' }}">
                            <span class="wave">～</span>
                            <input type="time" name="requested_breaks[{{ $index }}][out]" form="correctionForm"
                                   class="input-time"
                                   value="{{ $break->break_out ? $break->break_out->format('H:i') : '' }}">
                        @else
                            <span class="text-display">
                                {{ optional($break->break_in)->format('H:i') }} ～ 
                                {{ optional($break->break_out)->format('H:i') }}
                            </span>
                        @endif
                    </td>
                </tr>
            @endforeach

            {{-- 休憩の追加枠 --}}
            <tr>
                <th>休憩{{ count($breaks) + 1 }}</th>
                <td class="time-range">
                    @if(!$request || $request->status !== 'pending')
                        <input type="time" name="requested_breaks_new[in]" form="correctionForm" class="input-time">
                        <span class="wave">～</span>
                        <input type="time" name="requested_breaks_new[out]" form="correctionForm" class="input-time">
                    @else
                        <span class="text-display">—</span>
                    @endif
                </td>
            </tr>

            {{-- 備考 --}}
            <tr>
                <th>備考</th>
                <td>
                    @if(!$request || $request->status !== 'pending')
                        <textarea name="reason" form="correctionForm"
                                  class="input-comment" rows="3">{{ $attendance->comment }}</textarea>
                    @else
                        <span class="text-display">{{ $request->reason }}</span>
                    @endif
                </td>
            </tr>

        </table>
    </div>

    {{-- 修正ボタン（承認待ちでない場合のみ） --}}
    @if(!$request || $request->status !== 'pending')
        <div class="detail-actions">
            <form id="correctionForm" action="{{ route('stamp_correction_request.store') }}" method="POST">
                @csrf
                <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                <button class="btn-edit">修正</button>
            </form>
        </div>
    @endif

    {{-- 承認待ちの場合のメッセージ --}}
    @if($request && $request->status === 'pending')
        <p class="pending-message">※承認待ちのため修正はできません。</p>
    @endif

</div>
@endsection