@extends('layouts.default')

@section('title', 'スタッフ一覧')

@section('content')
<div class="attendance-list-wrapper">

    {{-- タイトル --}}
    <h2 class="page-title">
        スタッフ一覧
    </h2>

    {{-- スタッフ一覧テーブル --}}
    <table class="attendance-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
        </thead>

        <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <a href="{{ route('admin.attendance.staff', ['id' => $user->id]) }}" class="detail-btn">
                            詳細
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>
@endsection