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

    public function showRegister()
    {
        if (Auth::check()) {
            return in_array(Auth::user()->role, ['super_admin', 'admin_bidang', 'admin']) 
                ? redirect('/admin/dashboard') 
                : redirect('/dashboard');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['required', 'regex:/^[A-Za-z0-9\-]{4,25}$/', 'unique:users,nip'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'nip.required' => 'NIP / ID Jabatan wajib diisi.',
            'nip.regex' => 'NIP / ID Jabatan harus berupa 4 sampai 25 karakter alfanumerik (huruf, angka, atau tanda hubung -).',
            'nip.unique' => 'NIP ini sudah terdaftar.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 4 karakter.'
        ]);

        $email = $request->filled('email') ? $request->email : ($request->nip . '@diskominfo.local');

        $user = User::create([
            'name' => $request->name,
            'nip' => $request->nip,
            'email' => $email,
            'password' => Hash::make($request->password),
            'role' => 'pegawai',
        ]);

        Auth::login($user);

        return redirect('/dashboard');
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

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.exists' => 'Email tidak terdaftar di sistem kami.',
        ]);

        $status = \Illuminate\Support\Facades\Password::sendResetLink(
            $request->only('email')
        );

        return $status == \Illuminate\Support\Facades\Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:4|confirmed',
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 4 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.'
        ]);

        $status = \Illuminate\Support\Facades\Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        return $status == \Illuminate\Support\Facades\Password::PASSWORD_RESET
            ? redirect('/login')->with('status', 'Password berhasil diubah! Silakan login dengan password baru.')
            : back()->withErrors(['email' => __($status)]);
    }
}
