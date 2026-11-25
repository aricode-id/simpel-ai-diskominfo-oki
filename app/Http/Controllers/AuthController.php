<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return in_array(Auth::user()->role, ['super_admin', 'admin_bidang', 'admin']) 
                ? redirect('/admin/dashboard') 
                : redirect('/dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'nip' => ['required', 'regex:/^[A-Za-z0-9\-]{4,25}$/'],
            'password' => ['required'],
        ], [
            'nip.required' => 'NIP / ID Jabatan wajib diisi.',
            'nip.regex' => 'NIP / ID Jabatan harus berupa 4 sampai 25 karakter alfanumerik (huruf, angka, atau tanda hubung -).',
            'password.required' => 'Password wajib diisi.',
        ]);

        $loginInput = trim($request->input('nip'));

        // Cari user berdasarkan NIP atau Email
        $user = User::where('nip', $loginInput)
                    ->orWhere('email', $loginInput)
                    ->first();

        // Cek kecocokan password: hash password atau fallback plaintext NIP awal
        $passwordValid = false;
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $passwordValid = true;
            } elseif ($user->nip && $request->password === $user->nip) {
                // Fallback jika password plaintext sama dengan NIP, sekalian update ke hash
                $passwordValid = true;
                $user->password = Hash::make($user->nip);
                $user->save();
            }
        }

        if ($user && $passwordValid) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            if (in_array($user->role, ['super_admin', 'admin_bidang', 'admin'])) {
                return redirect()->intended('/admin/dashboard');
            }
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors(['nip' => 'NIP / ID Jabatan atau password salah.'])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
