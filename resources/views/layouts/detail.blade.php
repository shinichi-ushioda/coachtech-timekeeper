@extends('layouts.default')

@section('title', '勤怠詳細')

@section('content')
<div class="attendance-detail-container">

    {{-- ページタイトル --}}
    <h1 class="page-title">勤怠詳細</h1>

    {{-- フラッシュメッセージ --}}
    @if(session('message'))
        <p class="flash-message">{{ session('message') }}</p>
    @endif

    @if(session('error'))
        <p class="flash-error">{{ session('error') }}</p>
    @endif

    {{-- 白い背景のカード --}}
    <div class="detail-card">
        <table class="detail-table">
            <tbody>

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
                @php
                    // 承認待ちの申請があればその値を、なければ元の勤怠データを表示する
                    $clockIn = $request && $request->requested_clock_in
                        ? \Carbon\Carbon::parse($request->requested_clock_in)->format('H:i')
                        : ($attendance->clock_in ? $attendance->clock_in->format('H:i') : '');

                    $clockOut = $request && $request->requested_clock_out
                        ? \Carbon\Carbon::parse($request->requested_clock_out)->format('H:i')
                        : ($attendance->clock_out ? $attendance->clock_out->format('H:i') : '');

                    // 承認待ちのときは編集不可
                    $isPending = $request && $request->status === 'pending';
                @endphp

                <tr>
                    <th>出勤・退勤</th>
                    <td class="time-range">
                        @if(!$isPending)
                            <input type="time" name="requested_clock_in" form="correctionForm"
                                   class="input-time" value="{{ old('requested_clock_in', $clockIn) }}">
                            <span class="wave">～</span>
                            <input type="time" name="requested_clock_out" form="correctionForm"
                                   class="input-time" value="{{ old('requested_clock_out', $clockOut) }}">

                            @error('requested_clock_in')
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                            @error('requested_clock_out')
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                        @else
                            <span class="text-display">{{ $clockIn }} ～ {{ $clockOut }}</span>
                        @endif
                    </td>
                </tr>

                {{-- 休憩一覧 --}}
                @if(!$isPending)
                    {{-- 編集モード：元の休憩レコードを入力欄で表示 --}}
                    @foreach($breaks as $index => $break)
                        <tr>
                            <th>休憩{{ $index + 1 }}</th>
                            <td class="time-range">
                                <input type="time" name="requested_breaks[{{ $index }}][in]" form="correctionForm"
                                       class="input-time"
                                       value="{{ old('requested_breaks.'.$index.'.in', $break->break_in ? $break->break_in->format('H:i') : '') }}">
                                <span class="wave">～</span>
                                <input type="time" name="requested_breaks[{{ $index }}][out]" form="correctionForm"
                                       class="input-time"
                                       value="{{ old('requested_breaks.'.$index.'.out', $break->break_out ? $break->break_out->format('H:i') : '') }}">

                                @error('requested_breaks.'.$index.'.in')
                                    <p class="error-message">{{ $message }}</p>
                                @enderror
                                @error('requested_breaks.'.$index.'.out')
                                    <p class="error-message">{{ $message }}</p>
                                @enderror
                            </td>
                        </tr>
                    @endforeach

                    {{-- 休憩の追加枠 --}}
                    <tr>
                        <th>休憩{{ $breaks->count() + 1 }}</th>
                        <td class="time-range">
                            <input type="time" name="requested_breaks_new[in]" form="correctionForm"
                                   class="input-time" value="{{ old('requested_breaks_new.in') }}">
                            <span class="wave">～</span>
                            <input type="time" name="requested_breaks_new[out]" form="correctionForm"
                                   class="input-time" value="{{ old('requested_breaks_new.out') }}">

                            @error('requested_breaks_new.in')
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                            @error('requested_breaks_new.out')
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>
                @else
                    {{-- 承認待ちモード：申請された休憩内容を表示 --}}
                    @forelse($request->requested_breaks ?? [] as $index => $break)
                        <tr>
                            <th>休憩{{ $index + 1 }}</th>
                            <td class="time-range">
                                <span class="text-display">
                                    {{ $break['break_in'] ?? '' }} ～ {{ $break['break_out'] ?? '' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <th>休憩1</th>
                            <td class="time-range">
                                <span class="text-display"></span>
                            </td>
                        </tr>
                    @endforelse
                @endif

                {{-- 備考 --}}
                <tr>
                    <th>備考</th>
                    <td>
                        @if(!$isPending)
                            <textarea name="reason" form="correctionForm"
                                      class="input-comment" rows="3">{{ old('reason', $attendance->comment) }}</textarea>

                            @error('reason')
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                        @else
                            <span class="text-display">{{ $request->reason }}</span>
                        @endif
                    </td>
                </tr>

            </tbody>
        </table>
    </div>

    {{-- 修正ボタン（承認待ちでない場合のみ） --}}
    @if(!$isPending)
        <div class="detail-actions">
            <form id="correctionForm" action="{{ route('stamp_correction_request.store') }}" method="POST">
                @csrf
                <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                <button type="submit" class="btn-edit">修正</button>
            </form>
        </div>
    @endif

    {{-- 承認待ちの場合のメッセージ --}}
    @if($isPending)
        <p class="pending-message">※承認待ちのため修正はできません。</p>
    @endif

</div>
@endsection
