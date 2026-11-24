<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Report; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        
        // Kunci ketat hanya pegawai wajib lapor (7 akun jabatan tidak ikut terhitung)
        $userQuery = User::where('role', 'pegawai');
        $reportQuery = Report::query();
        
        if ($user->role !== 'super_admin') {
            $userQuery->where('bidang', $user->bidang);
            $reportQuery->where('bidang', $user->bidang);
        }

        $total_pegawai = (clone $userQuery)->count();
        $total_harian = (clone $reportQuery)->where('jenis_dokumen', 'Laporan Harian')->count();
        $total_mingguan = (clone $reportQuery)->where('jenis_dokumen', 'Laporan Mingguan')->count();
        $total_notulen = (clone $reportQuery)->where('jenis_dokumen', 'Notulensi Rapat')->count();
        $total_lpd = (clone $reportQuery)->where('jenis_dokumen', 'Laporan Perjalanan Dinas')->count();
        $total_pending = (clone $reportQuery)->where('status', 'pending')->count();
        $total_approved = (clone $reportQuery)->where('status', 'approved')->count();

        $sortOption = $request->query('sort', 'created_at_desc');
        $queryAktivitas = Report::with(['user', 'approver']);
        
        if ($user->role !== 'super_admin') {
            $queryAktivitas->where('bidang', $user->bidang);
        }
        
        if ($sortOption === 'updated_at_desc') {
            $queryAktivitas->orderBy('updated_at', 'desc');
        } else {
            $queryAktivitas->orderBy('created_at', 'desc');
        }
        
        $reports_terbaru = $queryAktivitas->take(10)->get();

        return view('admin.dashboard', compact(
            'total_pegawai', 'total_harian', 'total_mingguan', 'total_notulen', 'total_lpd', 
            'total_pending', 'total_approved', 'reports_terbaru', 'sortOption'
        ));
    }

    // Proxy ke UserController untuk backward compatibility
    public function indexUsers(Request $request)
    {
        return app(UserController::class)->index($request);
    }

    public function createUsers()
    {
        return app(UserController::class)->create();
    }

    public function storeUsers(Request $request)
    {
        return app(UserController::class)->store($request);
    }

    public function edit($id)
    {
        return app(UserController::class)->edit($id);
    }

    public function update(Request $request, $id)
    {
        return app(UserController::class)->update($request, $id);
    }

    public function destroyUsers(User $user)
    {
        return app(UserController::class)->destroy($user);
    }
}
