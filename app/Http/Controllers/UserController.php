<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected array $validBidang = [
        'Sekretariat', 'Statistik', 'PKP', 'TIK', 'E-Gov', 'Persandian'
    ];

    public function index(Request $request)
    {
        $currentUser = Auth::user();
        if (!in_array($currentUser->role, ['super_admin', 'admin_bidang', 'admin'])) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin mengelola pengguna.');
        }

        $query = User::query();

        if ($currentUser->role !== 'super_admin') {
            // Admin bidang hanya dapat melihat pegawai di bidangnya
            $query->where('bidang', $currentUser->bidang)
                  ->where('role', 'pegawai');
        } else {
            if ($request->filled('bidang') && $request->bidang !== 'Semua') {
                $query->where('bidang', $request->bidang);
            }
        }

        $users = $query->orderBy('created_at', 'desc')->get();
        $bidangList = $this->validBidang;

        return view('admin.users.index', compact('users', 'bidangList'));
    }

    public function create()
    {
        $currentUser = Auth::user();
        if (!in_array($currentUser->role, ['super_admin', 'admin_bidang', 'admin'])) {
            abort(403, 'Akses ditolak.');
        }

        return redirect()->route('admin.users.index');
    }

    public function store(Request $request)
    {
        $currentUser = Auth::user();
        if (!in_array($currentUser->role, ['super_admin', 'admin_bidang', 'admin'])) {
            abort(403, 'Akses ditolak.');
        }

        // Rules validasi
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'status_pegawai' => ['required', 'string', 'in:ASN,Tenaga Ahli'],
            'nip' => ['required', 'regex:/^[A-Za-z0-9\-]{4,25}$/', 'unique:users,nip'],
            'jabatan' => ['nullable', 'string', 'max:150'],
        ];

        if ($currentUser->role === 'super_admin') {
            $rules['role'] = ['required', 'in:super_admin,admin_bidang,admin,pegawai'];
            $rules['bidang'] = ['nullable', 'string'];
        } else {
            // Admin bidang hanya bisa membuat akun pegawai untuk bidangnya sendiri
            $rules['role'] = ['required', 'in:pegawai'];
        }

        $request->validate($rules, [
            'name.required' => 'Nama lengkap wajib diisi.',
            'status_pegawai.required' => 'Status kepegawaian wajib dipilih.',
            'status_pegawai.in' => 'Status kepegawaian harus ASN atau Tenaga Ahli.',
            'nip.required' => 'NIP / ID Login wajib diisi.',
            'nip.regex' => 'NIP / ID Login harus berupa 4 sampai 25 karakter alfanumerik (huruf, angka, atau tanda hubung -).',
            'nip.unique' => 'NIP / ID Login sudah terdaftar dalam sistem.',
            'role.required' => 'Role/Hak akses wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak diizinkan.',
        ]);

        $bidangUser = ($currentUser->role === 'super_admin') 
            ? ($request->bidang ?: null) 
            : $currentUser->bidang;

        // Email dibuat otomatis jika tidak diisi
        $email = $request->filled('email') 
            ? $request->email 
            : ($request->nip . '@diskominfo.local');

        // Pastikan email unik jika terisi
        if (User::where('email', $email)->exists()) {
            $email = $request->nip . '.' . uniqid() . '@diskominfo.local';
        }

        $roleSimpan = ($request->role === 'admin') ? 'admin_bidang' : $request->role;

        User::create([
            'name' => $request->name,
            'status_pegawai' => $request->status_pegawai ?? 'ASN',
            'nip' => $request->nip,
            'jabatan' => $request->jabatan,
            'email' => $email,
            'password' => Hash::make($request->nip), // Password default sama dengan NIP / ID
            'role' => $roleSimpan,
            'bidang' => $bidangUser,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Akun pengguna baru berhasil ditambahkan! Password default disetel sama dengan NIP / ID Jabatan.');
    }

    public function edit($id)
    {
        $currentUser = Auth::user();
        if (!in_array($currentUser->role, ['super_admin', 'admin_bidang', 'admin'])) {
            abort(403, 'Akses ditolak.');
        }

        $user = User::findOrFail($id);

        // Jika bukan super_admin, pastikan user berada di bidang yang sama dan hanya pegawai
        if ($currentUser->role !== 'super_admin') {
            if ($user->bidang !== $currentUser->bidang || in_array($user->role, ['super_admin', 'admin_bidang', 'admin'])) {
                abort(403, 'Anda tidak memiliki hak untuk mengedit data pengguna ini.');
            }
        }

        $bidangList = $this->validBidang;
        return view('admin.users.edit', compact('user', 'bidangList'));
    }

    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();
        if (!in_array($currentUser->role, ['super_admin', 'admin_bidang', 'admin'])) {
            abort(403, 'Akses ditolak.');
        }

        $user = User::findOrFail($id);

        if ($currentUser->role !== 'super_admin') {
            if ($user->bidang !== $currentUser->bidang || in_array($user->role, ['super_admin', 'admin_bidang', 'admin'])) {
                abort(403, 'Anda tidak memiliki hak untuk mengubah data pengguna ini.');
            }
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'status_pegawai' => ['required', 'string', 'in:ASN,Tenaga Ahli'],
            'nip' => ['required', 'regex:/^[A-Za-z0-9\-]{4,25}$/', 'unique:users,nip,' . $user->id],
            'jabatan' => ['nullable', 'string', 'max:150'],
        ];

        if ($currentUser->role === 'super_admin') {
            $rules['role'] = ['required', 'in:super_admin,admin_bidang,admin,pegawai'];
            $rules['bidang'] = ['nullable', 'string'];
        } else {
            $rules['role'] = ['required', 'in:pegawai'];
        }

        if ($request->filled('password')) {
            $rules['password'] = ['string', 'min:4'];
        }

        $request->validate($rules, [
            'name.required' => 'Nama lengkap wajib diisi.',
            'status_pegawai.required' => 'Status kepegawaian wajib dipilih.',
            'status_pegawai.in' => 'Status kepegawaian harus ASN atau Tenaga Ahli.',
            'nip.required' => 'NIP / ID Login wajib diisi.',
            'nip.regex' => 'NIP / ID Login harus berupa 4 sampai 25 karakter alfanumerik (huruf, angka, atau tanda hubung -).',
            'nip.unique' => 'NIP / ID Login sudah terdaftar dalam sistem.',
            'role.required' => 'Role/Hak akses wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak diizinkan.',
            'password.min' => 'Password minimal 4 karakter.',
        ]);

        $user->name = $request->name;
        $user->status_pegawai = $request->status_pegawai ?? 'ASN';
        $user->nip = $request->nip;
        $user->jabatan = $request->jabatan;
        $user->role = ($request->role === 'admin') ? 'admin_bidang' : $request->role;

        if ($request->filled('email')) {
            $user->email = $request->email;
        }

        if ($currentUser->role === 'super_admin') {
            $user->bidang = $request->bidang ?: null;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'Data pegawai berhasil diperbarui!');
    }

    public function resetPasswordToNip($id)
    {
        $currentUser = Auth::user();
        if (!in_array($currentUser->role, ['super_admin', 'admin_bidang', 'admin'])) {
            abort(403, 'Akses ditolak.');
        }

        $user = User::findOrFail($id);

        if ($currentUser->role !== 'super_admin') {
            if ($user->bidang !== $currentUser->bidang || in_array($user->role, ['super_admin', 'admin_bidang', 'admin'])) {
                abort(403, 'Anda tidak memiliki izin mereset password akun ini.');
            }
        }

        if (empty($user->nip)) {
            return back()->with('error', 'Gagal mereset: Pegawai ini belum memiliki NIP / ID Login.');
        }

        $user->password = Hash::make($user->nip);
        $user->save();

        return back()->with('success', 'Password akun ' . $user->name . ' berhasil di-reset menjadi NIP / ID Login (' . $user->nip . ')!');
    }

    public function destroy(User $user)
    {
        $currentUser = Auth::user();
        if (!in_array($currentUser->role, ['super_admin', 'admin_bidang', 'admin'])) {
            abort(403, 'Akses ditolak.');
        }

        $protectedEmails = array_map('trim', explode(',', strtolower(env('SUPER_ADMIN_EMAILS', 'ariirawan022021@gmail.com,ariirawann022021@gmail.com,ari@oki.go.id'))));
        if ($user->id === $currentUser->id || in_array(strtolower($user->email ?? ''), $protectedEmails)) {
            return back()->with('error', 'Akun ini tidak dapat dihapus!');
        }

        if ($currentUser->role !== 'super_admin') {
            if ($user->bidang !== $currentUser->bidang || in_array($user->role, ['super_admin', 'admin_bidang', 'admin'])) {
                abort(403, 'Anda tidak memiliki izin menghapus akun ini.');
            }
        }

        $user->delete();
        return back()->with('success', 'Akun pegawai berhasil dihapus dari sistem.');
    }
}
