<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserLoginController extends Controller
{
    //
    public function showLogin()
    {
        return view('auth.external-login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        // dd($credentials);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // $user = Auth::user();

            // dd($user);

            return redirect('/AssignedContact');
        }

        return back()->withErrors([
            'error' => 'Invalid Credentials'

        ]);
    }


    public function login_via_oms(Request $request)
    {

        $user = User::find($request->user_id);

        // dd($user);

        if (!$user) {
            abort(403);
        }

        Auth::login($user);

        $request->session()->regenerate();


        return redirect()->route('AsssignedContact');
    }


    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->to('https://www.oms.multi-linegroupofcompanies.com/dashboard');
    }
}
