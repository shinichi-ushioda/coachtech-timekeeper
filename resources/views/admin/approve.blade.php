@extends('layouts.default')

@section('title', '勤怠詳細（承認）')

@section('content')
<!-- 修正申請承認画面（管理者） -->
<div class="attendance-detail-container">

    <h2 class="page-title">勤怠詳細</h2>

    <div class="detail-card">
        <table class="detail-table">
            <tbody>

                {{-- 名前 --}}
                <tr>
                    <th>名前</th>
                    <td><span class="text-display">{{ $correction->attendance->user->name }}</span></td>
                </tr>

                {{-- 日付 --}}
                <tr>
                    <th>日付</th>
                    <td>
                        <span class="date-year">{{ \Carbon\Carbon::parse($correction->attendance->work_date)->format('Y年') }}</span>
                        <span class="date-day">{{ \Carbon\Carbon::parse($correction->attendance->work_date)->format('n月j日') }}</span>
                    </td>
                </tr>

                {{-- 出勤・退勤（申請された値を表示） --}}
                <tr>
                    <th>出勤・退勤</th>
                    <td class="time-range">
                        <span class="text-display">
                            {{ $correction->requested_clock_in ? \Carbon\Carbon::parse($correction->requested_clock_in)->format('H:i') : '' }}
                            ～
                            {{ $correction->requested_clock_out ? \Carbon\Carbon::parse($correction->requested_clock_out)->format('H:i') : '' }}
                        </span>
                    </td>
                </tr>

                {{-- 休憩（申請された値を表示：時刻だけ） --}}
                @forelse($correction->requested_breaks ?? [] as $index => $break)
                <tr>
                    <th>休憩{{ $index + 1 }}</th>
                    <td class="time-range">
                        <span class="text-display">
                            {{ !empty($break['break_in']) ? \Carbon\Carbon::parse($break['break_in'])->format('H:i') : '' }}
                            ～
                            {{ !empty($break['break_out']) ? \Carbon\Carbon::parse($break['break_out'])->format('H:i') : '' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <th>休憩</th>
                    <td class="time-range"><span class="text-display"></span></td>
                </tr>
                @endforelse

                {{-- 備考（申請理由） --}}
                <tr>
                    <th>備考</th>
                    <td><span class="text-display">{{ $correction->reason }}</span></td>
                </tr>

            </tbody>
        </table>
    </div>

    {{-- 承認ボタン --}}
    <div class="detail-actions">
        @if($isApproved)
        <button class="btn-edit" disabled style="background:#c7c5c5;">承認済み</button>
        @else
        <form action="{{ route('admin.requests.approve', ['attendance_correct_request' => $correction->id]) }}" method="POST">
            @csrf
            <button type="submit" class="btn-edit">承認</button>
        </form>
        @endif
    </div>

</div>
@endsection