<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->merge(['username' => strtolower(trim((string) $request->input('username')))]);

        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $ok = Auth::attempt(
            $data + ['is_active' => true],   // utilizadores desactivados não entram
            $request->boolean('remember')
        );

        if (! $ok) {
            $user = User::where('username', $data['username'])->first();

            if ($user && ! $user->is_active && Hash::check($data['password'], $user->password)) {
                return back()->withErrors(['username' => 'Conta desactivada. Fala com o gerente.'])
                             ->onlyInput('username');
            }

            return back()->withErrors(['username' => 'Utilizador ou senha incorrectos.'])
                         ->onlyInput('username');
        }

        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
