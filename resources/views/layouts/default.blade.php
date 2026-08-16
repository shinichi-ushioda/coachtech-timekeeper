<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title')</title>

    {{-- 外部ライブラリ --}}
    <script src="https://kit.fontawesome.com/42694f25bf.js" crossorigin="anonymous"></script>
    <script src="https://ajaxzip3.github.io/ajaxzip3.js" charset="UTF-8"></script>

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    {{-- 共通CSS --}}
    <link rel="stylesheet" href="{{ asset('/css/reset.css') }}">
    <link rel="stylesheet" href="{{ asset('/css/common.css') }}">

    {{-- toastr --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css">

    {{-- 勤怠登録画面専用CSS（PG03） --}}
    <link rel="stylesheet" href="{{ asset('/css/attendance.css') }}">

    {{-- 勤怠一覧画面専用CSS --}}
    <link rel="stylesheet" href="{{ asset('/css/list.css') }}">

    {{-- 勤怠詳細画面専用CSS --}}
    <link rel="stylesheet" href="{{ asset('/css/detail.css') }}">

    {{-- 申請一覧画面専用CSS --}}
    <link rel="stylesheet" href="{{ asset('/css/correction_request_list.css') }}">

    {{-- マイ勤怠レポート画面専用CSS --}}
    <link rel="stylesheet" href="{{ asset('/css/attendance_report.css') }}">
    @yield('css')
</head>

<body>

    {{-- ★ components/header.blade.php を読み込む --}}
    @include('components.header')

    @yield('content')

    {{-- 共通JS --}}
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-bottom-right",
        }

        @if(Session::has('flashSuccess'))
             toastr.success("{{ session('flashSuccess') }}");
        @endif
    </script>
</body>

</html>