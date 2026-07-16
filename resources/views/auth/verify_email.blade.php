@extends('layouts.default')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/verify_email.css')  }}">
@endsection

@section('content')
<div class="verify-container">
    <p class="verify-text">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </p>

    <div class="verify-action">
        <a href="{{ route('verification.notice') }}" class="verify-btn"> 認証はこちらから</a>
    </div>

    <div class="resend-action">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="resend-link">
                認証メールを再送する
            </button>
        </form>
    </div>
</div>
@endsection