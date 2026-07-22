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
use App\Http\Requests\LoginRequest;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
         // Fortify の LoginRequest を、自作の LoginRequest に差し替える
         $this->app->bind(FortifyLoginRequest::class, LoginRequest::class);

         // 会員登録後のリダイレクト
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
                    // 管理者は管理画面へ、一般ユーザーは打刻画面へ
                     if ($request->user()->admin_status) {
                         return redirect('/admin/attendance/list');
                    }
                
                     return redirect('/attendance');
                }
            }
        );

        // 認証ロジック（一般・管理者で共通、URLで判定を分ける）
        Fortify::authenticateUsing(function (Request $request) {
             $user = User::where('email', $request->email)->first();

             // メールアドレスまたはパスワードが違う
             if (! $user || ! Hash::check($request->password, $user->password)) {
                 return null;
             }

             // 管理者ログイン画面からは、管理者以外を拒否
             if ($request->is('admin/login') && ! $user->admin_status) {
                 return null;
             } 

             // 一般ログイン画面からは、管理者を拒否
             if ($request->is('login') && $user->admin_status) {
                 return null;
            }

            return $user;
        });
    }
}
