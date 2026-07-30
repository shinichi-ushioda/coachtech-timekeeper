<header class="header">
    <div class="header__logo">
        <img src="{{ asset('img/logo.png') }}" alt="ロゴ">
    </div>

    {{-- register / login / admin.login / verification.notice の画面では非表示 --}}
    @if( !in_array(Route::currentRouteName(), ['register', 'login', 'admin.login', 'verification.notice']) )

        @if(Auth::check())
            <nav class="header__nav">
                <ul>

                    {{-- ★ 管理者ユーザー --}}
                    @if(Auth::user()->admin_status)

                        <li><a href="/admin/attendance/list">勤怠一覧</a></li>
                        <li><a href="/admin/staff/list">スタッフ一覧</a></li>
                        <li><a href="/stamp_correction_request/list">申請一覧</a></li>

                        <li>
                            <form action="/logout" method="POST">
                                @csrf
                                <button class="header__logout">ログアウト</button>
                            </form>
                        </li>

                    {{-- ★ 一般ユーザー：退勤後 --}}
                    @elseif(isset($attendanceStatus) && $attendanceStatus === 'after_clock_out')

                        <li><a href="/attendance/list">今月の出勤一覧</a></li>
                        <li><a href="/stamp_correction_request/list">申請一覧</a></li>

                        <li>
                            <form action="/logout" method="POST">
                                @csrf
                                <button class="header__logout">ログアウト</button>
                            </form>
                        </li>

                    {{-- ★ 一般ユーザー：通常時 --}}
                    @else

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
