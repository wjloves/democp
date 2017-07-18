<?php

namespace App\Http\Controllers\Admin\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin/home';

    protected $guard = 'admin';

    protected $loginView = 'admin.auth.login';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest:admin')->except('logout');
    }

    /**
    *  启用用户名登录验证
    */
    public function username()
    {
        return 'username';
    }

    /**
     * 重写登录视图页面
     * @return type
     */
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

     /**
     * 自定义认证驱动
     */
    protected function guard()
    {
        return auth()->guard('admin');
    }


    public function logout(Request $request)
    {
        $this->guard('admin')->logout();

        $request->session()->flush();

        $request->session()->regenerate();

        return redirect('/admin/login');
    }
}
