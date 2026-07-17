<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use App\Actions\Fortify\CreateNewUser;
use Laravel\Fortify\Contracts\RegisterResponse;
use App\Actions\Fortify\LogoutResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Features; //Laravel 13では config/fortify.php が無いので、FortifyServiceProvider 内で features を設定する必要あり
use Illuminate\Cache\RateLimiting\Limit; //レートリミッターを定義
use Illuminate\Support\Facades\RateLimiter; //　レートリミッターを定義

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->instance(RegisterResponse::class, new class implements RegisterResponse {
            public function toResponse($request)
            {
                return redirect('/attendance');
            }
        });

        // ログアウト後のリダイレクト
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {   
        // ★ login レートリミッターを定義（必須）
        RateLimiter::for('login', function ($request) {
                return Limit::perMinute(5)->by($request->email.$request->ip());
        });

        //ユーザ作成
        Fortify::createUsersUsing(CreateNewUser::class);

        //登録画面
        Fortify::registerView(function () {
                return view('auth.register');
        });

        //ログイン画面
        Fortify::loginView(function () {
           return view('auth.login');
        });

        //【最重要】ログイン成功時のリダイレクト先を「/attendance」に完全固定する
        $this->app->singleton(
            \Laravel\Fortify\Contracts\LoginResponse::class,
            fn () => new class implements \Laravel\Fortify\Contracts\LoginResponse {
                public function toResponse($request)
                {
                    return redirect('verification.notice');
                }
            }
        );
    }
}
