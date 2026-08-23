@extends('layouts.default')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/attendance_detail.css') }}">
@endsection

@section('content')
<!-- 勤怠詳細画面（管理者） -->
<div class="detail">
    <h1 class="detail__heading"><span class="detail__bar"></span>勤怠詳細</h1>

    {{-- フラッシュメッセージ --}}
    @if(session('error'))
    <p class="flash-error">{{ session('error') }}</p>
    @endif

    @if(session('flashSuccess'))
    <p class="flash-message">{{ session('flashSuccess') }}</p>
    @endif

    <div class="detail__panel">
        {{-- 直接修正フォーム（管理者） --}}
        <form id="attendanceForm" method="POST"
            action="{{ route('admin.attendance.update', ['id' => $attendance->id]) }}">
            @csrf
            @method('PATCH')

            <table class="detail__table">
                <tr>
                    <th>名前</th>
                    <td>{{ $attendance->user->name }}</td>
                </tr>

                <tr>
                    <th>日付</th>
                    <td>
                        <span class="detail__date-year">{{ $attendance->work_date->format('Y年') }}</span>
                        <span class="detail__date-day">{{ $attendance->work_date->format('n月j日') }}</span>
                    </td>
                </tr>

                {{-- 出勤・退勤 --}}
                <tr>
                    <th>出勤・退勤</th>
                    <td class="time-range">
                        <div class="time-field">
                            <input type="time" name="clock_in" class="input-time"
                                value="{{ old('clock_in', $attendance->clock_in ? $attendance->clock_in->format('H:i') : '') }}">
                            @error('clock_in')
                            <p class="error-message">{{ $message }}</p>
                            @enderror
                        </div>

                        <span class="wave">～</span>

                        <div class="time-field">
                            <input type="time" name="clock_out" class="input-time"
                                value="{{ old('clock_out', $attendance->clock_out ? $attendance->clock_out->format('H:i') : '') }}">
                            @error('clock_out')
                            <p class="error-message">{{ $message }}</p>
                            @enderror
                        </div>
                    </td>
                </tr>

                {{-- 休憩（既存分：配列形式） --}}
                @foreach($breaks as $index => $break)
                <tr>
                    <th>休憩{{ $index + 1 }}</th>
                    <td class="time-range">
                        <div class="time-field">
                            <input type="time" name="breaks[{{ $index }}][in]" class="input-time"
                                value="{{ old('breaks.'.$index.'.in', $break->break_in ? $break->break_in->format('H:i') : '') }}">
                            @error('breaks.'.$index.'.in')
                            <p class="error-message">{{ $message }}</p>
                            @enderror
                        </div>

                        <span class="wave">～</span>

                        <div class="time-field">
                            <input type="time" name="breaks[{{ $index }}][out]" class="input-time"
                                value="{{ old('breaks.'.$index.'.out', $break->break_out ? $break->break_out->format('H:i') : '') }}">
                            @error('breaks.'.$index.'.out')
                            <p class="error-message">{{ $message }}</p>
                            @enderror
                        </div>
                    </td>
                </tr>
                @endforeach

                {{-- 休憩の追加枠（配列形式） --}}
                <tr>
                    <th>休憩{{ $breaks->count() + 1 }}</th>
                    <td class="time-range">
                        <div class="time-field">
                            <input type="time" name="breaks[{{ $breaks->count() }}][in]" class="input-time"
                                value="{{ old('breaks.'.$breaks->count().'.in') }}">
                            @error('breaks.'.$breaks->count().'.in')
                            <p class="error-message">{{ $message }}</p>
                            @enderror
                        </div>

                        <span class="wave">～</span>

                        <div class="time-field">
                            <input type="time" name="breaks[{{ $breaks->count() }}][out]" class="input-time"
                                value="{{ old('breaks.'.$breaks->count().'.out') }}">
                            @error('breaks.'.$breaks->count().'.out')
                            <p class="error-message">{{ $message }}</p>
                            @enderror
                        </div>
                    </td>
                </tr>

                {{-- 備考（管理者は勤怠本体の comment を直接編集） --}}
                <tr>
                    <th>備考</th>
                    <td>
                        <textarea name="comment" class="detail__textarea">{{ old('comment', $attendance->comment) }}</textarea>
                        @error('comment')
                        <p class="error-message">{{ $message }}</p>
                        @enderror
                    </td>
                </tr>
            </table>
        </form>
    </div>

    <div class="detail__action">
        <button type="submit" form="attendanceForm" class="detail__submit-btn">修正</button>
    </div>
</div>
@endsection