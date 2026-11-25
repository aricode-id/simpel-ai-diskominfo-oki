<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Report;
use App\Models\User;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Dashboard Staf (Pegawai)
     */
    public function dashboardUser()
    {
        $user = auth()->user();
        if ($user->role !== 'pegawai') {
            return redirect()->route('admin.dashboard');
        }

        $total_draft = Report::where('user_id', $user->id)->where('status', 'draft')->count();
        $total_pending = Report::where('user_id', $user->id)->where('status', 'pending')->count();
        $total_approved = Report::where('user_id', $user->id)->where('status', 'approved')->count();
        $total_rejected = Report::where('user_id', $user->id)->where('status', 'rejected')->count();

        $reports_terbaru = Report::where('user_id', $user->id)
            ->with('approver')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('user.dashboard', compact(
            'total_draft', 'total_pending', 'total_approved', 'total_rejected', 'reports_terbaru'
        ));
    }

    /**
     * Tampilan Buat Laporan Baru
     */
    public function create() 
    { 
        return view('admin.laporan.create'); 
    }
    
    public function createAdmin() 
    { 
        return view('admin.laporan.create'); 
    }

    /**
     * Riwayat Laporan untuk Pegawai (Akun Sendiri)
     */
    public function history() 
    {
        $user = auth()->user();
        if (in_array($user->role, ['super_admin', 'admin_bidang', 'admin'])) {
            return redirect()->route('admin.laporan.history');
        }

        $reports = Report::where('user_id', $user->id)
            ->with('approver')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('user.report.history', compact('reports'));
    }

    /**
     * Riwayat Laporan untuk Admin Bidang dan Super Admin
     */
    public function historyAdmin(Request $request) 
    {
        $user = auth()->user();
        $query = Report::with(['user', 'approver']);

        if ($user->role === 'super_admin') {
            if ($request->filled('bidang') && $request->bidang !== 'Semua') {
                $query->where('bidang', $request->bidang);
            }
        } else {
            $query->where('bidang', $user->bidang);
        }

        if ($request->filled('status') && $request->status !== 'Semua') {
            $query->where('status', $request->status);
            // Laporan Harian tidak memerlukan antrean verifikasi Admin Bidang
            if ($request->status === 'pending') {
                $query->where('jenis_dokumen', '!=', 'Laporan Harian');
            }
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('jenis_dokumen') && $request->jenis_dokumen !== 'Semua') {
            $query->where('jenis_dokumen', $request->jenis_dokumen);
        }

        $sortOption = $request->query('sort', 'created_at_desc');
        if ($sortOption === 'updated_at_desc') {
            $query->orderBy('updated_at', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $reports = $query->get();
        return view('admin.laporan.history', compact('reports', 'sortOption'));
    }

    /**
     * Monitoring Pegawai untuk Admin Bidang dan Super Admin
     */
    public function monitoringPegawai(Request $request)
    {
        $currentUser = auth()->user();
        if (!in_array($currentUser->role, ['super_admin', 'admin_bidang', 'admin'])) {
            abort(403, 'Akses ditolak. Halaman ini khusus untuk Admin Bidang dan Super Admin.');
        }

        $bidangList = ['Sekretariat', 'Statistik', 'PKP', 'TIK', 'E-Gov', 'Persandian'];
        $selectedBidang = $currentUser->bidang;
        if ($currentUser->role === 'super_admin') {
            $selectedBidang = $request->get('bidang', 'Semua');
        }

        // Filter periode (format YYYY-MM), default ke bulan berjalan saat ini
        $periode = $request->has('periode') ? $request->get('periode') : date('Y-m');

        // KUNCI KETAT: Monitoring HANYA menghitung daftar/total staf pegawai wajib lapor (role = pegawai)
        // 7 akun jabatan (111111 & 2221–2226) tidak ikut terhitung sebagai staf wajib lapor.
        $userQuery = User::where('role', 'pegawai');
        if ($selectedBidang && $selectedBidang !== 'Semua') {
            $userQuery->where('bidang', $selectedBidang);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $userQuery->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('nip', 'like', "%{$s}%")
                  ->orWhere('jabatan', 'like', "%{$s}%");
            });
        }

        $pegawaiList = $userQuery->orderBy('name')->get();

        $monitoringData = [];
        $totalPegawai = $pegawaiList->count();
        $aktifMelapor = 0;
        $belumMelapor = 0;
        $totalLaporanBidang = 0;
        $totalApprovedBidang = 0;
        $totalPendingBidang = 0;
        $totalRejectedBidang = 0;

        foreach ($pegawaiList as $pegawai) {
            $repQuery = Report::where('user_id', $pegawai->id);

            // Filter periode jika ada
            if (!empty($periode) && $periode !== 'all') {
                $repQuery->where(function ($q) use ($periode) {
                    $q->where('tanggal', 'like', $periode . '%')
                      ->orWhere('created_at', 'like', $periode . '%');
                });
            }

            $reportsInPeriod = $repQuery->get();

            $totalLaporan = $reportsInPeriod->count();
            $approvedCount = $reportsInPeriod->where('status', 'approved')->count();
            $pendingCount = $reportsInPeriod->where('status', 'pending')->count();
            $rejectedCount = $reportsInPeriod->where('status', 'rejected')->count();
            $draftCount = $reportsInPeriod->where('status', 'draft')->count();

            // Breakdown jenis dokumen
            $harianCount = $reportsInPeriod->where('jenis_dokumen', 'Laporan Harian')->count();
            $mingguanCount = $reportsInPeriod->where('jenis_dokumen', 'Laporan Mingguan')->count();
            $notulenCount = $reportsInPeriod->where('jenis_dokumen', 'Notulensi Rapat')->count();
            $lhpdCount = $reportsInPeriod->where('jenis_dokumen', 'Laporan Perjalanan Dinas')->count();

            // Laporan terakhir yang dikerjakan pegawai
            $latestReport = Report::where('user_id', $pegawai->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $isActive = $totalLaporan > 0;
            if ($isActive) {
                $aktifMelapor++;
            } else {
                $belumMelapor++;
            }

            $totalLaporanBidang += $totalLaporan;
            $totalApprovedBidang += $approvedCount;
            $totalPendingBidang += $pendingCount;
            $totalRejectedBidang += $rejectedCount;

            $monitoringData[] = [
                'user' => $pegawai,
                'total_laporan' => $totalLaporan,
                'approved_count' => $approvedCount,
                'pending_count' => $pendingCount,
                'rejected_count' => $rejectedCount,
                'draft_count' => $draftCount,
                'harian_count' => $harianCount,
                'mingguan_count' => $mingguanCount,
                'notulen_count' => $notulenCount,
                'lhpd_count' => $lhpdCount,
                'latest_report' => $latestReport,
                'is_active' => $isActive,
            ];
        }

        $summary = [
            'total_pegawai' => $totalPegawai,
            'aktif_melapor' => $aktifMelapor,
            'belum_melapor' => $belumMelapor,
            'persen_aktif' => $totalPegawai > 0 ? round(($aktifMelapor / $totalPegawai) * 100) : 0,
            'total_laporan' => $totalLaporanBidang,
            'total_approved' => $totalApprovedBidang,
            'total_pending' => $totalPendingBidang,
            'total_rejected' => $totalRejectedBidang,
        ];

        return view('admin.monitoring', compact(
            'monitoringData',
            'summary',
            'selectedBidang',
            'bidangList',
            'periode'
        ));
    }

    /**
     * Lanjutkan Edit Dokumen (Draft, Rejected, atau Laporan Harian Milik Sendiri)
     */
    public function edit($id) 
    {
        $draft = Report::findOrFail($id);
        $user = auth()->user();
        $isOwnHarian = ($draft->jenis_dokumen === 'Laporan Harian' && $draft->user_id === $user->id);

        if ($user->role === 'pegawai') {
            if ($draft->user_id !== $user->id) {
                return redirect()->back()->with('error', 'Akses ditolak.');
            }
            if (!$isOwnHarian && !in_array($draft->status, ['draft', 'rejected'])) {
                return redirect()->back()->with('error', 'Dokumen berstatus ' . $draft->status . ' tidak dapat diedit lagi oleh pegawai.');
            }
        } elseif (in_array($user->role, ['admin_bidang', 'admin'])) {
            if ($draft->bidang !== $user->bidang && $user->role !== 'super_admin') {
                return redirect()->back()->with('error', 'Akses ditolak.');
            }
            if ($draft->user_id !== $user->id && $user->role !== 'super_admin') {
                return redirect()->back()->with('error', 'Akses ditolak. Admin Bidang hanya dapat mengedit laporannya sendiri.');
            }
            if (!$isOwnHarian && $draft->status === 'approved') {
                return redirect()->back()->with('error', 'Laporan yang telah disetujui tidak dapat diedit lagi.');
            }
        }

        return view('admin.laporan.create', compact('draft'));
    }

    /**
     * Pratinjau Dokumen untuk Modal (AJAX GET)
     */
    public function previewContent($id)
    {
        $report = Report::with('user', 'approver')->findOrFail($id);
        $user = auth()->user();

        // Validasi hak akses
        if ($user->role === 'pegawai') {
            if ($report->user_id !== $user->id) { 
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403); 
            } 
        } elseif (in_array($user->role, ['admin_bidang', 'admin'])) {
            if ($report->bidang !== $user->bidang && $user->role !== 'super_admin') {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }
        }

        $previewHtml = view('admin.laporan.preview_modal_content', [
            'report' => $report,
            'formData' => $report->form_data ?? [],
        ])->render();

        $canVerify = ($report->jenis_dokumen !== 'Laporan Harian') && 
            ($user->role === 'super_admin' || (in_array($user->role, ['admin_bidang', 'admin']) && $user->bidang === $report->bidang));
        $canEdit = ($report->user_id === $user->id && ($report->status !== 'approved' || $report->jenis_dokumen === 'Laporan Harian')) || 
            in_array($user->role, ['admin_bidang', 'admin', 'super_admin']);

        return response()->json([
            'success' => true,
            'report' => [
                'id' => $report->id,
                'judul' => $report->judul,
                'jenis_dokumen' => $report->jenis_dokumen,
                'status' => $report->status,
                'bidang' => $report->bidang,
                'tanggal' => $report->tanggal,
                'can_verify' => $canVerify,
                'can_edit' => $canEdit,
                'download_url' => route('report.downloadById', $report->id),
                'approve_url' => route('report.approve', $report->id),
                'reject_url' => route('report.reject', $report->id),
                'edit_url' => route('report.edit', $report->id),
            ],
            'html' => $previewHtml,
        ]);
    }

    /**
     * Approve Laporan (Khusus Admin Bidang Terkait & Super Admin)
     */
    public function approve($id)
    {
        $report = Report::findOrFail($id);
        $user = auth()->user();

        if ($user->role !== 'super_admin' && !(in_array($user->role, ['admin_bidang', 'admin']) && $user->bidang === $report->bidang)) {
            return redirect()->back()->with('error', 'Akses ditolak. Hanya Admin Bidang terkait dan Super Admin yang berhak menyetujui laporan ini.');
        }

        $report->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'catatan_revisi' => null,
        ]);

        return redirect()->back()->with('success', 'Laporan berhasil disetujui (Approved)!');
    }

    /**
     * Reject Laporan (Khusus Admin Bidang Terkait & Super Admin)
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'catatan_revisi' => ['required', 'string'],
        ], [
            'catatan_revisi.required' => 'Catatan revisi wajib diisi jika menolak laporan.',
        ]);

        $report = Report::findOrFail($id);
        $user = auth()->user();

        if ($user->role !== 'super_admin' && !(in_array($user->role, ['admin_bidang', 'admin']) && $user->bidang === $report->bidang)) {
            return redirect()->back()->with('error', 'Akses ditolak. Hanya Admin Bidang terkait dan Super Admin yang berhak menolak laporan ini.');
        }

        $report->update([
            'status' => 'rejected',
            'catatan_revisi' => $request->catatan_revisi,
        ]);

        return redirect()->back()->with('success', 'Laporan berhasil ditolak dan catatan revisi telah dikirimkan ke pegawai.');
    }

    /**
     * Menu Rekapan (Laporan Berstatus Approved)
     */
    public function rekapan(Request $request)
    {
        $currentUser = auth()->user();
        if (!in_array($currentUser->role, ['super_admin', 'admin_bidang', 'admin', 'pegawai'])) {
            abort(403, 'Akses ditolak.');
        }

        $query = Report::with(['user', 'approver'])
            ->where('status', 'approved');

        if ($currentUser->role === 'pegawai') {
            $query->where('user_id', $currentUser->id);
        } elseif ($currentUser->role === 'super_admin') {
            if ($request->filled('bidang') && $request->bidang !== 'Semua') {
                $query->where('bidang', $request->bidang);
            }
        } else {
            $query->where('bidang', $currentUser->bidang);
        }

        if ($request->filled('jenis_dokumen') && $request->jenis_dokumen !== 'Semua') {
            $query->where('jenis_dokumen', $request->jenis_dokumen);
        }

        if ($request->filled('periode')) {
            $query->where('tanggal', 'like', $request->periode . '%');
        } elseif ($request->filled('tahun')) {
            $query->whereYear('tanggal', $request->tahun);
            if ($request->filled('bulan') && $request->bulan !== 'Semua') {
                $query->whereMonth('tanggal', $request->bulan);
            }
        }

        $reports = $query->orderBy('tanggal', 'desc')->get();
        $bidangList = ['Sekretariat', 'Statistik', 'PKP', 'TIK', 'E-Gov', 'Persandian'];

        return view('rekapan.index', compact('reports', 'bidangList'));
    }

    /**
     * Helper pencari pejabat definitif Kepala Bidang (role = pegawai)
     * Digunakan agar blok TTD "Mengetahui, Kepala Bidang..." tidak mencetak nama akun admin_bidang.
     */
    public static function findKepalaBidang(?string $bidang): ?User
    {
        if (empty($bidang)) {
            return null;
        }

        return User::where('role', 'pegawai')
            ->where('bidang', $bidang)
            ->where(function ($q) {
                $q->where('jabatan', 'like', '%Kepala Bidang%')
                  ->orWhere('jabatan', 'like', '%Kabid%')
                  ->orWhere('jabatan', 'like', 'Kepala %');
            })
            ->first();
    }

    /**
     * Helper pembersih simbol ketikan mentah (-, /, [, ], *, #)
     * Mengubah daftar poin menjadi narasi paragraf EYD baku yang mengalir rapi.
     */
    public function bersihkanSimbol(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Pisahkan baris / paragraf
        $paragraphs = preg_split('/\n\s*\n/', str_replace(["\r\n", "\r"], "\n", $text));
        $cleanedParagraphs = [];

        foreach ($paragraphs as $para) {
            $lines = explode("\n", trim($para));
            $cleanedSentences = [];

            foreach ($lines as $line) {
                $l = trim($line);
                if ($l === '') continue;

                // Hilangkan pembuka boilerplate AI jika ada
                $lower = strtolower($l);
                if (
                    str_starts_with($lower, 'berdasarkan tugas') ||
                    str_starts_with($lower, 'berikut adalah') ||
                    str_starts_with($lower, 'tuliskan hanya') ||
                    str_starts_with($lower, 'sebagai asisten')
                ) {
                    continue;
                }

                // Hilangkan simbol bullet, dash, pagar, slash, kurung siku di awal baris
                $l = preg_replace('/^[\s\-\*\#\/\•\—\–]+/', '', $l);
                $l = preg_replace('/^\d+[\.\)]\s*/', '', $l);
                $l = preg_replace('/^[a-zA-Z][\.\)]\s*/', '', $l);

                // Hapus total simbol ketikan mentah: [, ], *, #
                $l = str_replace(['[', ']', '**', '*', '__', '#'], '', $l);

                // Ganti tanda minus atau slash yang berdiri sendiri menjadi sambungan kalimat
                $l = preg_replace('/\s+[\/\-]\s+/', ', ', $l);

                $l = trim($l);
                if ($l !== '') {
                    if (!in_array(substr($l, -1), ['.', '!', '?', ';', ':'])) {
                        $l .= '.';
                    }
                    $cleanedSentences[] = $l;
                }
            }

            if (!empty($cleanedSentences)) {
                $paraText = implode(' ', $cleanedSentences);
                // Pastikan tidak ada sisa simbol mentah -, /, [, ], *, # di dalam teks narasi
                $paraText = str_replace(['*', '#', '[', ']'], '', $paraText);
                $paraText = preg_replace('/\s*\/\s*/', ' ', $paraText);
                $paraText = preg_replace('/\s*[\–\—\-]\s*/', ' ', $paraText);
                $paraText = preg_replace('/\s{2,}/', ' ', $paraText);
                $paraText = preg_replace('/\s+([\,\.\;\:])/', '$1', $paraText);
                $paraText = preg_replace('/\.{2,}/', '.', $paraText);
                $cleanedParagraphs[] = trim($paraText);
            }
        }

        $cleanedText = implode("\n\n", $cleanedParagraphs);
        return $this->pertahankanSingkatanKedinasan($cleanedText);
    }

    /**
     * Mempertahankan penulisan huruf kapital penuh untuk singkatan resmi kedinasan
     * (BMD, ATK, SPJ, SPM, SPPD, SPT, IKU, SAKIP, SPBE, ASN, SRIKANDI, OKI, WIB, dll).
     */
    public function pertahankanSingkatanKedinasan(string $text): string
    {
        if (trim($text) === '') {
            return $text;
        }

        $singkatanList = [
            'BMD', 'ATK', 'SPJ', 'SPM', 'SPPD', 'SPT', 'IKU', 'SAKIP', 'SPBE',
            'ASN', 'SRIKANDI', 'OKI', 'WIB', 'WITA', 'WIT',
            'PNS', 'PPPK', 'SOP', 'TIK', 'E-GOV', 'EGOV', 'PKP', 'KTP', 'SIM',
            'APBD', 'APBN', 'OPD', 'DPA', 'RKA', 'SKPD', 'BAPEDA', 'BAPPEDA',
            'BAWASLU', 'KPU', 'DPRD', 'KPK', 'BPK', 'BPKP', 'PANRB', 'KOMINFO',
            'DISKOMINFO', 'PPID', 'CSIRT', 'KMS', 'AI', 'SIMPEL-AI', 'SIMPEL',
            'PDF', 'DOCX', 'HEIC', 'JPEG', 'PNG', 'REST', 'API', 'SSO', 'URL'
        ];

        foreach ($singkatanList as $abbr) {
            $pattern = '/\b' . preg_quote($abbr, '/') . '\b/i';
            $text = preg_replace($pattern, $abbr, $text);
        }

        $text = preg_replace('/\bE-Gov\b/i', 'E-Gov', $text);
        $text = preg_replace('/\bE-Kinerja\b/i', 'e-Kinerja', $text);

        return $text;
    }

    /**
     * Format judul kegiatan formal Title Case, pertahankan singkatan kedinasan,
     * dan hilangkan tanda titik di akhir judul.
     */
    public function formatJudulKegiatan(string $judul): string
    {
        $judul = trim($this->bersihkanSimbol($judul));
        // Hilangkan titik di akhir judul
        $judul = rtrim($judul, " .\t\n\r\0\x0B");

        if ($judul === '') {
            return '';
        }

        // Ubah ke Title Case
        $judul = ucwords(strtolower($judul));

        // Kembalikan singkatan kedinasan menjadi UPPERCASE
        $judul = $this->pertahankanSingkatanKedinasan($judul);

        // Pastikan tidak ada titik lagi di akhir
        return rtrim($judul, " .\t\n\r\0\x0B");
    }

    /**
     * Helper terpusat untuk memproses teks laporan dengan Gemini AI.
     * Mengunci responseMimeType: "application/json" dan selalu mengembalikan 3 key baku:
     * - 'judul': Title Case singkat formal tanpa titik di akhir (maksimal 8 kata)
     * - 'uraian': 1 paragraf narasi dinas pemerintahan EYD baku dengan unsur 5W+1H lengkap
     * - 'output': hasil/capaian/keputusan konkret dalam kalimat formal rapi
     */
    public function prosesTeksDenganAI(
        string $catatanMentah,
        string $judulAwal = '',
        string $outputAwal = '',
        string $jenisDokumen = 'Laporan Harian',
        string $bidang = '',
        ?string $apiKey = null,
        array $konteks = []
    ): array {
        $apiKey = $apiKey ?: env('GEMINI_API_KEY');

        $defaultJudul = !empty(trim($judulAwal)) 
            ? $this->formatJudulKegiatan($judulAwal) 
            : (!empty(trim($catatanMentah)) ? $this->formatJudulKegiatan('Pelaksanaan Tugas ' . ($bidang ?: 'Kedinasan')) : 'Kegiatan Kedinasan');

        $defaultUraian = !empty(trim($catatanMentah)) 
            ? $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($catatanMentah)) 
            : 'Melaksanakan tugas kedinasan sesuai dengan arahan dan target yang telah ditetapkan.';

        $defaultOutput = !empty(trim($outputAwal))
            ? $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($outputAwal)) 
            : 'Terlaksananya kegiatan dengan lancar dan tertib sesuai dengan target kinerja.';

        if (empty($apiKey) || empty(trim($catatanMentah . ' ' . $judulAwal))) {
            return [
                'judul'  => $defaultJudul,
                'uraian' => $defaultUraian,
                'output' => $defaultOutput,
            ];
        }

        $namaPegawai = !empty($konteks['nama_pegawai']) 
            ? $konteks['nama_pegawai'] 
            : (auth()->check() ? auth()->user()->name : 'Pegawai Diskominfo OKI');
        $jabatan = !empty($konteks['jabatan']) 
            ? $konteks['jabatan'] 
            : (auth()->check() ? (auth()->user()->jabatan ?? 'Aparatur Sipil Negara') : 'Aparatur Sipil Negara');
        $hariTanggal = !empty($konteks['hari_tanggal']) ? $konteks['hari_tanggal'] : date('d F Y');
        $waktu = !empty($konteks['waktu']) ? $konteks['waktu'] : '08:00 WIB s.d. Selesai';
        $lokasi = !empty(trim($konteks['lokasi'] ?? '')) ? trim($konteks['lokasi']) : 'Kantor Dinas Komunikasi dan Informatika Kabupaten Ogan Komering Ilir';

        // 1. Tentukan aturan sudut pandang subjek berdasarkan jenis dokumen
        if ($jenisDokumen === 'Laporan Harian') {
            $aturanSubjek = "SUDUT PANDANG SUBJEK: Laporan Harian adalah laporan kinerja individu. Gunakan nama dan jabatan pegawai ({$namaPegawai}, {$jabatan}) sebagai pelaku/subjek utama kegiatan.";
        } elseif ($jenisDokumen === 'Notulensi Rapat') {
            $aturanSubjek = "SUDUT PANDANG SUBJEK: Notulensi Rapat adalah catatan forum dinas resmi. Gunakan subjek forum rapat, pimpinan rapat, dan peserta rapat sebagai subjek pembahasan dan pengambilan keputusan.";
        } elseif ($jenisDokumen === 'Laporan Perjalanan Dinas') {
            $aturanSubjek = "SUDUT PANDANG SUBJEK: Laporan Perjalanan Dinas (LHPD) adalah laporan tugas luar berdasarkan Surat Perintah Tugas (SPT). Gunakan subjek tim/personil aparatur pelaksana yang ditugaskan dalam SPT ({$namaPegawai}) sebagai subjek kegiatan perjalanan dinas.";
        } else {
            $aturanSubjek = "SUDUT PANDANG SUBJEK: Gunakan subjek institusi resmi Pemerintah Kabupaten Ogan Komering Ilir.";
        }

        $systemInstruction = "Anda adalah asisten AI perumus naskah dan laporan kedinasan resmi Pemerintah Kabupaten Ogan Komering Ilir (Diskominfo OKI).\n"
            . "Tugas Anda adalah merumuskan dan mengembangkan catatan kegiatan aparatur pemerintah menjadi naskah dinas formal EYD baku.\n\n"
            . "ATURAN WAJIB:\n"
            . "1. {$aturanSubjek}\n"
            . "2. DILARANG menyalin ulang catatan mentah user secara verbatim. Kembangkan catatan singkat tersebut menjadi 1 paragraf narasi dinas pemerintahan EYD yang mengalir dengan unsur 5W+1H lengkap (Apa kegiatannya, Siapa pelaksananya, Kapan hari/tanggal/jam, Di mana lokasinya, Mengapa/tujuan, dan Bagaimana proses pelaksanaannya).\n"
            . "3. LOKASI DEFAULT: Jika field lokasi pada form kosong atau tidak spesifik, gunakan 'Kantor Dinas Komunikasi dan Informatika Kabupaten Ogan Komering Ilir'.\n"
            . "4. SINGKATAN KEDINASAN: Pertahankan penulisan huruf kapital penuh untuk singkatan kedinasan (seperti BMD, ATK, SPJ, SPM, SPPD, SPT, IKU, SAKIP, SPBE, ASN, SRIKANDI, OKI, WIB) pada judul, uraian, maupun output.\n"
            . "5. HAPUS TITIK PADA JUDUL: DILARANG memberi tanda titik di akhir judul kegiatan. Judul harus berupa Title Case formal ringkas maksimal 8 kata tanpa titik di akhir (gantikan judul generik seperti 'Pelaksanaan Kegiatan 1').\n"
            . "6. STERILISASI SIMBOL: DILARANG menggunakan simbol ketikan mentah atau bullet points seperti '-', '/', '[', ']', '*', '#'. Seluruh catatan HARUS diubah menjadi kalimat-kalimat paragraf yang utuh, padat, dan mengalir rapi.\n"
            . "7. FORMAT KELUARAN WAJIB JSON: Kembalikan HANYA JSON murni (valid JSON) dengan 3 key baku berikut:\n"
            . "   - 'judul': Ringkasan judul kegiatan formal Title Case singkat tanpa tanda titik di akhir (maksimal 8 kata).\n"
            . "   - 'uraian': 1 paragraf narasi dinas pemerintahan EYD yang mengalir dengan unsur 5W+1H lengkap sesuai aturan sudut pandang subjek tanpa simbol -, /, [, ], *, #.\n"
            . "   - 'output': Hasil capaian, output, atau keputusan konkret kegiatan dalam 1 kalimat formal rapi.";

        $userPrompt = "KONTEKS KEGIATAN DARI FORM:\n"
            . "- Jenis Dokumen: {$jenisDokumen}\n"
            . "- Unit Kerja/Bidang: " . ($bidang ?: 'Dinas Komunikasi dan Informatika Kabupaten OKI') . "\n"
            . "- Pelaksana (Nama & Jabatan): {$namaPegawai} ({$jabatan})\n"
            . "- Hari / Tanggal: {$hariTanggal}\n"
            . "- Waktu Pelaksanaan: {$waktu}\n"
            . "- Lokasi: {$lokasi}\n"
            . "- Usulan Judul Awal: " . ($judulAwal ?: '(Belum ada, buatkan judul Title Case formal maksimal 8 kata tanpa titik di akhir)') . "\n"
            . "- Catatan Mentah Kegiatan:\n" . strip_tags($catatanMentah) . "\n"
            . "- Output Awal: " . ($outputAwal ?: '(Rumuskan capaian konkret)');

        $models = [
            env('GEMINI_MODEL', 'gemini-3.6-flash'),
            'gemini-3.5-flash',
            'gemini-flash-lite-latest',
            'gemma-4-31b-it',
        ];
        $models = array_values(array_unique(array_filter($models)));

        foreach ($models as $idx => $model) {
            try {
                $isGemma = str_contains(strtolower($model), 'gemma');
                $postData = [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $systemInstruction . "\n\n" . $userPrompt]
                            ]
                        ]
                    ],
                ];

                // gemma-4-31b-it dan model seri Gemma tidak mendukung parameter responseMimeType: "application/json"
                if (!$isGemma) {
                    $postData['generationConfig'] = [
                        'responseMimeType' => 'application/json',
                    ];
                }

                $response = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->withoutVerifying()
                    ->timeout(30)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", $postData);

                if ($response->successful()) {
                    $rawJson = trim($response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '');
                    if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $rawJson, $matches)) {
                        $rawJson = trim($matches[1]);
                    } else {
                        $rawJson = preg_replace('/^```(?:json)?\s*/i', '', $rawJson);
                        $rawJson = preg_replace('/\s*```$/', '', $rawJson);
                    }
                    $parsed = json_decode(trim($rawJson), true);

                    if (is_array($parsed) && isset($parsed['uraian'])) {
                        $judul = !empty(trim($parsed['judul'] ?? '')) 
                            ? $this->formatJudulKegiatan($parsed['judul'])
                            : $defaultJudul;

                        $uraian = !empty(trim($parsed['uraian'] ?? ''))
                            ? $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($parsed['uraian']))
                            : $defaultUraian;

                        $output = !empty(trim($parsed['output'] ?? ''))
                            ? $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($parsed['output']))
                            : $defaultOutput;

                        return [
                            'judul'  => $judul,
                            'uraian' => $uraian,
                            'output' => $output,
                        ];
                    }
                }

                $status = $response->status();
                Log::warning("Gemini API ({$model}) HTTP {$status}: " . substr($response->body(), 0, 300));

                if (in_array($status, [429, 503])) {
                    sleep(1);
                }
            } catch (\Throwable $e) {
                Log::error("Gemini API Exception on model {$model} in prosesTeksDenganAI: " . $e->getMessage());
                if ($idx < count($models) - 1) {
                    sleep(1);
                }
            }
        }

        return [
            'judul'  => $defaultJudul,
            'uraian' => $defaultUraian,
            'output' => $defaultOutput,
        ];
    }

    /**
     * Memeriksa apakah payload request kosong (misal akibat batas post_max_size terlampaui)
     * atau seluruh input kegiatan kosong.
     */
    private function isPayloadOrKegiatanEmpty(Request $request): bool
    {
        $all = $request->except(['_token']);
        if (empty($all) || count($all) === 0 || empty($request->input('jenis_dokumen'))) {
            return true;
        }

        $jenis = $request->input('jenis_dokumen');

        if ($jenis === 'Laporan Harian') {
            $kegiatans = $request->input('kegiatan', []);
            if (!is_array($kegiatans) || empty($kegiatans)) {
                return true;
            }
            $hasIsi = false;
            foreach ($kegiatans as $keg) {
                $j = trim($keg['judul'] ?? '');
                $h = trim($keg['hasil_kegiatan'] ?? $keg['hasil'] ?? $keg['uraian'] ?? '');
                if ($j !== '' || $h !== '') {
                    $hasIsi = true;
                    break;
                }
            }
            if (!$hasIsi) {
                return true;
            }
        } elseif ($jenis === 'Laporan Mingguan') {
            $mingguan = $request->input('mingguan', []);
            if (!is_array($mingguan) || empty($mingguan)) {
                return true;
            }
            $hasIsi = false;
            foreach ($mingguan as $hari) {
                $kegs = $hari['kegiatan'] ?? [];
                if (is_array($kegs)) {
                    foreach ($kegs as $keg) {
                        $u = trim($keg['uraian'] ?? $keg['judul'] ?? '');
                        $o = trim($keg['output'] ?? $keg['hasil'] ?? '');
                        if ($u !== '' || $o !== '') {
                            $hasIsi = true;
                            break 2;
                        }
                    }
                }
            }
            if (!$hasIsi) {
                return true;
            }
        } elseif ($jenis === 'Notulensi Rapat') {
            $judul = trim($request->input('notulen_judul', ''));
            $hasil = trim($request->input('notulen_hasil', ''));
            $agenda = trim($request->input('notulen_agenda', ''));
            $pembahasan = trim($request->input('notulen_pembahasan', ''));
            $judulUmum = trim($request->input('judul', ''));
            if ($judul === '' && $hasil === '' && $agenda === '' && $pembahasan === '' && $judulUmum === '') {
                return true;
            }
        } elseif ($jenis === 'Laporan Perjalanan Dinas') {
            $maksud = trim($request->input('sppd_maksud', ''));
            $hasil = trim($request->input('sppd_hasil', ''));
            $tujuan = trim($request->input('sppd_tujuan', ''));
            $judulUmum = trim($request->input('judul', ''));
            if ($maksud === '' && $hasil === '' && $tujuan === '' && $judulUmum === '') {
                return true;
            }
        } else {
            if (trim($request->input('judul', '')) === '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate Dokumen PHPWord & Simpan Lokal
     */
    public function generate(Request $request) 
    {
        // Pengecekan wajib di awal fungsi: jika input kegiatan kosong atau request()->all() kosong
        if ($this->isPayloadOrKegiatanEmpty($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Data form kosong atau ukuran foto melebihi batas server',
            ], 422);
        }

        try {
            return $this->doGenerate($request);
        } catch (\Throwable $e) {
            Log::error('ReportController::generate error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'userId' => auth()->id(),
            ]);
            if ($request->ajax() || $request->wantsJson() || $request->input('action') === 'preview') {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memproses dokumen: ' . $e->getMessage(),
                ], 500);
            }
            throw $e;
        }
    }

    private function doGenerate(Request $request) 
    {
        // Pengecekan wajib di awal fungsi: cegah penyimpanan baris draf kosong ke database
        if ($this->isPayloadOrKegiatanEmpty($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Data form kosong atau ukuran foto melebihi batas server',
            ], 422);
        }

        // Inisialisasi awal variabel nama_file & judul_db untuk mencegah bug Undefined variable
        $nama_file = null;
        $judul_db = trim($request->input('judul', '')) !== '' 
            ? trim($request->input('judul')) 
            : (!empty($request->jenis_dokumen) ? ($request->jenis_dokumen . ' Kedinasan') : 'Laporan Kegiatan Kedinasan');

        $action = $request->input('action', 'draft'); 
        $jenis = $request->jenis_dokumen;
        $reportId = $request->input('report_id');
        $is_bypass = $request->boolean('bypass_ai'); 
        $currentUser = auth()->user();

        // JANGAN TIMPA USER ID PEMBUAT ASLI JIKA DIEDIT OLEH ADMIN / SUPER ADMIN
        $authorId = $currentUser->id;
        $oldData = [];

        if ($reportId) {
            $existingReport = Report::findOrFail($reportId);
            $oldData = $existingReport->form_data ?? [];
            $authorId = $existingReport->user_id;
            $isOwnHarian = ($existingReport->jenis_dokumen === 'Laporan Harian' && $existingReport->user_id === $currentUser->id);

            if ($currentUser->role === 'pegawai') {
                if ($existingReport->user_id !== $currentUser->id || (!$isOwnHarian && !in_array($existingReport->status, ['draft', 'rejected']))) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => 'Akses ditolak. Pegawai hanya dapat mengedit laporan berstatus draft atau ditolak.'], 403);
                    }
                    return redirect()->back()->with('error', 'Akses ditolak. Pegawai hanya dapat mengedit laporan berstatus draft atau ditolak.');
                }
            } elseif (in_array($currentUser->role, ['admin_bidang', 'admin'])) {
                if ($existingReport->bidang !== $currentUser->bidang && $currentUser->role !== 'super_admin') {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
                    }
                    return redirect()->back()->with('error', 'Akses ditolak.');
                }
                if ($existingReport->user_id !== $currentUser->id && $currentUser->role !== 'super_admin') {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => 'Akses ditolak. Admin Bidang hanya dapat mengedit laporannya sendiri.'], 403);
                    }
                    return redirect()->back()->with('error', 'Akses ditolak. Admin Bidang hanya dapat mengedit laporannya sendiri.');
                }
                if (!$isOwnHarian && $existingReport->status === 'approved') {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => 'Laporan yang telah disetujui tidak dapat diedit lagi.'], 403);
                    }
                    return redirect()->back()->with('error', 'Laporan yang telah disetujui tidak dapat diedit lagi.');
                }
            }
        }

        $formData = $request->except(['_token', 'action', 'logo_kop']);

        if ($jenis === 'Laporan Harian') {
            $formData['pakai_kop'] = 'tidak';
        }

        if (($formData['pakai_ttd'] ?? 'tidak') === 'tidak') {
            $formData['ttd_jabatan'] = null;
            $formData['ttd_nama'] = null;
            $formData['ttd_nip'] = null;
            $formData['ttd_status_pegawai'] = null;
        }

        // Simpan foto dengan kompresi bawaan PHP GD
        if ($jenis === 'Laporan Mingguan') {
            foreach ($request->input('mingguan', []) as $hari_idx => $hari) {
                if (!isset($hari['kegiatan'])) continue;
                foreach ($hari['kegiatan'] as $keg_idx => $keg) {
                    for ($f = 1; $f <= 4; $f++) {
                        $fileKey = "foto_mingguan_{$hari_idx}_{$keg_idx}_{$f}";
                        if ($request->hasFile($fileKey)) {
                            $savedPhoto = $this->savePermImage($request->file($fileKey));
                            if ($savedPhoto) {
                                $formData['mingguan'][$hari_idx]['kegiatan'][$keg_idx]["foto_{$f}_path"] = $savedPhoto;
                            }
                        } else {
                            if (isset($oldData['mingguan'][$hari_idx]['kegiatan'][$keg_idx]["foto_{$f}_path"])) {
                                $formData['mingguan'][$hari_idx]['kegiatan'][$keg_idx]["foto_{$f}_path"] = $oldData['mingguan'][$hari_idx]['kegiatan'][$keg_idx]["foto_{$f}_path"];
                            }
                        }
                    }
                }
            }
        } elseif ($jenis === 'Laporan Harian') {
            foreach ($request->input('kegiatan', []) as $keg_idx => $keg) {
                for ($f = 1; $f <= 4; $f++) {
                    $fileKey = "foto_{$keg_idx}_{$f}";
                    if ($request->hasFile($fileKey)) {
                        $savedPhoto = $this->savePermImage($request->file($fileKey));
                        if ($savedPhoto) {
                            $formData['kegiatan'][$keg_idx]["foto_{$f}_path"] = $savedPhoto;
                        }
                    } else {
                        if (isset($oldData['kegiatan'][$keg_idx]["foto_{$f}_path"])) {
                            $formData['kegiatan'][$keg_idx]["foto_{$f}_path"] = $oldData['kegiatan'][$keg_idx]["foto_{$f}_path"];
                        }
                    }
                }
            }
        } elseif ($jenis === 'Laporan Perjalanan Dinas') {
            for ($f = 1; $f <= 2; $f++) {
                if ($request->hasFile("sppd_foto_{$f}")) {
                    $savedPhoto = $this->savePermImage($request->file("sppd_foto_{$f}"));
                    if ($savedPhoto) {
                        $formData["sppd_foto_{$f}_path"] = $savedPhoto;
                    }
                } else {
                    if (isset($oldData["sppd_foto_{$f}_path"])) {
                        $formData["sppd_foto_{$f}_path"] = $oldData["sppd_foto_{$f}_path"];
                    }
                }
            }
        } elseif ($jenis === 'Notulensi Rapat') {
            for ($f = 1; $f <= 2; $f++) {
                if ($request->hasFile("notulen_foto_{$f}")) {
                    $savedPhoto = $this->savePermImage($request->file("notulen_foto_{$f}"));
                    if ($savedPhoto) {
                        $formData["notulen_foto_{$f}_path"] = $savedPhoto;
                    }
                } else {
                    if (isset($oldData["notulen_foto_{$f}_path"])) {
                        $formData["notulen_foto_{$f}_path"] = $oldData["notulen_foto_{$f}_path"];
                    }
                }
            }
        }
        
        $api_key = env('GEMINI_API_KEY');
        
        // Mencegah file corrupt karena special character
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);
        
        // Konversi WebP ke PNG agar aman untuk PHPWord
        $logoWebp = public_path('logo_oki.webp');
        $logoPng = public_path('logo_oki.png');
        if (file_exists($logoWebp) && !file_exists($logoPng)) {
            if (function_exists('imagecreatefromwebp')) {
                $img = @imagecreatefromwebp($logoWebp);
                if ($img) {
                    @imagepng($img, $logoPng);
                    @imagedestroy($img);
                }
            }
        }
        $logoPath = (file_exists($logoPng) && @getimagesize($logoPng)) ? $logoPng : null;

        $section = $phpWord->addSection([
            'paperSize' => 'A4', 
            'marginTop' => 1134, 
            'marginBottom' => 1134, 
            'marginLeft' => 1134, 
            'marginRight' => 1134
        ]);

        $bidang_teks = $request->bidang ?? $currentUser->bidang ?? 'Pemerintah Kabupaten OKI';
        $authorUser = User::find($authorId) ?? $currentUser;
        $namaUserLengkap = $authorUser->name ?? 'Pegawai';
        $namaDepan = strtolower(explode(' ', $namaUserLengkap)[0]);

        // Default dinamis untuk judul_db setelah bidang_teks terdefinisi
        if (empty(trim($judul_db ?? '')) || $judul_db === 'Laporan Kegiatan Kedinasan' || $judul_db === ($jenis . ' Kedinasan')) {
            $judul_db = !empty(trim($request->input('judul', ''))) 
                ? trim($request->input('judul')) 
                : (!empty($jenis) ? "{$jenis} {$bidang_teks}" : "Laporan Kegiatan {$bidang_teks}");
        }

        // Tentukan tanggal utama dokumen
        $tanggalDokumen = $request->tanggal
            ?? ($formData['kegiatan'][0]['tanggal'] ?? null)
            ?? $request->notulen_tanggal
            ?? $request->sppd_tanggal
            ?? null;

        if (!$tanggalDokumen && $jenis === 'Laporan Mingguan') {
            foreach ($formData['mingguan'] ?? [] as $hari) {
                foreach ($hari['kegiatan'] ?? [] as $keg) {
                    if (!empty($keg['tanggal'])) {
                        $tanggalDokumen = $keg['tanggal'];
                        break 2;
                    }
                }
            }
        }
        $tanggalDokumen = $tanggalDokumen ?? date('Y-m-d');
        $tglFileCustom = strtolower(Carbon::parse($tanggalDokumen)->translatedFormat('d M y'));

        // ====================================================================
        // CABANG 1: LAPORAN MINGGUAN (FORMAT NASKAH DINAS RESMI A - E + LAMPIRAN)
        // ====================================================================
        if ($jenis === 'Laporan Mingguan') {
            $kegiatan_list = [];
            foreach ($formData['mingguan'] ?? [] as $hari_idx => &$hari) {
                if (!isset($hari['kegiatan'])) continue;
                foreach ($hari['kegiatan'] as $keg_idx => &$keg) {
                    if (empty(trim($keg['judul'] ?? '')) && empty(trim($keg['hasil_kegiatan'] ?? ''))) continue;

                    $itemBypass = $is_bypass || !empty($keg['bypass_ai']);
                    $kegiatan_list[] = [
                        'data' => $keg,
                        'hari' => $hari['hari'] ?? null,
                        'hari_idx' => $hari_idx,
                        'keg_idx' => $keg_idx,
                        'bypass' => $itemBypass,
                    ];
                }
            }
            unset($hari, $keg);

            $mingguanDates = [];
            foreach ($kegiatan_list as $item) {
                if (!empty($item['data']['tanggal'])) {
                    $mingguanDates[] = strtotime($item['data']['tanggal']);
                }
            }

            $tglUtamaArr = $this->formatTanggal($tanggalDokumen);
            $periodeLengkap = $this->formatPeriodeMingguanSd($mingguanDates, $tglUtamaArr['tgl_saja'], true);
            $periodeSingkat = $this->formatPeriodeMingguanSd($mingguanDates, $tglUtamaArr['tgl_saja'], false);
            $periodeSampaiDengan = str_replace(' s.d. ', ' sampai dengan ', $periodeLengkap);

            $ringkasanMingguan = $this->processLaporanMingguan(
                $kegiatan_list,
                $formData,
                $bidang_teks,
                $periodeLengkap,
                $periodeSingkat,
                $periodeSampaiDengan,
                $tglUtamaArr['tgl_saja'],
                $is_bypass,
                $api_key,
                $authorUser
            );

            // Simpan kembali judul hasil AI ke struktur formData['mingguan']
            foreach ($ringkasanMingguan['tabel_kegiatan'] as $rowKeg) {
                $hIdx = $rowKeg['hari_idx'] ?? null;
                $kIdx = $rowKeg['keg_idx'] ?? null;
                if ($hIdx !== null && $kIdx !== null && isset($formData['mingguan'][$hIdx]['kegiatan'][$kIdx])) {
                    $formData['mingguan'][$hIdx]['kegiatan'][$kIdx]['judul'] = $rowKeg['judul'];
                    $formData['mingguan'][$hIdx]['kegiatan'][$kIdx]['hasil_kegiatan_ai'] = $rowKeg['uraian'];
                    $formData['mingguan'][$hIdx]['kegiatan'][$kIdx]['output_ai'] = $rowKeg['output'];
                }
            }
            $tglTtdMingguan = (count($mingguanDates) > 0)
                ? $this->formatTanggal(date('Y-m-d', max($mingguanDates)))['tgl_saja']
                : $this->formatTanggal($tanggalDokumen)['tgl_saja'];
            $ringkasanMingguan['tgl_ttd'] = $tglTtdMingguan;
            $ringkasanMingguan['mingguan_dates'] = $mingguanDates;
            $formData['mingguan_ringkasan'] = $ringkasanMingguan;

            if (empty($kegiatan_list)) {
                $section->addText("Belum ada data kegiatan yang diisi.", ['italic' => true, 'color' => '999999']);
            } else {
                $this->buildWordMingguan($section, $phpWord, $ringkasanMingguan, $logoPath, $request, $authorUser);
            }

            if (count($mingguanDates) > 0) {
                $min = min($mingguanDates);
                $max = max($mingguanDates);
                $bulanMap = [1=>'jan','feb','mar','apr','mei','jun','jul','agt','sep','okt','nov','des'];
                $dMin = date('d', $min);
                $dMax = date('d', $max);
                $mMax = $bulanMap[date('n', $max)];
                $yMax = date('Y', $max);
                $rentang = ($min == $max) ? "{$dMin}_{$mMax}_{$yMax}" : "{$dMin}-{$dMax}_{$mMax}_{$yMax}";
            } else {
                $rentang = $this->getFormattedDateForFilename(date('Y-m-d'));
            }
            $nama_file = "laporan_mingguan_{$rentang}_" . rand(10000000, 99999999) . ".docx";
            $judul_db = !empty(trim($request->input('judul', ''))) 
                ? trim($request->input('judul')) 
                : ("Rekap Mingguan " . $bidang_teks);

        // ====================================================================
        // CABANG 2: LAPORAN HARIAN (INDIVIDU / E-KINERJA)
        // ====================================================================
        } elseif ($jenis === 'Laporan Harian') {
            $kegiatan_list = [];
            foreach ($formData['kegiatan'] ?? [] as $keg_idx => &$keg) {
                if (empty(trim($keg['judul'] ?? '')) && empty(trim($keg['hasil_kegiatan'] ?? ''))) continue;

                $itemBypass = $is_bypass || !empty($keg['bypass_ai']);
                $keg['tanggal'] = $keg['tanggal'] ?? $tanggalDokumen;

                $kegiatan_list[] = [
                    'data' => $keg,
                    'hari' => null,
                    'hari_idx' => null,
                    'keg_idx' => $keg_idx,
                    'bypass' => $itemBypass,
                ];
            }
            unset($keg);

            $itemsRender = [];
            $allPhotosHarian = [];

            foreach ($kegiatan_list as $index => $item) {
                $keg = $item['data'];
                $kIdx = $item['keg_idx'];

                $waktuTeks = ($keg['waktu_mulai'] ?? '08:00') . ' WIB s.d. ' . (!empty($keg['waktu_selesai']) ? $keg['waktu_selesai'] . ' WIB' : 'Selesai');
                $lokasiTeks = trim($keg['lokasi'] ?? '') !== '' ? trim($keg['lokasi']) : 'Kantor Dinas Komunikasi dan Informatika Kabupaten Ogan Komering Ilir';
                $tglKeg = !empty($keg['tanggal']) ? $keg['tanggal'] : $tanggalDokumen;
                $tglFormat = $this->formatTanggal($tglKeg);

                if ($index > 0 && !$item['bypass'] && !empty($api_key)) {
                    usleep(400000); // Jeda 0.4 detik antar-kegiatan agar tidak terkena limit antrean
                }

                if ($item['bypass'] || empty($api_key)) {
                    $aiRes = [
                        'judul'  => !empty(trim($keg['judul'] ?? '')) ? $this->formatJudulKegiatan($keg['judul']) : $this->formatJudulKegiatan('Kegiatan Harian'),
                        'uraian' => $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($keg['hasil_kegiatan'] ?? '')),
                        'output' => $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($keg['output'] ?? 'Kegiatan terlaksana dengan baik sesuai jadwal.')),
                    ];
                } else {
                    $konteksHarian = [
                        'nama_pegawai' => $authorUser->name ?? '',
                        'jabatan'      => $authorUser->jabatan ?? '',
                        'hari_tanggal' => $tglFormat['format_lengkap'] ?? $tglKeg,
                        'waktu'        => $waktuTeks,
                        'lokasi'       => $lokasiTeks,
                    ];

                    $aiRes = $this->prosesTeksDenganAI(
                        $keg['hasil_kegiatan'] ?? '',
                        $keg['judul'] ?? '',
                        $keg['output'] ?? '',
                        'Laporan Harian',
                        $bidang_teks,
                        $api_key,
                        $konteksHarian
                    );
                }

                $judul_final = $aiRes['judul'];
                $teks_ai = $aiRes['uraian'];
                $outputTeks = $aiRes['output'];

                if (isset($formData['kegiatan'][$kIdx])) {
                    $formData['kegiatan'][$kIdx]['judul'] = $judul_final;
                    $formData['kegiatan'][$kIdx]['hasil_kegiatan'] = $teks_ai;
                    $formData['kegiatan'][$kIdx]['hasil_kegiatan_ai'] = $teks_ai;
                    $formData['kegiatan'][$kIdx]['output'] = $outputTeks;
                    $formData['kegiatan'][$kIdx]['output_ai'] = $outputTeks;
                }

                for ($f = 1; $f <= 4; $f++) {
                    if (isset($keg["foto_{$f}_path"]) && file_exists($keg["foto_{$f}_path"]) && @getimagesize($keg["foto_{$f}_path"])) {
                        $allPhotosHarian[] = [
                            'path' => $keg["foto_{$f}_path"],
                            'judul' => $judul_final,
                            'waktu' => $waktuTeks,
                            'lokasi' => $lokasiTeks,
                        ];
                    }
                }

                $itemsRender[] = [
                    'no' => $index + 1,
                    'judul' => $judul_final,
                    'waktu' => $waktuTeks,
                    'lokasi' => $lokasiTeks,
                    'uraian' => $teks_ai,
                    'output' => $outputTeks,
                ];
            }

            $tglUtamaArr = $this->formatTanggal($tanggalDokumen);

            if (empty($itemsRender)) {
                $fallbackTgl = $this->getFormattedDateForFilename($tanggalDokumen);
                $nama_file = "laporan_harian_{$fallbackTgl}_" . rand(1000, 9999) . ".docx";
                $judul_db = !empty(trim($request->input('judul', ''))) 
                    ? trim($request->input('judul')) 
                    : ("Laporan Harian " . $bidang_teks);
                $section->addText("Belum ada data kegiatan yang diisi.", ['italic' => true, 'color' => '999999']);
            } else {
                $this->buildWordHarian($section, $phpWord, $itemsRender, $allPhotosHarian, $bidang_teks, $tglUtamaArr, $authorUser, $request);

                $nama_file = "Lap_harian_{$namaDepan}_{$tglFileCustom}_" . rand(1000, 9999) . ".docx";
                $judulUtama = trim($itemsRender[0]['judul'] ?? '');
                if (empty($judulUtama)) {
                    $judulUtama = !empty(trim($request->input('judul', ''))) 
                        ? trim($request->input('judul')) 
                        : ("Laporan Harian " . $bidang_teks);
                }
                if (count($itemsRender) > 1) {
                    $judul_db = $judulUtama . " (+" . (count($itemsRender) - 1) . " Kegiatan Lainnya)";
                } else {
                    $judul_db = $judulUtama;
                }
            }

        // ====================================================================
        // CABANG 3: NOTULENSI RAPAT & PERJALANAN DINAS (LHPD)
        // ====================================================================
        } else {
            $catatanRaw = htmlspecialchars($request->notulen_hasil ?? $request->sppd_hasil ?? '');
            
            if ($jenis === 'Notulensi Rapat') {
                $tgl_arr = $this->formatTanggal($request->notulen_tanggal ?? date('Y-m-d'));
                $waktu = ($request->notulen_waktu_mulai ?? '08:00') . ' WIB s.d. ' . (!empty($request->notulen_waktu_selesai) ? $request->notulen_waktu_selesai . ' WIB' : 'Selesai');
                $lokasiNotulen = !empty(trim($request->notulen_tempat ?? '')) ? trim($request->notulen_tempat) : 'Kantor Dinas Komunikasi dan Informatika Kabupaten Ogan Komering Ilir';

                if ($is_bypass || empty(trim($catatanRaw))) {
                    $aiRes = [
                        'judul'  => !empty($request->notulen_judul) ? $this->formatJudulKegiatan($request->notulen_judul) : $this->formatJudulKegiatan('Notulensi Rapat'),
                        'uraian' => $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($catatanRaw)),
                        'output' => 'Tercapainya kesepakatan dan rekomendasi tindak lanjut hasil rapat dinas.',
                    ];
                } else {
                    $konteksNotulen = [
                        'nama_pegawai' => $authorUser->name ?? '',
                        'jabatan'      => $authorUser->jabatan ?? '',
                        'hari_tanggal' => $tgl_arr['format_lengkap'] ?? ($request->notulen_tanggal ?? date('Y-m-d')),
                        'waktu'        => $waktu,
                        'lokasi'       => $lokasiNotulen,
                    ];

                    $aiRes = $this->prosesTeksDenganAI(
                        html_entity_decode($catatanRaw),
                        $request->notulen_judul ?? '',
                        '',
                        'Notulensi Rapat',
                        $bidang_teks,
                        $api_key,
                        $konteksNotulen
                    );
                }

                $teks_ai = $aiRes['uraian'];
                $output_ai = $aiRes['output'];

                if (empty($formData['notulen_judul'])) {
                    $formData['notulen_judul'] = $aiRes['judul'];
                    $request->merge(['notulen_judul' => $aiRes['judul']]);
                }
                $formData['notulen_hasil'] = $teks_ai;
                $formData['notulen_hasil_ai'] = $teks_ai;
                $formData['notulen_output'] = $output_ai;
                $formData['notulen_output_ai'] = $output_ai;

                $data_render = [
                    'format_tanggal' => $tgl_arr['format_lengkap'], 
                    'waktu' => $waktu, 
                    'tgl_saja' => $tgl_arr['tgl_saja'],
                    'output' => $output_ai,
                ];

                $fotosNotulen = [];
                for ($f = 1; $f <= 2; $f++) {
                    if (!empty($formData["notulen_foto_{$f}_path"]) && file_exists($formData["notulen_foto_{$f}_path"]) && @getimagesize($formData["notulen_foto_{$f}_path"])) {
                        $fotosNotulen[] = $formData["notulen_foto_{$f}_path"];
                    }
                }

                $this->buildWordNotulen($section, $phpWord, $data_render, $teks_ai, $logoPath, $request, $authorUser, $fotosNotulen);
                
                $tglFile = $this->getFormattedDateForFilename($request->notulen_tanggal ?? date('Y-m-d'));
                $nama_file = "notulensi_rapat_{$tglFile}_" . rand(10000000, 99999999) . ".docx";
                $judul_db = !empty(trim($formData['notulen_judul'] ?? '')) 
                    ? trim($formData['notulen_judul']) 
                    : (!empty(trim($request->notulen_judul ?? '')) 
                        ? trim($request->notulen_judul) 
                        : (!empty(trim($request->input('judul', ''))) 
                            ? trim($request->input('judul')) 
                            : 'Notulensi Rapat'));
                
            } elseif ($jenis === 'Laporan Perjalanan Dinas') {
                $tgl_arr = $this->formatTanggal($request->sppd_tanggal ?? date('Y-m-d'));
                $batas = (int) ($request->sppd_jumlah_petugas ?? 2);
                $petugasBerangkat = array_slice($request->sppd_petugas ?? [], 0, $batas);
                $petugasBerangkat = array_filter($petugasBerangkat, function($p) { return !empty($p['nama']); });

                $namaPetugasList = !empty($petugasBerangkat) 
                    ? implode(', ', array_map(function($p) {
                        return ($p['nama'] ?? '') . (!empty($p['pangkat']) ? ' (' . $p['pangkat'] . ')' : '');
                    }, $petugasBerangkat))
                    : ($authorUser->name ?? '');

                $lokasiSppd = !empty(trim($request->sppd_tempat_tujuan ?? '')) ? trim($request->sppd_tempat_tujuan) : 'Wilayah Kabupaten Ogan Komering Ilir';
                $waktuSppd = 'Tanggal ' . ($tgl_arr['format_lengkap'] ?? ($request->sppd_tanggal ?? date('Y-m-d')));

                if ($is_bypass || empty(trim($catatanRaw))) {
                    $aiRes = [
                        'judul'  => !empty($request->sppd_maksud) ? $this->formatJudulKegiatan($request->sppd_maksud) : $this->formatJudulKegiatan('Laporan Perjalanan Dinas'),
                        'uraian' => $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($catatanRaw)),
                        'output' => 'Terlaksananya agenda perjalanan dinas dengan lancar sesuai surat perintah tugas.',
                    ];
                } else {
                    $konteksSppd = [
                        'nama_pegawai' => $namaPetugasList,
                        'jabatan'      => $authorUser->jabatan ?? 'Pelaksana Tugas',
                        'hari_tanggal' => $tgl_arr['format_lengkap'] ?? ($request->sppd_tanggal ?? date('Y-m-d')),
                        'waktu'        => $waktuSppd,
                        'lokasi'       => $lokasiSppd,
                    ];

                    $aiRes = $this->prosesTeksDenganAI(
                        html_entity_decode($catatanRaw),
                        $request->sppd_maksud ?? '',
                        '',
                        'Laporan Perjalanan Dinas',
                        $bidang_teks,
                        $api_key,
                        $konteksSppd
                    );
                }

                $teks_ai = $aiRes['uraian'];
                $output_ai = $aiRes['output'];

                if (empty($formData['sppd_maksud'])) {
                    $formData['sppd_maksud'] = $aiRes['judul'];
                    $request->merge(['sppd_maksud' => $aiRes['judul']]);
                }
                $formData['sppd_hasil'] = $teks_ai;
                $formData['sppd_hasil_ai'] = $teks_ai;
                $formData['sppd_output'] = $output_ai;
                $formData['sppd_output_ai'] = $output_ai;

                $tgl_arr = $this->formatTanggal($request->sppd_tanggal ?? date('Y-m-d'));
                $batas = (int) ($request->sppd_jumlah_petugas ?? 2);
                $petugasBerangkat = array_slice($request->sppd_petugas ?? [], 0, $batas);
                $petugasBerangkat = array_filter($petugasBerangkat, function($p) { return !empty($p['nama']); });
                
                $fotosSppd = [];
                for ($f = 1; $f <= 2; $f++) {
                    if (isset($formData["sppd_foto_{$f}_path"]) && file_exists($formData["sppd_foto_{$f}_path"]) && @getimagesize($formData["sppd_foto_{$f}_path"])) {
                        $fotosSppd[$f] = $formData["sppd_foto_{$f}_path"];
                    }
                }

                $maksud = $formData['sppd_maksud'] ?? $request->sppd_maksud;
                $judul_dok = !empty(trim($request->sppd_judul_dokumentasi)) ? $request->sppd_judul_dokumentasi : "DOKUMENTASI PERJALANAN DINAS " . strtoupper($maksud);

                $data_render = [
                    'tujuan' => $request->sppd_tujuan,
                    'maksud' => $maksud,
                    'lama' => $request->sppd_lama,
                    'tanggal' => $tgl_arr['format_lengkap'],
                    'tgl_saja' => $tgl_arr['tgl_saja'],
                    'petugas' => $petugasBerangkat,
                    'judul_dokumentasi' => $judul_dok,
                    'output' => $output_ai,
                ];

                $this->buildWordPerjalananDinas($section, $phpWord, $data_render, $teks_ai, $logoPath, $fotosSppd, $request);
                
                $tglFile = $this->getFormattedDateForFilename($request->sppd_tanggal ?? date('Y-m-d'));
                $nama_file = "laporan_perjalanan_dinas_{$tglFile}_" . rand(10000000, 99999999) . ".docx";
                $tujuanSppd = trim($request->sppd_tujuan ?? '');
                $judul_db = !empty($tujuanSppd) 
                    ? ("Perjalanan Dinas: " . $tujuanSppd) 
                    : (!empty(trim($request->input('judul', ''))) 
                        ? trim($request->input('judul')) 
                        : "Laporan Perjalanan Dinas {$bidang_teks}");
            } else {
                $tglFile = $this->getFormattedDateForFilename($tanggalDokumen);
                $nama_file = "dokumen_{$tglFile}_" . rand(10000000, 99999999) . ".docx";
                $judul_db = !empty(trim($request->input('judul', ''))) 
                    ? trim($request->input('judul')) 
                    : (!empty($jenis) ? ($jenis . ' ' . $bidang_teks) : ('Laporan Kegiatan ' . $bidang_teks));
                $section->addText("Format dokumen kedinasan: " . ($jenis ?: 'Umum'), ['bold' => true]);
            }
        }

        // Pastikan nama_file memiliki fallback agar tidak pernah null
        if (empty($nama_file)) {
            $fallbackTgl = $this->getFormattedDateForFilename($tanggalDokumen);
            $nama_file = "dokumen_{$fallbackTgl}_" . rand(10000000, 99999999) . ".docx";
        }

        // Pastikan judul_db selalu terdefinisi dan tidak pernah null atau kosong
        if (empty(trim($judul_db ?? ''))) {
            $judul_db = !empty(trim($request->input('judul', ''))) 
                ? trim($request->input('judul')) 
                : (!empty($jenis) ? ($jenis . ' - ' . $bidang_teks) : ('Laporan Kegiatan ' . $bidang_teks));
        }

        // ====================================================================
        // SIMPAN FILE .DOCX SECARA LOKAL DI STORAGE
        // ====================================================================
        Storage::disk('public')->makeDirectory('reports');
        Storage::disk('public')->makeDirectory('reports_assets');
        
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $savePath = storage_path('app/public/reports/');
        if (!file_exists($savePath)) { 
            mkdir($savePath, 0775, true); 
        }
        $objWriter->save($savePath . $nama_file);

        // ====================================================================
        // PENENTUAN STATUS DOKUMEN BERDASARKAN ROLE & TINDAKAN
        // ====================================================================
        if ($jenis === 'Laporan Harian') {
            $status = 'approved';
            $approvedBy = $authorId;
            $approvedAt = now();
        } else {
            if ($action === 'preview') {
                $status = 'draft';
                $approvedBy = null;
                $approvedAt = null;
            } elseif ($action === 'draft') {
                $status = 'draft';
                $approvedBy = null;
                $approvedAt = null;
            } else {
                // $action === 'final'
                if (in_array($currentUser->role, ['super_admin', 'admin_bidang', 'admin'])) {
                    $status = 'approved';
                    $approvedBy = $currentUser->id;
                    $approvedAt = now();
                } else {
                    $status = 'pending';
                    $approvedBy = null;
                    $approvedAt = null;
                }
            }
        }

        $reportAttributes = [
            'user_id' => $authorId,
            'judul' => $judul_db,
            'jenis_dokumen' => $jenis,
            'bidang' => $authorUser->bidang ?? $currentUser->bidang,
            'tanggal' => $tanggalDokumen,
            'form_data' => $formData,
            'html_hasil' => $nama_file,
            'status' => $status,
            'approved_by' => $approvedBy,
            'approved_at' => $approvedAt,
            'is_manual_edit' => false,
        ];

        if ($status !== 'rejected') {
            $reportAttributes['catatan_revisi'] = null;
        }

        $report = Report::updateOrCreate(
            ['id' => $reportId],
            $reportAttributes
        );

        if ($action === 'preview' || $request->ajax() || $request->wantsJson()) {
            $redirectUrl = in_array($currentUser->role, ['super_admin', 'admin_bidang', 'admin']) 
                ? route('admin.laporan.history') 
                : route('report.history');
                
            $previewHtml = view('admin.laporan.preview_modal_content', [
                'report' => $report,
                'formData' => $formData,
            ])->render();

            return response()->json([
                'success' => true,
                'report_id' => $report->id,
                'judul' => $report->judul,
                'jenis_dokumen' => $report->jenis_dokumen,
                'bidang' => $report->bidang,
                'status' => $report->status,
                'preview_html' => $previewHtml,
                'form_data' => $formData,
                'redirect_url' => $redirectUrl,
                'submit_kabid_url' => route('report.submitKabid', $report->id),
                'edit_url' => route('report.edit', $report->id),
                'role' => $currentUser->role,
                'message' => 'Dokumen berhasil di-generate dan draf telah disimpan!'
            ]);
        }

        if ($jenis === 'Laporan Harian' && $status === 'approved') {
            $msg = 'Laporan Harian berhasil disimpan dan siap diunduh untuk pelaporan e-Kinerja!';
        } elseif ($status === 'pending') {
            $msg = 'Laporan berhasil diajukan ke Admin Bidang untuk diperiksa dan disetujui!';
        } elseif ($status === 'approved') {
            $msg = 'Laporan berhasil difinalisasi dan langsung disetujui!';
        } else {
            $msg = 'Draf laporan berhasil disimpan secara lokal dan siap diunduh!';
        }

        if (in_array($currentUser->role, ['super_admin', 'admin_bidang', 'admin'])) {
            return redirect()->route('admin.laporan.history')->with('success', $msg);
        }
        return redirect()->route('report.history')->with('success', $msg);
    }

    /**
     * Proses AI & Penyusunan Struktur Naskah Dinas Laporan Mingguan (A - E + Lampiran)
     */
    private function processLaporanMingguan(
        array $kegiatanList,
        array $formData,
        string $bidangShort,
        string $periodeLengkap,
        string $periodeSingkat,
        string $periodeSampaiDengan,
        string $fallbackTglSaja,
        bool $isGlobalBypass,
        ?string $apiKey,
        ?User $authorUser = null
    ): array {
        $authorUser = $authorUser ?: (auth()->check() ? auth()->user() : null);
        $namaPegawai = $authorUser->name ?? 'Pegawai Diskominfo OKI';
        $jabatanPegawai = $authorUser->jabatan ?? 'Aparatur Sipil Negara';

        $namaBidangUpper = $this->getNamaLengkapBidang($bidangShort);
        $namaBidangTitle = $this->getNamaBidangTitleCase($bidangShort);

        $manualKendala = trim($formData['mingguan_kendala'] ?? '');
        $manualTindakLanjut = trim($formData['mingguan_tindak_lanjut'] ?? '');

        $rowsInput = [];
        $allPhotos = [];
        $needAi = false;

        foreach ($kegiatanList as $idx => $item) {
            $keg = $item['data'];
            $tglArr = !empty($keg['tanggal']) ? $this->formatTanggal($keg['tanggal']) : null;
            $hariTanggal = $tglArr ? $tglArr['format_lengkap'] : ($item['hari'] ?? $fallbackTglSaja);

            $judulRaw = trim($keg['judul'] ?? '');
            $catatanRaw = trim($keg['hasil_kegiatan'] ?? '');
            $outputRaw = trim($keg['output'] ?? '');
            $lokasiRaw = trim($keg['lokasi'] ?? '');
            $waktuRaw = ($keg['waktu_mulai'] ?? '08:00') . ' WIB s.d. ' . (!empty($keg['waktu_selesai']) ? $keg['waktu_selesai'] . ' WIB' : 'Selesai');

            if (!$item['bypass'] && !$isGlobalBypass && !empty($apiKey)) {
                $needAi = true;
            }

            $fotoPaths = [];
            for ($f = 1; $f <= 4; $f++) {
                if (!empty($keg["foto_{$f}_path"]) && file_exists($keg["foto_{$f}_path"]) && @getimagesize($keg["foto_{$f}_path"])) {
                    $fotoPaths[] = $keg["foto_{$f}_path"];
                }
            }

            $rowsInput[] = [
                'no' => $idx + 1,
                'hari_idx' => $item['hari_idx'],
                'keg_idx' => $item['keg_idx'],
                'hari_tanggal' => $hariTanggal,
                'waktu' => $waktuRaw,
                'judul_raw' => $judulRaw,
                'catatan_raw' => $catatanRaw,
                'output_raw' => $outputRaw,
                'lokasi' => $lokasiRaw !== '' ? $lokasiRaw : 'Kantor Dinas Komunikasi dan Informatika Kabupaten Ogan Komering Ilir',
                'bypass' => $item['bypass'],
                'foto_paths' => $fotoPaths,
            ];
        }

        // Susun default fallback yang formal dan rapi
        $pendahuluanUmum = "Laporan mingguan ini disusun sebagai dokumentasi dan bahan pemantauan pelaksanaan kegiatan pada {$namaBidangTitle} Dinas Komunikasi dan Informatika Kabupaten Ogan Komering Ilir selama periode {$periodeSampaiDengan}.";
        $pendahuluanTujuan = "Memberikan informasi mengenai pelaksanaan kegiatan pada {$namaBidangTitle}, hasil yang dicapai, kendala yang ditemukan, serta tindak lanjut yang diperlukan selama periode {$periodeSampaiDengan}.";
        $penutup = "Demikian laporan mingguan ini disusun sebagai bahan dokumentasi, monitoring, dan evaluasi pelaksanaan kegiatan {$namaBidangTitle} Dinas Komunikasi dan Informatika Kabupaten Ogan Komering Ilir.";

        $aiParsed = ['kegiatan' => []];
        if ($needAi && count($rowsInput) > 0) {
            $batchItems = [];
            foreach ($rowsInput as $idx => $r) {
                if (!$r['bypass']) {
                    $batchItems[] = [
                        'index'          => $idx,
                        'hari_tanggal'   => $r['hari_tanggal'],
                        'waktu'          => $r['waktu'],
                        'lokasi'         => $r['lokasi'],
                        'judul_awal'     => $r['judul_raw'] !== '' ? $r['judul_raw'] : 'Pelaksanaan Kegiatan ' . $r['no'],
                        'catatan_mentah' => $r['catatan_raw'] !== '' ? strip_tags($r['catatan_raw']) : '-',
                        'output_awal'    => $r['output_raw'] !== '' ? $r['output_raw'] : '-',
                    ];
                }
            }

            if (!empty($batchItems)) {
                $systemInstruction = "Anda adalah asisten AI perumus naskah dan laporan kedinasan resmi Pemerintah Kabupaten Ogan Komering Ilir (Diskominfo OKI).\n"
                    . "Tugas Anda adalah merumuskan dan mengembangkan seluruh daftar kegiatan mingguan aparatur pemerintah menjadi naskah dinas formal EYD baku secara serentak dalam satu respons JSON.\n\n"
                    . "ATURAN WAJIB:\n"
                    . "1. SUDUT PANDANG SUBJEK KOLEKTIF BIDANG: Ini adalah laporan kinerja kolektif BIDANG/UNIT KERJA, BUKAN laporan individu penginput. Gunakan subjek kolektif seperti 'Jajaran {$namaBidangTitle} Dinas Komunikasi dan Informatika Kabupaten Ogan Komering Ilir' atau 'Tim Kerja {$namaBidangTitle} Diskominfo Kabupaten OKI'. DILARANG menyebut nama pribadi pegawai penginput sebagai pelaku tunggal di dalam narasi uraian mingguan.\n"
                    . "2. DILARANG MENYALIN ULANG: DILARANG menyalin ulang catatan mentah user secara verbatim. Kembangkan catatan singkat setiap kegiatan menjadi 1 paragraf narasi dinas pemerintahan EYD yang mengalir dengan unsur 5W+1H lengkap (Apa kegiatannya, Siapa pelaksananya yaitu subjek kolektif bidang, Kapan hari/tanggal/jam, Di mana lokasinya, Mengapa/tujuan, dan Bagaimana proses pelaksanaannya).\n"
                    . "3. LOKASI DEFAULT: Jika lokasi pada suatu kegiatan kosong atau tidak spesifik, gunakan 'Kantor Dinas Komunikasi dan Informatika Kabupaten Ogan Komering Ilir'.\n"
                    . "4. SINGKATAN KEDINASAN: Pertahankan penulisan huruf kapital penuh untuk singkatan kedinasan resmi (seperti BMD, ATK, SPJ, SPM, SPPD, SPT, IKU, SAKIP, SPBE, ASN, SRIKANDI, OKI, WIB) pada judul, uraian, maupun output.\n"
                    . "5. HAPUS TITIK PADA JUDUL: DILARANG memberi tanda titik di akhir judul kegiatan. Judul harus berupa Title Case formal ringkas maksimal 8 kata tanpa titik di akhir (gantikan judul generik seperti 'Pelaksanaan Kegiatan 1').\n"
                    . "6. STERILISASI SIMBOL: DILARANG menggunakan simbol ketikan mentah atau bullet points seperti '-', '/', '[', ']', '*', '#'. Seluruh catatan HARUS diubah menjadi kalimat-kalimat paragraf yang utuh, padat, dan mengalir rapi.\n"
                    . "7. FORMAT KELUARAN WAJIB JSON: Kembalikan HANYA JSON murni (valid JSON) berisi array 'kegiatan' dengan objek untuk setiap item kegiatan:\n"
                    . "{\n"
                    . "  \"kegiatan\": [\n"
                    . "    {\n"
                    . "      \"index\": 0,\n"
                    . "      \"judul\": \"Judul Title Case Ringkas Maksimal 8 Kata Tanpa Titik\",\n"
                    . "      \"uraian\": \"1 paragraf narasi dinas 5W+1H sudut pandang kolektif bidang tanpa simbol mentah...\",\n"
                    . "      \"output\": \"1 kalimat capaian konkret...\"\n"
                    . "    }\n"
                    . "  ]\n"
                    . "}";

                $userPrompt = "KONTEKS LAPORAN MINGGUAN BIDANG:\n"
                    . "- Jenis Dokumen: Laporan Mingguan\n"
                    . "- Unit Kerja / Bidang: {$namaBidangTitle} Dinas Komunikasi dan Informatika Kabupaten Ogan Komering Ilir\n"
                    . "- Periode Pelaksanaan: {$periodeSampaiDengan}\n\n"
                    . "DAFTAR SELURUH KEGIATAN YANG HARUS DIPROSES SERENTAK (JSON):\n"
                    . json_encode($batchItems, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n"
                    . "Instruksi: Olah seluruh item di atas dan kembalikan array 'kegiatan' berisi seluruh index yang tertera.";

                $models = [
                    env('GEMINI_MODEL', 'gemini-3.6-flash'),
                    'gemini-3.5-flash',
                    'gemini-flash-lite-latest',
                    'gemma-4-31b-it',
                ];
                $models = array_values(array_unique(array_filter($models)));

                $batchSuccess = false;
                foreach ($models as $mIdx => $model) {
                    try {
                        $isGemma = str_contains(strtolower($model), 'gemma');
                        $postData = [
                            'contents' => [
                                [
                                    'parts' => [
                                        ['text' => $systemInstruction . "\n\n" . $userPrompt]
                                    ]
                                ]
                            ],
                        ];

                        // gemma-4-31b-it dan model seri Gemma tidak mendukung parameter responseMimeType: "application/json"
                        if (!$isGemma) {
                            $postData['generationConfig'] = [
                                'responseMimeType' => 'application/json',
                            ];
                        }

                        $response = Http::withHeaders(['Content-Type' => 'application/json'])
                            ->withoutVerifying()
                            ->timeout(45)
                            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", $postData);

                        if ($response->successful()) {
                            $rawJson = trim($response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '');
                            if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $rawJson, $matches)) {
                                $rawJson = trim($matches[1]);
                            } else {
                                $rawJson = preg_replace('/^```(?:json)?\s*/i', '', $rawJson);
                                $rawJson = preg_replace('/\s*```$/', '', $rawJson);
                            }
                            $parsed = json_decode(trim($rawJson), true);

                            if (is_array($parsed)) {
                                $itemsFromAi = [];
                                if (isset($parsed['kegiatan']) && is_array($parsed['kegiatan'])) {
                                    $itemsFromAi = $parsed['kegiatan'];
                                } elseif (isset($parsed[0]) && is_array($parsed[0])) {
                                    $itemsFromAi = $parsed;
                                }

                                if (!empty($itemsFromAi)) {
                                    $hasExplicitIndex = false;
                                    foreach ($itemsFromAi as $itemAi) {
                                        if (is_array($itemAi) && isset($itemAi['index'])) {
                                            $hasExplicitIndex = true;
                                            break;
                                        }
                                    }

                                    foreach ($itemsFromAi as $seqIdx => $itemAi) {
                                        if (!is_array($itemAi)) {
                                            continue;
                                        }

                                        $targetIdx = ($hasExplicitIndex && isset($itemAi['index']))
                                            ? (int) $itemAi['index']
                                            : ($batchItems[$seqIdx]['index'] ?? $seqIdx);

                                        $aiParsed['kegiatan'][$targetIdx] = [
                                            'judul'  => !empty($itemAi['judul']) ? $this->formatJudulKegiatan($itemAi['judul']) : '',
                                            'uraian' => !empty($itemAi['uraian']) ? $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($itemAi['uraian'])) : '',
                                            'output' => !empty($itemAi['output']) ? $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($itemAi['output'])) : '',
                                        ];
                                    }

                                    if (!empty($aiParsed['kegiatan'])) {
                                        $batchSuccess = true;
                                        break;
                                    }
                                }
                            }
                        }

                        $status = $response->status();
                        Log::warning("Gemini API Mingguan Batch ({$model}) HTTP {$status}: " . substr($response->body(), 0, 300));

                        if (in_array($status, [429, 503])) {
                            sleep(1);
                        }
                    } catch (\Throwable $e) {
                        Log::error("Gemini API Exception Mingguan Batch on model {$model}: " . $e->getMessage());
                        if ($mIdx < count($models) - 1) {
                            sleep(1);
                        }
                    }
                }
            }
        }

        foreach ($rowsInput as $idx => $r) {
            if (!$r['bypass'] && !isset($aiParsed['kegiatan'][$idx])) {
                $judulFallback = $r['judul_raw'] !== '' 
                    ? $this->formatJudulKegiatan($r['judul_raw']) 
                    : $this->formatJudulKegiatan('Pelaksanaan Kegiatan ' . $r['no']);
                $aiParsed['kegiatan'][$idx] = [
                    'judul'  => $judulFallback,
                    'uraian' => $r['catatan_raw'] !== '' ? $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($r['catatan_raw'])) : $judulFallback,
                    'output' => $r['output_raw'] !== '' ? $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($r['output_raw'])) : 'Terlaksananya kegiatan dengan lancar sesuai target.',
                ];
            }
        }

        $tabelKegiatan = [];
        $hasilDicapaiFallback = [];

        foreach ($rowsInput as $i => $r) {
            $aiItem = (!$r['bypass'] && isset($aiParsed['kegiatan'][$i])) ? $aiParsed['kegiatan'][$i] : null;

            $judulFinal = !empty($aiItem['judul'])
                ? $this->formatJudulKegiatan($aiItem['judul'])
                : ($r['judul_raw'] !== ''
                    ? $this->formatJudulKegiatan($r['judul_raw'])
                    : $this->formatJudulKegiatan('Pelaksanaan Kegiatan ' . $r['no']));

            $uraianFinal = !empty($aiItem['uraian'])
                ? $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($aiItem['uraian']))
                : ($r['catatan_raw'] !== '' ? $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($r['catatan_raw'])) : $judulFinal);

            $outputFinal = !empty($aiItem['output'])
                ? $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($aiItem['output']))
                : ($r['output_raw'] !== '' ? $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($r['output_raw'])) : 'Terlaksananya ' . lcfirst($judulFinal) . ' dengan baik.');

            $tabelKegiatan[] = [
                'no' => $r['no'],
                'hari_idx' => $r['hari_idx'],
                'keg_idx' => $r['keg_idx'],
                'hari_tanggal' => $r['hari_tanggal'],
                'judul' => $judulFinal,
                'uraian' => $uraianFinal,
                'output' => $outputFinal,
                'foto_paths' => $r['foto_paths'],
            ];

            $hasilDicapaiFallback[] = $outputFinal;

            foreach ($r['foto_paths'] as $pPath) {
                $allPhotos[] = [
                    'path' => $pPath,
                    'judul' => $judulFinal,
                    'hari_tanggal' => $r['hari_tanggal'],
                ];
            }
        }

        $hasilDicapai = array_slice($hasilDicapaiFallback, 0, 5);

        if ($manualKendala !== '') {
            $kendalaFinal = $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($manualKendala));
        } else {
            $kendalaFinal = "Selama pelaksanaan kegiatan pada periode {$periodeSampaiDengan} tidak ditemukan kendala yang berarti; seluruh rangkaian kegiatan berjalan sesuai jadwal yang telah ditetapkan.";
        }

        if ($manualTindakLanjut !== '') {
            $tindakLanjutFinal = $this->pertahankanSingkatanKedinasan($this->bersihkanSimbol($manualTindakLanjut));
        } else {
            $tindakLanjutFinal = "Melaksanakan pemantauan, evaluasi, dan koordinasi secara berkala guna menjaga kelancaran pelaksanaan tugas pada {$namaBidangTitle}.";
        }

        return [
            'bidang_short' => $bidangShort,
            'bidang_upper' => $namaBidangUpper,
            'bidang_title' => $namaBidangTitle,
            'periode_lengkap' => $periodeLengkap,
            'periode_singkat' => $periodeSingkat,
            'pendahuluan_umum' => $pendahuluanUmum,
            'pendahuluan_tujuan' => $pendahuluanTujuan,
            'tabel_kegiatan' => $tabelKegiatan,
            'hasil_dicapai' => $hasilDicapai,
            'kendala' => $kendalaFinal,
            'tindak_lanjut' => $tindakLanjutFinal,
            'penutup' => $penutup,
            'all_photos' => $allPhotos,
        ];
    }

    /**
     * Render Dokumen Word Laporan Mingguan 100% Sesuai Template Resmi Diskominfo OKI
     */
    private function buildWordMingguan($section, $phpWord, array $ringkasan, $logoPath, $req, $authorUser)
    {
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);

        if ($req->pakai_kop === 'ya' && !empty($logoPath)) {
            $this->addKopSurat($section, $logoPath);
        }

        // Judul Dokumen
        $section->addText(
            "LAPORAN MINGGUAN",
            ['bold' => true, 'size' => 12, 'underline' => 'single'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 20]
        );
        $section->addText(
            strtoupper($ringkasan['bidang_upper']),
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 20]
        );
        $section->addText(
            "PERIODE " . strtoupper($ringkasan['periode_lengkap']),
            ['bold' => true, 'size' => 10],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 140]
        );

        // A. PENDAHULUAN
        $section->addText("A. PENDAHULUAN", ['bold' => true, 'size' => 10], ['spaceAfter' => 60]);

        $runA1 = $section->addTextRun(['alignment' => Jc::BOTH, 'spaceAfter' => 80, 'lineHeight' => 1.15]);
        $runA1->addText("1. Umum. ", ['bold' => true, 'size' => 10]);
        $runA1->addText($ringkasan['pendahuluan_umum'], ['size' => 10]);

        $runA2 = $section->addTextRun(['alignment' => Jc::BOTH, 'spaceAfter' => 140, 'lineHeight' => 1.15]);
        $runA2->addText("2. Maksud dan Tujuan. ", ['bold' => true, 'size' => 10]);
        $runA2->addText($ringkasan['pendahuluan_tujuan'], ['size' => 10]);

        // B. KEGIATAN YANG TELAH DILAKSANAKAN
        $section->addText("B. KEGIATAN YANG TELAH DILAKSANAKAN", ['bold' => true, 'size' => 10], ['spaceAfter' => 60]);
        $periodeBody = str_replace(' s.d. ', ' sampai dengan ', $ringkasan['periode_lengkap']);
        $section->addText(
            "Kegiatan {$ringkasan['bidang_title']} yang dilaksanakan selama periode {$periodeBody} adalah sebagai berikut:",
            ['size' => 10],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 100, 'lineHeight' => 1.15]
        );

        // Tabel 4 Kolom: No. | Hari/Tanggal | Uraian Kegiatan | Hasil/Output
        $styleTabelKeg = [
            'borderSize' => 6,
            'borderColor' => '64748B',
            'cellMarginTop' => 80,
            'cellMarginBottom' => 80,
            'cellMarginLeft' => 100,
            'cellMarginRight' => 100,
        ];
        $phpWord->addTableStyle('TabelMingguanStyle', $styleTabelKeg);
        $tblKeg = $section->addTable('TabelMingguanStyle');

        // Header Row
        $tblKeg->addRow(null, ['tblHeader' => true]);
        $tblKeg->addCell(600, ['bgColor' => 'E2E8F0', 'valign' => 'center'])->addText("No.", ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $tblKeg->addCell(2100, ['bgColor' => 'E2E8F0', 'valign' => 'center'])->addText("Hari/Tanggal", ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $tblKeg->addCell(3800, ['bgColor' => 'E2E8F0', 'valign' => 'center'])->addText("Uraian Kegiatan", ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $tblKeg->addCell(3000, ['bgColor' => 'E2E8F0', 'valign' => 'center'])->addText("Hasil/Output", ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        // Data Rows: Kolom Uraian Kegiatan selalu menampilkan judul (Bold) di baris pertama diikuti uraian di bawahnya, dan Hasil/Output diisi output AI
        foreach ($ringkasan['tabel_kegiatan'] as $row) {
            $tblKeg->addRow(null, ['cantSplit' => true]);
            $tblKeg->addCell(600, ['valign' => 'top'])->addText((string) $row['no'], ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $tblKeg->addCell(2100, ['valign' => 'top'])->addText($row['hari_tanggal'], ['size' => 10], ['spaceAfter' => 0, 'lineHeight' => 1.15]);

            $cellUraian = $tblKeg->addCell(3800, ['valign' => 'top']);
            $cellUraian->addText($row['judul'], ['bold' => true, 'size' => 10], ['spaceAfter' => 30]);
            $paragraphs = explode("\n", $row['uraian']);
            foreach ($paragraphs as $p) {
                if (trim($p) !== '') {
                    $cellUraian->addText(trim($p), ['size' => 10], ['alignment' => Jc::BOTH, 'spaceAfter' => 40, 'lineHeight' => 1.15]);
                }
            }

            $tblKeg->addCell(3000, ['valign' => 'top'])->addText($row['output'], ['size' => 10], ['alignment' => Jc::BOTH, 'spaceAfter' => 0, 'lineHeight' => 1.15]);
        }

        $section->addTextBreak(1, ['size' => 6]);

        // C. HASIL YANG DICAPAI
        $section->addText("C. HASIL YANG DICAPAI", ['bold' => true, 'size' => 10], ['spaceAfter' => 60]);
        foreach ($ringkasan['hasil_dicapai'] as $idx => $poin) {
            $section->addText(
                ($idx + 1) . ". " . $poin,
                ['size' => 10],
                ['alignment' => Jc::BOTH, 'spaceAfter' => 60, 'lineHeight' => 1.15]
            );
        }

        $section->addTextBreak(1, ['size' => 4]);

        // D. KENDALA DAN TINDAK LANJUT
        $section->addText("D. KENDALA DAN TINDAK LANJUT", ['bold' => true, 'size' => 10], ['spaceAfter' => 60]);

        $runD1 = $section->addTextRun(['alignment' => Jc::BOTH, 'spaceAfter' => 80, 'lineHeight' => 1.15]);
        $runD1->addText("1. Kendala. ", ['bold' => true, 'size' => 10]);
        $runD1->addText($ringkasan['kendala'], ['size' => 10]);

        $runD2 = $section->addTextRun(['alignment' => Jc::BOTH, 'spaceAfter' => 140, 'lineHeight' => 1.15]);
        $runD2->addText("2. Tindak Lanjut. ", ['bold' => true, 'size' => 10]);
        $runD2->addText($ringkasan['tindak_lanjut'], ['size' => 10]);

        // E. PENUTUP
        $section->addText("E. PENUTUP", ['bold' => true, 'size' => 10], ['spaceAfter' => 60]);
        $section->addText(
            $ringkasan['penutup'],
            ['size' => 10],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 180, 'lineHeight' => 1.15]
        );

        // BLOK TANDA TANGAN TERPADU (SESUAI PILIHAN DROPDOWN PENANDATANGAN PIMPINAN)
        $tglTtd = $ringkasan['tgl_ttd'] ?? ((isset($ringkasan['mingguan_dates']) && count($ringkasan['mingguan_dates']) > 0)
            ? $this->formatTanggal(date('Y-m-d', max($ringkasan['mingguan_dates'])))['tgl_saja']
            : $this->formatTanggal(date('Y-m-d'))['tgl_saja']);

        $this->addBlokTtdWord($section, $tglTtd, $authorUser, $req, 'Yang Menyusun,');

        // ====================================================================
        // HALAMAN BARU: LAMPIRAN DOKUMENTASI KEGIATAN MINGGUAN (GRID 2 KOLOM)
        // ====================================================================
        $allPhotos = $ringkasan['all_photos'] ?? [];
        if (count($allPhotos) > 0) {
            $section->addPageBreak();

            $section->addText(
                "LAMPIRAN",
                ['bold' => true, 'size' => 12],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );
            $section->addText(
                "DOKUMENTASI KEGIATAN MINGGUAN",
                ['bold' => true, 'size' => 12],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 40]
            );
            $section->addText(
                strtoupper($ringkasan['bidang_upper']) . " • " . strtoupper($ringkasan['periode_singkat']),
                ['bold' => true, 'size' => 10, 'color' => '475569'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 180]
            );

            $phpWord->addTableStyle('DokGridMingguanStyle', [
                'borderSize' => 0,
                'cellMarginTop' => 80,
                'cellMarginBottom' => 80,
                'cellMarginLeft' => 80,
                'cellMarginRight' => 80,
            ]);
            $tblFoto = $section->addTable('DokGridMingguanStyle');

            $cellBorder = [
                'borderSize' => 6,
                'borderColor' => 'CBD5E1',
                'cellMarginTop' => 80,
                'cellMarginBottom' => 80,
                'cellMarginLeft' => 80,
                'cellMarginRight' => 80,
                'valign' => 'top',
            ];

            $totalPhotos = count($allPhotos);
            for ($k = 0; $k < $totalPhotos; $k += 2) {
                $fotoKiri = $allPhotos[$k];
                $fotoKanan = ($k + 1 < $totalPhotos) ? $allPhotos[$k + 1] : null;

                $tblFoto->addRow(null, ['cantSplit' => true]);

                // Kolom Kiri
                $cellLeft = $tblFoto->addCell(4650, $cellBorder);
                if (!empty($fotoKiri['path']) && file_exists($fotoKiri['path']) && @getimagesize($fotoKiri['path'])) {
                    $cellLeft->addImage($fotoKiri['path'], [
                        'width' => 220,
                        'height' => 150,
                        'alignment' => Jc::CENTER,
                    ]);
                }
                $cellLeft->addTextBreak(1, ['size' => 4]);
                $cellLeft->addText(
                    $fotoKiri['judul'],
                    ['bold' => true, 'size' => 9],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 10]
                );
                $cellLeft->addText(
                    $fotoKiri['hari_tanggal'],
                    ['size' => 8.5, 'color' => '64748B'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                // Spacer Tengah
                $tblFoto->addCell(200, ['borderSize' => 0]);

                // Kolom Kanan
                $cellRight = $tblFoto->addCell(4650, $fotoKanan ? $cellBorder : ['borderSize' => 0]);
                if ($fotoKanan) {
                    if (!empty($fotoKanan['path']) && file_exists($fotoKanan['path']) && @getimagesize($fotoKanan['path'])) {
                        $cellRight->addImage($fotoKanan['path'], [
                            'width' => 220,
                            'height' => 150,
                            'alignment' => Jc::CENTER,
                        ]);
                    }
                    $cellRight->addTextBreak(1, ['size' => 4]);
                    $cellRight->addText(
                        $fotoKanan['judul'],
                        ['bold' => true, 'size' => 9],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 10]
                    );
                    $cellRight->addText(
                        $fotoKanan['hari_tanggal'],
                        ['size' => 8.5, 'color' => '64748B'],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );
                }

                if ($k + 2 < $totalPhotos) {
                    $tblFoto->addRow(120);
                    $tblFoto->addCell(4650, ['borderSize' => 0]);
                    $tblFoto->addCell(200, ['borderSize' => 0]);
                    $tblFoto->addCell(4650, ['borderSize' => 0]);
                }
            }
        }
    }

    private function formatPeriodeMingguanSd(array $timestamps, string $fallbackTglSaja, bool $fullStartMonth = true): string
    {
        if (empty($timestamps)) {
            return $fallbackTglSaja;
        }

        $min = min($timestamps);
        $max = max($timestamps);

        $bulanIndo = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        $dMin = (int) date('j', $min);
        $mMin = (int) date('n', $min);
        $yMin = (int) date('Y', $min);

        $dMax = (int) date('j', $max);
        $mMax = (int) date('n', $max);
        $yMax = (int) date('Y', $max);

        if ($min === $max) {
            return "{$dMin} " . $bulanIndo[$mMin] . " {$yMin}";
        }

        if ($yMin === $yMax && $mMin === $mMax) {
            return "{$dMin} s.d. {$dMax} " . $bulanIndo[$mMax] . " {$yMax}";
        }

        if ($yMin === $yMax) {
            return "{$dMin} " . $bulanIndo[$mMin] . " s.d. {$dMax} " . $bulanIndo[$mMax] . " {$yMax}";
        }

        return "{$dMin} " . $bulanIndo[$mMin] . " {$yMin} s.d. {$dMax} " . $bulanIndo[$mMax] . " {$yMax}";
    }

    private function getFormattedDateForFilename($tgl) 
    {
        $timestamp = strtotime($tgl ?? date('Y-m-d'));
        $bulan = [1=>'jan','feb','mar','apr','mei','jun','jul','agt','sep','okt','nov','des'];
        $d = date('d', $timestamp);
        $m = $bulan[date('n', $timestamp)] ?? 'lap';
        $y = date('Y', $timestamp);
        return "{$d}_{$m}_{$y}";
    }

    private function cleanResponse($text) 
    {
        return $this->bersihkanSimbol($text);
    }

    private function generateTitleAI($catatan, $api_key) 
    {
        if (empty(trim($catatan))) return "Kegiatan Tanpa Judul";
        $author = auth()->user();
        $konteks = [
            'nama_pegawai' => $author->name ?? '',
            'jabatan'      => $author->jabatan ?? '',
            'hari_tanggal' => date('d F Y'),
            'waktu'        => '08:00 WIB s.d. Selesai',
            'lokasi'       => 'Kantor Dinas Komunikasi dan Informatika Kabupaten Ogan Komering Ilir',
        ];
        $res = $this->prosesTeksDenganAI($catatan, '', '', 'Judul Kegiatan', '', $api_key, $konteks);
        return $res['judul'] ?? 'Kegiatan Kedinasan';
    }

    /**
     * Simpan file foto permanen dengan kompresi GD bawaan
     */
    private function savePermImage($file): ?string
    {
        if (!$file || !$file->isValid()) {
            return null;
        }

        Storage::disk('public')->makeDirectory('reports_assets');
        $filename = 'img_' . uniqid() . '.jpg';
        $path = storage_path('app/public/reports_assets/');
        if (!file_exists($path)) { 
            mkdir($path, 0775, true); 
        }
        
        $fullPath = $path . $filename;
        $this->kompresGambar($file->getPathname(), $fullPath);

        return (file_exists($fullPath) && filesize($fullPath) > 0) ? $fullPath : null;
    }

    /**
     * Kompresi gambar menggunakan ekstensi PHP GD bawaan:
     * imagescale maksimal lebar 850px, simpan sebagai JPEG kualitas 70%
     * Memastikan file Word .docx tetap ringan di bawah 1 MB meskipun diunggah dari iPhone.
     */
    private function kompresGambar($fileMentah, $pathTujuan): bool
    {
        if (!file_exists($fileMentah)) {
            return false;
        }

        $info = @getimagesize($fileMentah);
        if (!$info) {
            return (bool) @copy($fileMentah, $pathTujuan);
        }

        $mime = $info['mime'] ?? '';
        $image = null;

        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            $image = @imagecreatefromjpeg($fileMentah);
        } elseif ($mime === 'image/png') {
            $image = @imagecreatefrompng($fileMentah);
        } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
            $image = @imagecreatefromwebp($fileMentah);
        }

        if (!$image && function_exists('imagecreatefromstring')) {
            $str = @file_get_contents($fileMentah);
            if ($str !== false) {
                $image = @imagecreatefromstring($str);
            }
        }

        if (!$image) {
            return (bool) @copy($fileMentah, $pathTujuan);
        }

        $lebarAsli = imagesx($image);
        $tinggiAsli = imagesy($image);
        $lebarMaks = 850; 

        if ($lebarAsli > $lebarMaks) {
            $imageScaled = imagescale($image, $lebarMaks);
            if ($imageScaled) {
                imagedestroy($image);
                $image = $imageScaled;
            }
        }

        $saved = @imagejpeg($image, $pathTujuan, 70);
        imagedestroy($image);

        if (!$saved) {
            return (bool) @copy($fileMentah, $pathTujuan);
        }

        return true;
    }

    /**
     * Render Dokumen Word Laporan Harian Sesuai Template Pratinjau (Bagian A, B, C + TTD Kanan)
     */
    private function buildWordHarian($section, $phpWord, array $itemsRender, array $allPhotos, string $bidangShort, array $tglUtamaArr, $authorUser, $req = null)
    {
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);

        $namaBidangUpper = $this->getNamaLengkapBidang($bidangShort);
        $namaBidangTitle = $this->getNamaBidangTitleCase($bidangShort);

        $nipBersih = trim($authorUser->nip ?? '');
        $isTenagaAhli = (($authorUser->status_pegawai ?? 'ASN') === 'Tenaga Ahli') || empty($nipBersih) || $nipBersih === '-';
        $jabatanTeks = $authorUser->jabatan ?? ($isTenagaAhli ? 'Tenaga Ahli' : 'Staf Pelaksana');

        // Judul Dokumen
        $section->addText(
            "LAPORAN KEGIATAN HARIAN",
            ['bold' => true, 'size' => 12, 'underline' => 'single'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 20]
        );
        $section->addText(
            strtoupper($tglUtamaArr['format_lengkap']),
            ['bold' => true, 'size' => 10],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 160]
        );

        // A. IDENTITAS PELAKSANA
        $section->addText("A. IDENTITAS PELAKSANA", ['bold' => true, 'size' => 10], ['spaceAfter' => 60]);

        $phpWord->addTableStyle('IdentitasHarianClean', [
            'borderSize' => 0,
            'cellMarginTop' => 30,
            'cellMarginBottom' => 30,
            'cellMarginLeft' => 120,
            'cellMarginRight' => 60,
        ]);
        $tblId = $section->addTable('IdentitasHarianClean');

        $rowsId = [
            ['Nama Pegawai', $authorUser->name ?? '-', true],
        ];
        if (!$isTenagaAhli) {
            $rowsId[] = ['NIP', $nipBersih, false];
        }
        $rowsId[] = ['Jabatan / Status', $jabatanTeks, false];
        $rowsId[] = ['Unit Kerja', $namaBidangTitle, false];
        $rowsId[] = ['Hari / Tanggal', $tglUtamaArr['format_lengkap'], false];

        foreach ($rowsId as $r) {
            $tblId->addRow();
            $tblId->addCell(2200)->addText($r[0], ['size' => 10], ['spaceAfter' => 0]);
            $tblId->addCell(250)->addText(":", ['size' => 10], ['spaceAfter' => 0]);
            $tblId->addCell(7050)->addText($r[1], ['bold' => $r[2], 'size' => 10], ['spaceAfter' => 0]);
        }

        $section->addTextBreak(1, ['size' => 6]);

        // B. PELAKSANAAN KEGIATAN
        $section->addText("B. PELAKSANAAN KEGIATAN", ['bold' => true, 'size' => 10], ['spaceAfter' => 40]);
        $section->addText(
            "Adapun rincian pelaksanaan kegiatan harian pada {$tglUtamaArr['format_lengkap']} adalah sebagai berikut:",
            ['size' => 10],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 80]
        );

        $phpWord->addTableStyle('TabelHarianStyle', [
            'borderSize' => 6,
            'borderColor' => '64748B',
            'cellMarginTop' => 80,
            'cellMarginBottom' => 80,
            'cellMarginLeft' => 90,
            'cellMarginRight' => 90,
        ]);
        $tblKeg = $section->addTable('TabelHarianStyle');

        $tblKeg->addRow(null, ['tblHeader' => true]);
        $tblKeg->addCell(550, ['bgColor' => 'E2E8F0', 'valign' => 'center'])->addText("No.", ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $tblKeg->addCell(2050, ['bgColor' => 'E2E8F0', 'valign' => 'center'])->addText("Waktu & Lokasi", ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $tblKeg->addCell(4500, ['bgColor' => 'E2E8F0', 'valign' => 'center'])->addText("Uraian Kegiatan", ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $tblKeg->addCell(2400, ['bgColor' => 'E2E8F0', 'valign' => 'center'])->addText("Hasil / Output", ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        // Baris data: Uraian Kegiatan menampilkan judul (Bold) di atas dan uraian di bawahnya, Hasil/Output menampilkan output olahan AI
        foreach ($itemsRender as $row) {
            $tblKeg->addRow(null, ['cantSplit' => true]);
            $tblKeg->addCell(550, ['valign' => 'top'])->addText((string) $row['no'], ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

            $cellWaktu = $tblKeg->addCell(2050, ['valign' => 'top']);
            $cellWaktu->addText($row['waktu'], ['bold' => true, 'size' => 9.5], ['spaceAfter' => 30]);
            $cellWaktu->addText($row['lokasi'], ['size' => 9.5, 'color' => '334155'], ['spaceAfter' => 0]);

            $cellUraian = $tblKeg->addCell(4500, ['valign' => 'top']);
            $cellUraian->addText($row['judul'], ['bold' => true, 'size' => 10], ['spaceAfter' => 40]);
            $paragraphs = explode("\n", $row['uraian']);
            foreach ($paragraphs as $p) {
                if (trim($p) !== '') {
                    $cellUraian->addText(trim($p), ['size' => 10], ['alignment' => Jc::BOTH, 'spaceAfter' => 60, 'lineHeight' => 1.15]);
                }
            }

            $tblKeg->addCell(2400, ['valign' => 'top'])->addText($row['output'], ['size' => 9.5], ['alignment' => Jc::LEFT, 'spaceAfter' => 0, 'lineHeight' => 1.15]);
        }

        // C. DOKUMENTASI KEGIATAN (Grid 2 Kolom)
        if (count($allPhotos) > 0) {
            $section->addTextBreak(1, ['size' => 6]);
            $section->addText("C. DOKUMENTASI KEGIATAN", ['bold' => true, 'size' => 10], ['spaceAfter' => 80]);

            $phpWord->addTableStyle('DokHarianGridStyle', [
                'borderSize' => 0,
                'cellMarginTop' => 70,
                'cellMarginBottom' => 70,
                'cellMarginLeft' => 70,
                'cellMarginRight' => 70,
            ]);
            $tblFoto = $section->addTable('DokHarianGridStyle');

            $cellBorder = [
                'borderSize' => 6,
                'borderColor' => 'CBD5E1',
                'cellMarginTop' => 70,
                'cellMarginBottom' => 70,
                'cellMarginLeft' => 70,
                'cellMarginRight' => 70,
                'valign' => 'top',
            ];

            $totalPhotos = count($allPhotos);
            for ($k = 0; $k < $totalPhotos; $k += 2) {
                $fKiri = $allPhotos[$k];
                $fKanan = ($k + 1 < $totalPhotos) ? $allPhotos[$k + 1] : null;

                $tblFoto->addRow(null, ['cantSplit' => true]);

                $cLeft = $tblFoto->addCell(4650, $cellBorder);
                if (!empty($fKiri['path']) && file_exists($fKiri['path']) && @getimagesize($fKiri['path'])) {
                    $cLeft->addImage($fKiri['path'], [
                        'width' => 220,
                        'height' => 150,
                        'alignment' => Jc::CENTER,
                    ]);
                }
                $cLeft->addTextBreak(1, ['size' => 4]);
                $cLeft->addText($fKiri['judul'], ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER, 'spaceAfter' => 8]);
                $cLeft->addText($fKiri['waktu'] . " • " . $fKiri['lokasi'], ['size' => 8.5, 'color' => '64748B'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

                $tblFoto->addCell(200, ['borderSize' => 0]);

                $cRight = $tblFoto->addCell(4650, $fKanan ? $cellBorder : ['borderSize' => 0]);
                if ($fKanan) {
                    if (!empty($fKanan['path']) && file_exists($fKanan['path']) && @getimagesize($fKanan['path'])) {
                        $cRight->addImage($fKanan['path'], [
                            'width' => 220,
                            'height' => 150,
                            'alignment' => Jc::CENTER,
                        ]);
                    }
                    $cRight->addTextBreak(1, ['size' => 4]);
                    $cRight->addText($fKanan['judul'], ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER, 'spaceAfter' => 8]);
                    $cRight->addText($fKanan['waktu'] . " • " . $fKanan['lokasi'], ['size' => 8.5, 'color' => '64748B'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
                }

                if ($k + 2 < $totalPhotos) {
                    $tblFoto->addRow(120);
                    $tblFoto->addCell(4650, ['borderSize' => 0]);
                    $tblFoto->addCell(200, ['borderSize' => 0]);
                    $tblFoto->addCell(4650, ['borderSize' => 0]);
                }
            }
        }

        // BLOK TANDA TANGAN TERPADU (SESUAI PILIHAN DROPDOWN PENANDATANGAN PIMPINAN)
        $this->addBlokTtdWord($section, $tglUtamaArr['tgl_saja'], $authorUser, $req, 'Yang Menyusun,');
    }

    /**
     * Blok Tanda Tangan Laporan Harian (Hanya 1 blok kanan: Yang Melaporkan)
     */
    private function addTtdHarian($section, $tglSaja, $authorUser, $req = null)
    {
        $nipAuthor = trim($authorUser->nip ?? '');
        $isAuthorTA = (($authorUser->status_pegawai ?? 'ASN') === 'Tenaga Ahli') || empty($nipAuthor) || $nipAuthor === '-';

        $section->addTextBreak(1);
        $table = $section->addTable();
        $table->addRow();
        $table->addCell(5200);

        $cell = $table->addCell(4300, ['valign' => 'top']);
        $cell->addText("Kayuagung, " . $tglSaja, ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 20]);
        $cell->addText("Yang Melaporkan,", ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $cell->addTextBreak(3);
        $cell->addText($authorUser->name ?? '-', ['bold' => true, 'underline' => 'single', 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 20]);
        if (!$isAuthorTA) {
            $cell->addText("NIP. " . $nipAuthor, ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }
    }

    /**
     * Blok Tanda Tangan Notulensi Rapat
     */
    private function addTtdNotulen($section, $tglSaja, $authorUser, $req)
    {
        $authorUserCopy = clone $authorUser;
        $notulisNama = trim($req->notulen_pencatat ?? '');
        if (!empty($notulisNama)) {
            $authorUserCopy->name = $notulisNama;
            $uNotulis = User::where('name', $notulisNama)->first();
            if ($uNotulis) {
                $authorUserCopy->nip = $uNotulis->nip;
                $authorUserCopy->status_pegawai = $uNotulis->status_pegawai;
            }
        }
        $this->addBlokTtdWord($section, $tglSaja, $authorUserCopy, $req, 'Yang Menyusun,');
    }

    private function buildWordNotulen($section, $phpWord, $data, $teks_ai, $logoPath, $req, $authorUser, array $fotos = []) 
    {
        $phpWord->setDefaultFontSize(11);
        if ($req->pakai_kop === 'ya' && !empty($logoPath)) { 
            $this->addKopSurat($section, $logoPath); 
        }
        
        $section->addText("NOTULA", ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);
        
        $phpWord->addTableStyle('NotulenTable', ['borderSize' => 0, 'cellMargin' => 50]);
        $table = $section->addTable('NotulenTable');
        $table->addRow(); $table->addCell(2500)->addText("Sidang/Rapat"); $table->addCell(300)->addText(":"); $table->addCell(6700)->addText($req->notulen_judul ?? 'Rapat Kedinasan');
        $table->addRow(); $table->addCell(2500)->addText("Hari/Tanggal"); $table->addCell(300)->addText(":"); $table->addCell(6700)->addText($data['format_tanggal']);
        $table->addRow(); $table->addCell(2500)->addText("Waktu"); $table->addCell(300)->addText(":"); $table->addCell(6700)->addText($data['waktu']);
        
        $section->addTextBreak(1);
        $section->addText("Pimpinan Sidang/Rapat", ['bold' => true]);
        $table2 = $section->addTable('NotulenTable');
        $table2->addRow(); $table2->addCell(2500)->addText("Ketua"); $table2->addCell(300)->addText(":"); $table2->addCell(6700)->addText($req->notulen_ketua ?? '-');
        $table2->addRow(); $table2->addCell(2500)->addText("Sekretaris"); $table2->addCell(300)->addText(":"); $table2->addCell(6700)->addText($req->notulen_sekretaris ?? '-');
        $table2->addRow(); $table2->addCell(2500)->addText("Pencatat"); $table2->addCell(300)->addText(":"); $table2->addCell(6700)->addText($req->notulen_pencatat ?? '-');
        
        $section->addTextBreak(1);
        $section->addText("Peserta Sidang/Rapat:", ['bold' => true]);
        $pesertaArr = explode("\n", str_replace("\r", "", $req->notulen_peserta ?? ''));
        foreach ($pesertaArr as $idx => $pst) {
            if (trim($pst) != '') { $section->addText(($idx + 1) . ". " . trim($pst)); }
        }
        
        $section->addTextBreak(1);
        $section->addText("Kegiatan Sidang/Rapat (Uraian):", ['bold' => true]);
        $paragraphs = explode("\n", $teks_ai);
        foreach ($paragraphs as $p) {
            if (trim($p) != '') { $section->addText(trim($p), [], ['alignment' => Jc::BOTH, 'spaceAfter' => 120]); }
        }

        if (!empty($data['output'])) {
            $section->addText("Hasil / Keputusan Sidang/Rapat:", ['bold' => true], ['spaceBefore' => 60]);
            $section->addText($data['output'], [], ['alignment' => Jc::BOTH, 'spaceAfter' => 150]);
        }
        
        $this->addTtdNotulen($section, $data['tgl_saja'], $authorUser, $req);

        // Lampiran Dokumentasi Notulensi Rapat (Grid 2 Kolom)
        $validFotos = [];
        foreach ($fotos as $path) {
            if (!empty($path) && file_exists($path) && @getimagesize($path)) {
                $validFotos[] = $path;
            }
        }

        if (count($validFotos) > 0) {
            $section->addPageBreak();
            $section->addText(
                "DOKUMENTASI NOTULENSI RAPAT",
                ['bold' => true, 'size' => 11],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 150]
            );

            $phpWord->addTableStyle('DokNotulenGridStyle', [
                'borderSize' => 0,
                'cellMarginTop' => 70,
                'cellMarginBottom' => 70,
                'cellMarginLeft' => 70,
                'cellMarginRight' => 70,
            ]);
            $tblFoto = $section->addTable('DokNotulenGridStyle');

            $cellBorder = [
                'borderSize' => 6,
                'borderColor' => 'CBD5E1',
                'cellMarginTop' => 70,
                'cellMarginBottom' => 70,
                'cellMarginLeft' => 70,
                'cellMarginRight' => 70,
                'valign' => 'top',
            ];

            $chunks = array_chunk($validFotos, 2);
            foreach ($chunks as $cIdx => $pair) {
                $fKiri = $pair[0];
                $fKanan = $pair[1] ?? null;

                $tblFoto->addRow(null, ['cantSplit' => true]);

                $cLeft = $tblFoto->addCell(4650, $cellBorder);
                $cLeft->addImage($fKiri, [
                    'width' => 220,
                    'height' => 150,
                    'alignment' => Jc::CENTER,
                ]);

                $tblFoto->addCell(200, ['borderSize' => 0]);

                $cRight = $tblFoto->addCell(4650, $fKanan ? $cellBorder : ['borderSize' => 0]);
                if ($fKanan) {
                    $cRight->addImage($fKanan, [
                        'width' => 220,
                        'height' => 150,
                        'alignment' => Jc::CENTER,
                    ]);
                }

                if ($cIdx < count($chunks) - 1) {
                    $tblFoto->addRow(120);
                    $tblFoto->addCell(4650, ['borderSize' => 0]);
                    $tblFoto->addCell(200, ['borderSize' => 0]);
                    $tblFoto->addCell(4650, ['borderSize' => 0]);
                }
            }
        }
    }

    private function buildWordPerjalananDinas($section, $phpWord, $data, $teks_ai, $logoPath, $fotos, $req) 
    {
        $phpWord->setDefaultFontSize(11);
        
        if ($req->pakai_kop === 'ya' && !empty($logoPath)) { 
            $this->addKopSurat($section, $logoPath); 
        }
        $section->addText("LAPORAN HASIL PERJALANAN", ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);

        $phpWord->addTableStyle('SppdStyle', ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 40]);
        $table = $section->addTable('SppdStyle');

        $table->addRow();
        $table->addCell(500, ['valign' => 'top'])->addText("1.");
        $table->addCell(2500, ['valign' => 'top'])->addText("Nama Petugas Berpergian");
        $table->addCell(200, ['valign' => 'top'])->addText("");
        $table->addCell(6300, ['valign' => 'top'])->addText("");

        foreach ($data['petugas'] as $p) {
            $nipBersih = trim($p['nip'] ?? '');
            $statusPeg = $p['status_pegawai'] ?? null;
            if (empty($statusPeg) && !empty($p['nama'])) {
                $matchedUser = User::where('name', $p['nama'])->first();
                if ($matchedUser) {
                    $statusPeg = $matchedUser->status_pegawai;
                }
            }
            $isTenagaAhli = ($statusPeg === 'Tenaga Ahli') || empty($nipBersih) || $nipBersih === '-';

            $table->addRow();
            $table->addCell(500)->addText("");
            $table->addCell(2500)->addText($isTenagaAhli ? "      - Nama" : "      - Nama / NIP");
            $table->addCell(200)->addText(":");
            $petugasTeks = $p['nama'] ?? '';
            if (!$isTenagaAhli && !empty($nipBersih) && $nipBersih !== '-') {
                $petugasTeks .= " / " . $nipBersih;
            }
            $table->addCell(6300)->addText($petugasTeks);
        }

        $fields = [
            ["2.", "Tujuan Perjalanan", $data['tujuan'] ?? '-'],
            ["3.", "Maksud Perjalanan", $data['maksud'] ?? '-'],
            ["4.", "Lama Perjalanan", $data['lama'] ?? '-'],
            ["5.", "Waktu Perjalanan", "Tanggal " . $data['tgl_saja']],
        ];

        foreach ($fields as $f) {
            $table->addRow();
            $table->addCell(500, ['valign' => 'top'])->addText($f[0]);
            $table->addCell(2500, ['valign' => 'top'])->addText($f[1]);
            $table->addCell(200, ['valign' => 'top'])->addText(":");
            $table->addCell(6300, ['valign' => 'top'])->addText($f[2]);
        }

        $table->addRow();
        $table->addCell(500, ['valign' => 'top'])->addText("6.");
        $table->addCell(2500, ['valign' => 'top'])->addText("Hasil Perjalanan");
        $table->addCell(200, ['valign' => 'top'])->addText(":");
        $cellHasil = $table->addCell(6300, ['valign' => 'top']);
        
        $paragraphs = explode("\n", $teks_ai);
        foreach ($paragraphs as $p) {
            if (trim($p) != '') { 
                $cellHasil->addText(trim($p), [], ['alignment' => Jc::BOTH, 'spaceAfter' => 80]); 
            }
        }

        if (!empty($data['output'])) {
            $cellHasil->addText("Capaian / Hasil: " . $data['output'], ['bold' => true], ['alignment' => Jc::BOTH, 'spaceAfter' => 120]);
        }

        $section->addPageBreak();
        $this->addBlokTtdWord($section, $data['tgl_saja'], $req->user() ?? auth()->user(), $req, 'Yang Menyusun,', $data['petugas'] ?? []);

        $validFotos = [];
        foreach ($fotos as $path) {
            if (!empty($path) && file_exists($path) && @getimagesize($path)) {
                $validFotos[] = $path;
            }
        }

        if (count($validFotos) > 0) {
            $section->addPageBreak(); 
            
            $section->addText(
                strtoupper($data['judul_dokumentasi'] ?? 'DOKUMENTASI HASIL PERJALANAN DINAS'),
                ['bold' => true, 'size' => 11],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 150]
            );

            $phpWord->addTableStyle('DokSppdGridStyle', [
                'borderSize' => 0,
                'cellMarginTop' => 70,
                'cellMarginBottom' => 70,
                'cellMarginLeft' => 70,
                'cellMarginRight' => 70,
            ]);
            $tblFoto = $section->addTable('DokSppdGridStyle');

            $cellBorder = [
                'borderSize' => 6,
                'borderColor' => 'CBD5E1',
                'cellMarginTop' => 70,
                'cellMarginBottom' => 70,
                'cellMarginLeft' => 70,
                'cellMarginRight' => 70,
                'valign' => 'top',
            ];

            $chunks = array_chunk($validFotos, 2);
            foreach ($chunks as $cIdx => $pair) {
                $fKiri = $pair[0];
                $fKanan = $pair[1] ?? null;

                $tblFoto->addRow(null, ['cantSplit' => true]);

                $cLeft = $tblFoto->addCell(4650, $cellBorder);
                $cLeft->addImage($fKiri, [
                    'width' => 220,
                    'height' => 150,
                    'alignment' => Jc::CENTER,
                ]);

                $tblFoto->addCell(200, ['borderSize' => 0]);

                $cRight = $tblFoto->addCell(4650, $fKanan ? $cellBorder : ['borderSize' => 0]);
                if ($fKanan) {
                    $cRight->addImage($fKanan, [
                        'width' => 220,
                        'height' => 150,
                        'alignment' => Jc::CENTER,
                    ]);
                }

                if ($cIdx < count($chunks) - 1) {
                    $tblFoto->addRow(120);
                    $tblFoto->addCell(4650, ['borderSize' => 0]);
                    $tblFoto->addCell(200, ['borderSize' => 0]);
                    $tblFoto->addCell(4650, ['borderSize' => 0]);
                }
            }
        }
    }

    private function addKopSurat($section, $logoPath) 
    {
        $tableKop = $section->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $tableKop->addRow();
        
        $cell1 = $tableKop->addCell(1500, ['valign' => 'center']);
        if (!empty($logoPath) && file_exists($logoPath) && @getimagesize($logoPath)) {
            $cell1->addImage($logoPath, ['width' => 75, 'alignment' => Jc::CENTER]);
        }
        
        $cell2 = $tableKop->addCell(8000, ['valign' => 'center']);
        $cell2->addText("PEMERINTAH KABUPATEN OGAN KOMERING ILIR", ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $cell2->addText("DINAS KOMUNIKASI DAN INFORMATIKA", ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $cell2->addText("Jalan Letjen Yusuf Singedekane Nomor 01, Jua - jua, Kayuagung (30616)", ['size' => 8.5], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $cell2->addText("Laman: web.kaboki.go.id, Pos-el: diskominfooki2@gmail.com", ['size' => 8.5], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        $styleGarisGanda = [
            'borderBottomStyle' => 'double',
            'borderBottomSize' => 24, 
            'borderBottomColor' => '000000',
            'cellMarginTop' => 0,
            'cellMarginBottom' => 0,
        ];
        
        $tableGaris = $section->addTable($styleGarisGanda);
        $tableGaris->addRow(100, ['exactHeight' => true]); 
        $tableGaris->addCell(9500)->addText("", [], ['spaceAfter' => 0, 'spaceBefore' => 0]); 

        $section->addTextBreak(1, ['size' => 2]); 
    }

    /**
     * Render Blok Tanda Tangan Terpadu untuk Seluruh Dokumen Word (.docx)
     * 100% Mengikuti Pilihan Dropdown Penandatangan Pimpinan:
     * - ya: Tabel tanpa border 2 kolom (50% Kiri: Mengetahui Pimpinan, 50% Kanan: Yang Menyusun)
     * - tidak: 1 blok sebelah kanan (Yang Menyusun)
     */
    private function addBlokTtdWord($section, string $tglTtd, $authorUser, $req, string $labelPenyusun = 'Yang Menyusun,', array $petugasList = [])
    {
        $pakaiTtd = is_object($req) ? ($req->pakai_ttd ?? 'tidak') : ($req['pakai_ttd'] ?? 'tidak');

        // Data Pimpinan
        $jabatanPimpinan = strtoupper(trim(is_object($req) ? ($req->ttd_jabatan ?? 'KEPALA BIDANG') : ($req['ttd_jabatan'] ?? 'KEPALA BIDANG')));
        if (empty($jabatanPimpinan)) {
            $jabatanPimpinan = 'KEPALA BIDANG';
        }
        $namaPimpinan = trim(is_object($req) ? ($req->ttd_nama ?? '') : ($req['ttd_nama'] ?? ''));
        $nipPimpinan = trim(is_object($req) ? ($req->ttd_nip ?? '') : ($req['ttd_nip'] ?? ''));

        // Jika user memilih 'ya' tapi nama pimpinan kosong, fallback ke Kepala Bidang bidang terkait
        if ($pakaiTtd === 'ya' && empty($namaPimpinan)) {
            $bidang = is_object($req) ? ($req->bidang ?? ($authorUser->bidang ?? null)) : ($req['bidang'] ?? ($authorUser->bidang ?? null));
            $kabid = self::findKepalaBidang($bidang);
            if ($kabid) {
                $namaPimpinan = $kabid->name;
                $nipPimpinan = trim($kabid->nip ?? '');
                $jabatanPimpinan = strtoupper(trim($kabid->jabatan ?? ('KEPALA BIDANG ' . $bidang)));
            } else {
                $namaPimpinan = '..........................................';
            }
        }

        // Data Penyusun Tunggal
        $namaPenyusun = $authorUser->name ?? '-';
        $nipPenyusun = trim($authorUser->nip ?? '');
        $statusPenyusun = $authorUser->status_pegawai ?? 'ASN';
        $isPenyusunTA = ($statusPenyusun === 'Tenaga Ahli')
            || empty($nipPenyusun)
            || $nipPenyusun === '-'
            || str_starts_with(strtoupper($nipPenyusun), 'TA-')
            || !preg_match('/^[0-9\.\s]+$/', $nipPenyusun);

        $section->addTextBreak(1);
        $tblTtd = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0,
        ]);
        $tblTtd->addRow();

        $hasMultiplePetugas = count($petugasList) > 1;

        if ($pakaiTtd === 'ya') {
            // ================= 2 KOLOM (50% KIRI, 50% KANAN) =================
            // Kolom Kiri: Mengetahui
            $cellKiri = $tblTtd->addCell(4750, ['valign' => 'top']);
            $cellKiri->addText("", ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 20]); // Baris 1: Kosong sejajar tanggal kanan
            $cellKiri->addText("Mengetahui,", ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]); // Baris 2
            $cellKiri->addText($jabatanPimpinan, ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]); // Baris 3
            $cellKiri->addTextBreak(3); // Ruang kosong TTD (3-4 enter)
            $cellKiri->addText($namaPimpinan, ['bold' => true, 'underline' => 'single', 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 20]);
            if (!empty($nipPimpinan) && $nipPimpinan !== '-') {
                $cellKiri->addText("NIP. " . $nipPimpinan, ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            }

            // Kolom Kanan: Yang Menyusun
            $cellKanan = $tblTtd->addCell(4750, ['valign' => 'top']);
            $cellKanan->addText("Kayuagung, " . $tglTtd, ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 20]); // Baris 1
            $cellKanan->addText($labelPenyusun, ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]); // Baris 2
            $cellKanan->addText("", ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]); // Baris 3 kosong

            if ($hasMultiplePetugas) {
                $cellKanan->addTextBreak(1);
                foreach ($petugasList as $pIdx => $p) {
                    $pNama = $p['nama'] ?? '-';
                    $pNip = trim($p['nip'] ?? '');
                    $pStat = $p['status_pegawai'] ?? null;
                    if (empty($pStat)) {
                        $mUser = User::where('name', $pNama)->first();
                        if ($mUser) $pStat = $mUser->status_pegawai;
                    }
                    $pIsTA = ($pStat === 'Tenaga Ahli') || empty($pNip) || $pNip === '-' || str_starts_with(strtoupper($pNip), 'TA-') || !preg_match('/^[0-9\.\s]+$/', $pNip);

                    $cellKanan->addText(($pIdx + 1) . ". " . $pNama, ['bold' => true, 'underline' => 'single', 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
                    if (!$pIsTA && !empty($pNip)) {
                        $cellKanan->addText("NIP. " . $pNip, ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
                    } else {
                        $cellKanan->addTextBreak(1);
                    }
                }
            } else {
                if (count($petugasList) === 1) {
                    $namaPenyusun = $petugasList[0]['nama'] ?? $namaPenyusun;
                    $nipPenyusun = trim($petugasList[0]['nip'] ?? $nipPenyusun);
                    $statusPenyusun = $petugasList[0]['status_pegawai'] ?? $statusPenyusun;
                    $isPenyusunTA = ($statusPenyusun === 'Tenaga Ahli') || empty($nipPenyusun) || $nipPenyusun === '-' || str_starts_with(strtoupper($nipPenyusun), 'TA-') || !preg_match('/^[0-9\.\s]+$/', $nipPenyusun);
                }
                $cellKanan->addTextBreak(3); // Ruang kosong TTD (3-4 enter)
                $cellKanan->addText($namaPenyusun, ['bold' => true, 'underline' => 'single', 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 20]);
                if (!$isPenyusunTA && !empty($nipPenyusun)) {
                    $cellKanan->addText("NIP. " . $nipPenyusun, ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
                }
            }
        } else {
            // ================= 1 BLOK KANAN =================
            $tblTtd->addCell(5000); // Spacer kiri
            $cellKanan = $tblTtd->addCell(4500, ['valign' => 'top']);
            $cellKanan->addText("Kayuagung, " . $tglTtd, ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 20]);
            $cellKanan->addText($labelPenyusun, ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

            if ($hasMultiplePetugas) {
                $cellKanan->addTextBreak(1);
                foreach ($petugasList as $pIdx => $p) {
                    $pNama = $p['nama'] ?? '-';
                    $pNip = trim($p['nip'] ?? '');
                    $pStat = $p['status_pegawai'] ?? null;
                    if (empty($pStat)) {
                        $mUser = User::where('name', $pNama)->first();
                        if ($mUser) $pStat = $mUser->status_pegawai;
                    }
                    $pIsTA = ($pStat === 'Tenaga Ahli') || empty($pNip) || $pNip === '-' || str_starts_with(strtoupper($pNip), 'TA-') || !preg_match('/^[0-9\.\s]+$/', $pNip);

                    $cellKanan->addText(($pIdx + 1) . ". " . $pNama, ['bold' => true, 'underline' => 'single', 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
                    if (!$pIsTA && !empty($pNip)) {
                        $cellKanan->addText("NIP. " . $pNip, ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
                    } else {
                        $cellKanan->addTextBreak(1);
                    }
                }
            } else {
                if (count($petugasList) === 1) {
                    $namaPenyusun = $petugasList[0]['nama'] ?? $namaPenyusun;
                    $nipPenyusun = trim($petugasList[0]['nip'] ?? $nipPenyusun);
                    $statusPenyusun = $petugasList[0]['status_pegawai'] ?? $statusPenyusun;
                    $isPenyusunTA = ($statusPenyusun === 'Tenaga Ahli') || empty($nipPenyusun) || $nipPenyusun === '-' || str_starts_with(strtoupper($nipPenyusun), 'TA-') || !preg_match('/^[0-9\.\s]+$/', $nipPenyusun);
                }
                $cellKanan->addTextBreak(3);
                $cellKanan->addText($namaPenyusun, ['bold' => true, 'underline' => 'single', 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 20]);
                if (!$isPenyusunTA && !empty($nipPenyusun)) {
                    $cellKanan->addText("NIP. " . $nipPenyusun, ['size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
                }
            }
        }
    }

    private function formatTanggal($tgl) 
    {
        $timestamp = strtotime($tgl ?? date('Y-m-d'));
        $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        
        $hariTeks = $hari[date('w', $timestamp)];
        $tglAngka = date('j', $timestamp);
        $bulanTeks = $bulan[date('n', $timestamp)];
        $tahunTeks = date('Y', $timestamp);

        return [
            'format_lengkap' => "{$hariTeks}, {$tglAngka} {$bulanTeks} {$tahunTeks}",
            'tgl_saja' => "{$tglAngka} {$bulanTeks} {$tahunTeks}",
            'hari' => $hariTeks
        ];
    }

    public function submitToKabid(Request $request, $id) 
    {
        $report = Report::findOrFail($id);
        $user = auth()->user();

        if ($user->role !== 'super_admin') {
            if ($user->role === 'pegawai' && $report->user_id !== $user->id) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
                }
                return redirect()->back()->with('error', 'Akses ditolak.');
            }
            if (in_array($user->role, ['admin_bidang', 'admin'])) {
                if ($report->bidang !== $user->bidang || $report->user_id !== $user->id) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
                    }
                    return redirect()->back()->with('error', 'Akses ditolak.');
                }
            }
        }

        if ($report->jenis_dokumen === 'Laporan Harian') {
            $report->update([
                'status' => 'approved',
                'approved_by' => $report->user_id,
                'approved_at' => now(),
                'catatan_revisi' => null,
            ]);
            $msg = 'Laporan Harian berhasil disimpan dan siap diunduh untuk e-Kinerja!';
            $redirect = ($user->role === 'pegawai') ? route('report.history') : route('admin.laporan.history');
        } elseif (in_array($user->role, ['admin_bidang', 'admin', 'super_admin'])) {
            $report->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'catatan_revisi' => null,
            ]);
            $msg = 'Laporan berhasil difinalisasi dan langsung disetujui!';
            $redirect = route('admin.laporan.history');
        } else {
            $report->update([
                'status' => 'pending',
                'catatan_revisi' => null,
            ]);
            $msg = 'Laporan berhasil diajukan ke Admin Bidang untuk diperiksa!';
            $redirect = ($user->role === 'pegawai') ? route('report.history') : route('admin.laporan.history');
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'redirect_url' => $redirect,
            ]);
        }

        return redirect($redirect)->with('success', $msg);
    }

    public function downloadById($id)
    {
        $report = Report::findOrFail($id);
        $user = auth()->user();

        if ($user->role !== 'super_admin') {
            if ($user->role === 'pegawai') {
                if ($report->user_id !== $user->id) {
                    return redirect()->back()->with('error', 'Akses ditolak.');
                }
                if ($report->status !== 'approved' && $report->jenis_dokumen !== 'Laporan Harian') {
                    return redirect()->back()->with('error', 'Dokumen belum disetujui Admin Bidang. Menu unduh hanya aktif setelah laporan disetujui.');
                }
            }
            if (in_array($user->role, ['admin_bidang', 'admin']) && $report->bidang !== $user->bidang) {
                return redirect()->back()->with('error', 'Akses ditolak.');
            }
        }

        $filePath = storage_path('app/public/reports/' . $report->html_hasil);
        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File fisik dokumen tidak ditemukan di server.');
        }

        return response()->download($filePath, $report->html_hasil);
    }

    public function destroy($id) 
    {
        $report = Report::findOrFail($id);
        $user = auth()->user();
        $isOwnHarian = ($report->jenis_dokumen === 'Laporan Harian' && $report->user_id === $user->id);
        
        if ($user->role !== 'super_admin') {
            if ($user->role === 'pegawai') {
                if ($report->user_id !== $user->id || (!$isOwnHarian && !in_array($report->status, ['draft', 'rejected']))) {
                    return redirect()->back()->with('error', 'Akses ditolak. Anda hanya bisa menghapus laporan draf atau yang ditolak.');
                }
            } elseif (in_array($user->role, ['admin_bidang', 'admin'])) {
                if ($report->bidang !== $user->bidang) {
                    return redirect()->back()->with('error', 'Akses ditolak.');
                }
            }
        }

        if ($report->html_hasil) {
            $filePath = storage_path('app/public/reports/' . $report->html_hasil);
            if (file_exists($filePath)) { 
                @unlink($filePath); 
            }
        }

        $formData = $report->form_data ?? [];
        foreach ($formData['kegiatan'] ?? [] as $keg) {
            for ($f = 1; $f <= 4; $f++) {
                if (!empty($keg["foto_{$f}_path"]) && file_exists($keg["foto_{$f}_path"])) @unlink($keg["foto_{$f}_path"]);
            }
        }
        foreach ($formData['mingguan'] ?? [] as $hari) {
            foreach ($hari['kegiatan'] ?? [] as $keg) {
                for ($f = 1; $f <= 4; $f++) {
                    if (!empty($keg["foto_{$f}_path"]) && file_exists($keg["foto_{$f}_path"])) @unlink($keg["foto_{$f}_path"]);
                }
            }
        }
        for ($f = 1; $f <= 2; $f++) {
            if (!empty($formData["sppd_foto_{$f}_path"]) && file_exists($formData["sppd_foto_{$f}_path"])) @unlink($formData["sppd_foto_{$f}_path"]);
            if (!empty($formData["notulen_foto_{$f}_path"]) && file_exists($formData["notulen_foto_{$f}_path"])) @unlink($formData["notulen_foto_{$f}_path"]);
        }
        if (!empty($formData['logo_kop_path']) && file_exists($formData['logo_kop_path'])) {
            @unlink($formData['logo_kop_path']);
        }

        $report->delete();
        
        return redirect()->back()->with('success', 'Arsip dan seluruh file fisik dokumen (.docx) berhasil dihapus.');
    }

    private function getNamaLengkapBidang($bidangShort)
    {
        $map = [
            'Sekretariat' => 'SEKRETARIAT',
            'Statistik' => 'BIDANG PENGELOLAAN DATA INFORMASI PUBLIK DAN STATISTIK',
            'PKP' => 'BIDANG PENGELOLAAN KOMUNIKASI PUBLIK',
            'TIK' => 'BIDANG INFRASTRUKTUR TEKNOLOGI INFORMASI DAN KOMUNIKASI (TIK)',
            'E-Gov' => 'BIDANG LAYANAN E-GOVERNMENT',
            'Persandian' => 'BIDANG KEAMANAN INFORMASI DAN PERSANDIAN',
        ];
        return $map[$bidangShort] ?? ('BIDANG ' . strtoupper($bidangShort ?? ''));
    }

    private function getNamaBidangTitleCase($bidangShort)
    {
        $map = [
            'Sekretariat' => 'Sekretariat',
            'Statistik' => 'Bidang Pengelolaan Data Informasi Publik dan Statistik',
            'PKP' => 'Bidang Pengelolaan Komunikasi Publik',
            'TIK' => 'Bidang Infrastruktur Teknologi Informasi dan Komunikasi (TIK)',
            'E-Gov' => 'Bidang Layanan E-Government',
            'Persandian' => 'Bidang Keamanan Informasi dan Persandian',
        ];
        return $map[$bidangShort] ?? ('Bidang ' . ($bidangShort ?? ''));
    }
}
