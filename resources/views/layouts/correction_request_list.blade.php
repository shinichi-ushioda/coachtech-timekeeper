@extends('layouts.default')

@section('content')
<div class="correction-wrapper">

    {{-- タイトル --}}
    <h2 class="page-title">
        申請一覧
    </h2>

    {{-- ★ 承認待ち / 承認済み タブ --}}
    <div class="request-tabs">
        <a href="{{ route('stamp_correction_request.list', ['status' => 'pending']) }}"
            class="tab-btn {{ $status === 'pending' ? 'active' : '' }}">
            承認待ち
        </a>

        <a href="{{ route('stamp_correction_request.list', ['status' => 'approved']) }}"
            class="tab-btn {{ $status === 'approved' ? 'active' : '' }}">
            承認済み
        </a>
    </div>

    {{-- 一覧表示用のカード --}}
    <div class="correction-card">

        <table class="correction-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>

                @foreach($correctionRequests as $correction)
                <tr>
                    {{-- 状態（承認待ち / 承認済み のみ） --}}
                    <td>
                        @if($correction->status === 'pending')
                        <span class="status-pending">承認待ち</span>
                        @elseif($correction->status === 'approved')
                        <span class="status-approved">承認済み</span>
                        @endif
                    </td>

                    {{-- 名前（ログイン中の一般ユーザー） --}}
                    <td>{{ Auth::user()->name }}</td>

                    {{-- 対象日時 --}}
                    <td>{{ \Carbon\Carbon::parse($correction->attendance->work_date)->format('Y/m/d') }}</td>

                    {{-- 申請理由 --}}
                    <td>{{ $correction->reason }}</td>

                    {{-- 申請日時 --}}
                    <td>{{ $correction->created_at->format('Y/m/d') }}</td>

                    {{-- 詳細画面へのリンク --}}
                    <td>
                        <a href="{{ route('attendance.detail', ['id' => $correction->attendance_id]) }}" class="detail-link">
                            詳細
                        </a>
                    </td>
                </tr>
                @endforeach

                {{-- データが1件もない場合 --}}
                @if($correctionRequests->isEmpty())
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: #888;">
                        申請履歴はありません。
                    </td>
                </tr>
                @endif

            </tbody>
        </table>

    </div>

</div>
@endsection