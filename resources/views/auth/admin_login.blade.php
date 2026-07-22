@extends('layouts.default')

<!-- タイトル -->
@section('title','管理者ログイン')

<!-- css読み込み -->
@section('css')
<link rel="stylesheet" href="{{ asset('/css/authentication.css') }}">
@endsection

<!-- 本体 -->
@section('content')

<form action="/admin/login" method="post" class="authenticate center" novalidate>
    @csrf
    <h1 class="page__title">管理者ログイン</h1>

    <label for="mail" class="entry__name">メールアドレス</label>
    <input name="email" id="mail" type="email" class="input" value="{{ old('email') }}">
    <div class="form__error">
        @error('email')
        {{ $message }}
        @enderror
    </div>

    <label for="password" class="entry__name">パスワード</label>
    <input name="password" id="password" type="password" class="input">
    <div class="form__error">
        @error('password')
        {{ $message }}
        @enderror
    </div>

    <button class="btn btn--big">管理者ログインする</button>
</form>
@endsection