<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Support\LegacyPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login'); 
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = Admin::query()
            ->where('username', $request->input('login'))
            ->orWhere('email', $request->input('login'))
            ->first();

        if ($user === null) {
            return back()->withErrors(['login' => 'Wrong username/email or password']);
        }

        if (!LegacyPassword::verify($request->input('password'), $user->password)) {
            return back()->withErrors(['login' => 'Wrong username/email or password']);
        }

        // Authenticate the user for web session
        Auth::login($user, $request->boolean('remember'));

        return redirect()->route('dashboard');
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    }
}