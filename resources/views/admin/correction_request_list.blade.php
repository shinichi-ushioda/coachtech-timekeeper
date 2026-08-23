@extends('layouts.default')

@section('title', '申請一覧')

@section('content')
<div class="correction-wrapper">

    <h2 class="page-title">申請一覧</h2>

    {{-- タブ：承認待ち / 承認済み --}}
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

    {{-- 一覧テーブル --}}
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
                @forelse ($requests as $request)
                <tr>
                    <td>
                        @if($status === 'approved')
                        <span class="status-approved">承認済み</span>
                        @else
                        <span class="status-pending">承認待ち</span>
                        @endif
                    </td>
                    <td>{{ $request->attendance->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->attendance->work_date)->format('Y/m/d') }}</td>
                    <td>{{ $request->reason }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->created_at)->format('Y/m/d') }}</td>
                    <td>
                        <a href="{{ route('admin.requests.show', ['attendance_correct_request' => $request->id]) }}"
                            class="detail-link">
                            詳細
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:30px; color:#888;">
                        申請はありません。
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection