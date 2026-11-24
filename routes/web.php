<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;

// ==========================================
// 1. HALAMAN UTAMA (LANDING PAGE)
// ==========================================
Route::get('/', function () {
    return view('welcome');
});

// ==========================================
// 2. GOOGLE SSO (Single Sign-On)
// ==========================================
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// ==========================================
// 3. GUEST ROUTES (Hanya untuk yang belum login)
// ==========================================
Route::middleware(['guest'])->group(function () {
    // Login
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    // Nonaktifkan Registrasi & Reset Password Publik (Dialihkan ke Login)
    Route::get('/register', fn() => redirect()->route('login'))->name('register');
    Route::post('/register', fn() => redirect()->route('login'));
    Route::get('/forgot-password', fn() => redirect()->route('login'))->name('password.request');
    Route::post('/forgot-password', fn() => redirect()->route('login'))->name('password.email');
    Route::get('/reset-password/{token}', fn() => redirect()->route('login'))->name('password.reset');
    Route::post('/reset-password', fn() => redirect()->route('login'))->name('password.update');
});

// ==========================================
// 4. AUTH ROUTES DASAR (Logout & Lengkapi Bidang)
// ==========================================
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/lengkapi-bidang', function () {
        if (Auth::user()->bidang !== null || Auth::user()->role === 'super_admin') {
            return redirect()->route('dashboard');
        }
        return view('pilih-bidang');
    })->name('pilih.bidang');

    Route::post('/lengkapi-bidang', function (Request $request) {
        $request->validate(['bidang' => 'required|string']);
        $user = Auth::user();
        $user->bidang = $request->bidang;
        $user->save();
        return redirect()->route('dashboard');
    })->name('simpan.bidang');
});

// ==========================================
// 5. PROTECTED ROUTES PEGAWAI & LAPORAN UMUM
// ==========================================
Route::middleware(['auth', 'cek.bidang'])->group(function () {
    
    // Dashboard Staf / Pegawai
    Route::get('/dashboard', [ReportController::class, 'dashboardUser'])->name('dashboard');

    // Monitoring Kinerja Pegawai (Khusus Admin Bidang & Super Admin)
    Route::get('/admin/monitoring', [ReportController::class, 'monitoringPegawai'])->name('admin.monitoring');
    Route::get('/kabid/monitoring', fn() => redirect()->route('admin.monitoring'))->name('kabid.monitoring');
    
    // Group Routing untuk Laporan / Report
    Route::prefix('report')->name('report.')->group(function () {
        Route::get('/create', [ReportController::class, 'create'])->name('create');
        Route::post('/generate', [ReportController::class, 'generate'])->name('generate');
        Route::post('/preview', [ReportController::class, 'generate'])->name('preview');
        Route::get('/preview/{id}', [ReportController::class, 'previewContent'])->name('preview.show');
        Route::get('/history', [ReportController::class, 'history'])->name('history');
        Route::get('/edit/{id}', [ReportController::class, 'edit'])->name('edit');
        Route::get('/download/{id}', [ReportController::class, 'downloadById'])->name('downloadById');
        Route::delete('/hapus/{id}', [ReportController::class, 'destroy'])->name('destroy');

        // Persetujuan & Penolakan Laporan (Khusus Admin Bidang & Super Admin)
        Route::post('/{id}/approve', [ReportController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [ReportController::class, 'reject'])->name('reject');
        Route::post('/{id}/submit-kabid', [ReportController::class, 'submitToKabid'])->name('submitKabid');
        Route::post('/{id}/submit-admin', [ReportController::class, 'submitToKabid'])->name('submitAdmin');

        // Menu Rekapan (Laporan Berstatus Approved)
        Route::get('/rekapan', [ReportController::class, 'rekapan'])->name('rekapan');
    });
});

// ==========================================
// 6. ADMIN ROUTES (Dashboard, Kelola User, Arsip)
// ==========================================
Route::middleware(['auth', 'cek.bidang', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard Manajemen
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Group Routing Manajemen Pengguna (Super Admin & Admin Bidang)
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/reset-password', [UserController::class, 'resetPasswordToNip'])->name('resetPassword');
    });

    // Group Routing Laporan Manajemen
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/create', [ReportController::class, 'createAdmin'])->name('create');
        Route::get('/history', [ReportController::class, 'historyAdmin'])->name('history');
        Route::get('/rekapan', [ReportController::class, 'rekapan'])->name('rekapan');
    });
});
