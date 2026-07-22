<?php

namespace App\Http\Controllers;

class AdminAuthController extends Controller
{
    /**
     * 管理者ログイン画面の表示
     */
    public function create()
    {
        return view('auth.admin_login');
    }
}
