<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLoginController extends Controller
{
    public function create()
    {
        return view('admin.admin-login');
    }

    public function store(AdminLoginRequest $request) 
    {
        $credentials = $request->only(
            'email',
            'password'
        );
        //ログイン成功していてかつadmin_status が true なら
    if (
        Auth::attempt($credentials)
        && Auth::user()->admin_status
    ) {
        $request->session()->regenerate();


        return redirect('/admin/attendance/list');
        }

        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ])->onlyInput('email');
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
    
}
