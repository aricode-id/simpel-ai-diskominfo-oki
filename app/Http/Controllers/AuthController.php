<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

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

    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/drive.file'])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent' 
            ])
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $email = strtolower($googleUser->getEmail());

            $user = User::where('google_id', $googleUser->getId())
                        ->orWhere('email', $email)
                        ->first();

            // Email khusus Super Admin Utama (Dapat dikonfigurasi via .env)
            $superAdminEmailsConfig = env('SUPER_ADMIN_EMAILS', 'ariirawan022021@gmail.com,ariirawann022021@gmail.com,ari@oki.go.id');
            $superAdminEmails = array_map('trim', explode(',', strtolower($superAdminEmailsConfig)));
            
            $tokenData = json_encode([
                'access_token' => $googleUser->token,
                'expires_in'   => $googleUser->expiresIn,
                'created'      => time(),
            ]);

            if ($user) {
                $updateData = [
                    'google_id' => $googleUser->getId(),
                    'google_token' => $tokenData,
                    'google_refresh_token' => $googleUser->refreshToken ?? $user->google_refresh_token,
                ];

                if (in_array($email, $superAdminEmails)) {
                    $updateData['role'] = 'super_admin';
                }

                $user->update($updateData);
            } else {
                $roleDefault = in_array($email, $superAdminEmails) ? 'super_admin' : 'pegawai';
                
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $email,
                    'google_id' => $googleUser->getId(),
                    'password' => Hash::make(Str::random(16)),
                    'role' => $roleDefault,
                    'google_token' => $tokenData,
                    'google_refresh_token' => $googleUser->refreshToken,
                ]);
            }

            Auth::login($user);

            if (in_array($user->role, ['super_admin', 'admin_bidang', 'admin'])) {
                return redirect('/admin/dashboard')->with('success', 'Login Google berhasil!');
            }

            return redirect('/dashboard')->with('success', 'Login Google berhasil!');

        } 
        catch (\Exception $e) {
            return redirect('/login')->withErrors(['nip' => 'Gagal login menggunakan Google. Pesan Error: ' . $e->getMessage()]);
        }
    }
}
