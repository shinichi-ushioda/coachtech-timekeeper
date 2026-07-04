<header class="header">
    <div class="header__logo">
        <img src="{{ asset('img/logo.png') }}" alt="ロゴ">
    </div>

    {{-- register / login / verification.notice の画面では非表示 --}}
    @if( !in_array(Route::currentRouteName(), ['register', 'login', 'verification.notice']) )

        {{-- ログインしている場合のみ表示 --}}
        @if(Auth::check())
            <nav class="header__nav">
                <ul>

                    {{-- ★ 退勤後状態なら画像通りの3ボタンに切り替え --}}
                    @if(isset($attendanceStatus) && $attendanceStatus === 'after_clock_out')

                        <li><a href="/attendance/list">今月の出勤一覧</a></li>
                        <li><a href="/stamp_correction_request/list">申請一覧</a></li>

                        <li>
                            <form action="/logout" method="POST">
                                @csrf
                                <button class="header__logout">ログアウト</button>
                            </form>
                        </li>

                    @else
                        {{-- ★ 通常時の4ボタン（あなたの元コードをそのまま使用） --}}
                        <li><a href="/attendance">勤怠</a></li>
                        <li><a href="/attendance/list">勤怠一覧</a></li>
                        <li><a href="/stamp_correction_request/list">申請</a></li>

                        <li>
                            <form action="/logout" method="POST">
                                @csrf
                                <button class="header__logout">ログアウト</button>
                            </form>
                        </li>
                    @endif

                </ul>
            </nav>
        @endif

    @endif
</header>
