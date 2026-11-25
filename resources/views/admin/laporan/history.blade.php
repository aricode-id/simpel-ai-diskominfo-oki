@extends('layouts.admin')

@section('page_title', 'Arsip & Verifikasi Dokumen')

@section('content')
<div class="space-y-6">
    
    <!-- Title Section & Filter -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-2">
        <div>
            <h2 class="text-xl md:text-2xl font-black text-primary">
                @if(in_array(Auth::user()->role, ['admin_bidang', 'admin']))
                    Verifikasi & Arsip Laporan Bidang {{ Auth::user()->bidang }}
                @else
                    Semua Laporan Masuk (Seluruh Bidang)
                @endif
            </h2>
            <p class="text-[11px] md:text-xs text-secondary mt-1">
                @if(in_array(Auth::user()->role, ['admin_bidang', 'admin']))
                    Periksa laporan pending dari staf bidang {{ Auth::user()->bidang }}, setujui dokumen, atau berikan catatan revisi.
                @else
                    Pantau status laporan kegiatan, verifikasi admin bidang, dan arsip dokumen lokal.
                @endif
            </p>
        </div>

        <!-- FORM FILTER -->
        <form action="{{ route('admin.laporan.history') }}" method="GET" class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            @if(Auth::user()->role === 'super_admin')
                <select name="bidang" onchange="this.form.submit()" class="input-field px-3 py-2 rounded-xl text-xs font-bold transition">
                    <option value="Semua" {{ request('bidang') == 'Semua' ? 'selected' : '' }}>-- Semua Bidang --</option>
                    @foreach(['Sekretariat', 'Statistik', 'PKP', 'TIK', 'E-Gov', 'Persandian'] as $b)
                        <option value="{{ $b }}" {{ request('bidang') == $b ? 'selected' : '' }}>{{ $b }}</option>
                    @endforeach
                </select>
            @endif

            <select name="status" onchange="this.form.submit()" class="input-field px-3 py-2 rounded-xl text-xs font-bold transition">
                <option value="Semua" {{ request('status') == 'Semua' ? 'selected' : '' }}>-- Semua Status --</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu ACC (Pending)</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui (Approved)</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak (Rejected)</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draf</option>
            </select>

            <select name="jenis_dokumen" onchange="this.form.submit()" class="input-field px-3 py-2 rounded-xl text-xs font-bold transition">
                <option value="Semua" {{ request('jenis_dokumen') == 'Semua' ? 'selected' : '' }}>-- Semua Jenis --</option>
                <option value="Laporan Harian" {{ request('jenis_dokumen') == 'Laporan Harian' ? 'selected' : '' }}>Harian</option>
                <option value="Laporan Mingguan" {{ request('jenis_dokumen') == 'Laporan Mingguan' ? 'selected' : '' }}>Mingguan</option>
                <option value="Notulensi Rapat" {{ request('jenis_dokumen') == 'Notulensi Rapat' ? 'selected' : '' }}>Notulensi</option>
                <option value="Laporan Perjalanan Dinas" {{ request('jenis_dokumen') == 'Laporan Perjalanan Dinas' ? 'selected' : '' }}>LHPD</option>
            </select>

            @if(request('user_id'))
                <input type="hidden" name="user_id" value="{{ request('user_id') }}">
            @endif

            <select name="sort" onchange="this.form.submit()" class="input-field px-3 py-2 rounded-xl text-xs font-bold transition">
                <option value="created_at_desc" {{ (isset($sortOption) && $sortOption === 'created_at_desc') ? 'selected' : '' }}>↓ Terbaru Dibuat</option>
                <option value="updated_at_desc" {{ (isset($sortOption) && $sortOption === 'updated_at_desc') ? 'selected' : '' }}>↻ Terakhir Diedit</option>
            </select>
        </form>
    </div>

    @if(request('user_id') && ($filterUser = \App\Models\User::find(request('user_id'))))
        <div class="flex items-center justify-between gap-2 bg-blue-500/10 border border-blue-500/20 px-4 py-2.5 rounded-2xl text-xs font-semibold text-blue-600">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span>Menampilkan laporan khusus dari: <strong>{{ $filterUser->name }}</strong> ({{ $filterUser->jabatan ?? $filterUser->role }})</span>
            </div>
            <a href="{{ route('admin.laporan.history') }}" class="underline hover:text-blue-800 text-[11px] font-bold">Reset / Tampilkan Semua</a>
        </div>
    @endif

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 px-4 py-3 rounded-xl text-xs md:text-sm font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-500/10 text-red-600 border border-red-500/20 px-4 py-3 rounded-xl text-xs md:text-sm font-bold flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Card Tabel Riwayat -->
    <div class="glass-card rounded-3xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto w-full">
            <table class="w-full text-left min-w-[850px]">
                <thead>
                    <tr class="text-secondary text-[9px] md:text-[10px] font-black uppercase tracking-wider table-border bg-slate-500/5">
                        <th class="p-3 md:p-4 pl-4 md:pl-6 w-36">Tanggal</th>
                        <th class="p-3 md:p-4 w-28 text-center">Jenis</th>
                        <th class="p-3 md:p-4">Judul & Catatan</th>
                        <th class="p-3 md:p-4 w-40">Pembuat / Bidang</th>
                        <th class="p-3 md:p-4 text-center w-36">Status</th>
                        <th class="p-3 md:p-4 pr-4 md:pr-6 text-center w-52">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-[11px] md:text-xs divide-y divide-divider border-divider">
                    @forelse($reports as $report)
                    <tr class="table-border hover:bg-slate-500/5 transition">
                        <td class="p-3 md:p-4 pl-4 md:pl-6">
                            <p class="font-bold text-primary">{{ \Carbon\Carbon::parse($report->tanggal)->format('d M Y') }}</p>
                            <p class="text-[10px] text-secondary">Update: {{ \Carbon\Carbon::parse($report->updated_at)->diffForHumans() }}</p>
                        </td>

                        <td class="p-3 md:p-4 text-center">
                            @if($report->jenis_dokumen === 'Laporan Mingguan')
                                <span class="bg-purple-500/10 text-purple-600 border border-purple-500/20 px-2 py-0.5 rounded font-bold text-[8px] md:text-[9px] uppercase tracking-wider">Mingguan</span>
                            @elseif($report->jenis_dokumen === 'Notulensi Rapat')
                                <span class="bg-orange-500/10 text-orange-600 border border-orange-500/20 px-2 py-0.5 rounded font-bold text-[8px] md:text-[9px] uppercase tracking-wider">Notulensi</span>
                            @elseif($report->jenis_dokumen === 'Laporan Perjalanan Dinas')
                                <span class="bg-blue-500/10 text-blue-600 border border-blue-500/20 px-2 py-0.5 rounded font-bold text-[8px] md:text-[9px] uppercase tracking-wider">LHPD</span>
                            @else
                                <span class="bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 px-2 py-0.5 rounded font-bold text-[8px] md:text-[9px] uppercase tracking-wider">Harian</span>
                            @endif
                        </td>

                        <td class="p-3 md:p-4">
                            <p class="font-bold text-primary leading-snug">{{ $report->judul }}</p>
                            @if($report->status === 'rejected' && $report->catatan_revisi)
                                <div class="mt-1.5 p-2 bg-rose-500/10 border border-rose-500/20 rounded-lg text-rose-600 text-[10px]">
                                    <span class="font-bold">Revisi Diminta:</span> {{ $report->catatan_revisi }}
                                </div>
                            @endif
                            @if($report->status === 'approved' && $report->approver)
                                <p class="text-[10px] text-emerald-600 mt-0.5">Disetujui oleh: <b>{{ $report->approver->name }}</b></p>
                            @endif
                        </td>

                        <td class="p-3 md:p-4">
                            <p class="font-bold text-primary">{{ $report->user->name ?? 'Pegawai' }}</p>
                            <p class="text-[10px] text-secondary mt-0.5">Bidang {{ $report->bidang ?? '-' }}</p>
                        </td>

                        <td class="p-3 md:p-4 text-center">
                            @if($report->status === 'approved')
                                <span class="bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 px-2.5 py-1 rounded-full font-bold text-[9px] uppercase tracking-wider whitespace-nowrap">✓ Disetujui</span>
                            @elseif($report->status === 'pending')
                                <span class="bg-blue-500/10 text-blue-600 border border-blue-500/20 px-2.5 py-1 rounded-full font-bold text-[9px] uppercase tracking-wider whitespace-nowrap animate-pulse">Menunggu ACC</span>
                            @elseif($report->status === 'rejected')
                                <span class="bg-rose-500/10 text-rose-600 border border-rose-500/20 px-2.5 py-1 rounded-full font-bold text-[9px] uppercase tracking-wider whitespace-nowrap">Ditolak</span>
                            @else
                                <span class="bg-amber-500/10 text-amber-600 border border-amber-500/20 px-2.5 py-1 rounded-full font-bold text-[9px] uppercase tracking-wider whitespace-nowrap">Draft</span>
                            @endif
                        </td>

                        <td class="p-3 md:p-4 pr-4 md:pr-6 text-center">
                            <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                
                                @if($report->status === 'pending')
                                    {{-- STATUS: PENDING (Hanya Pratinjau, Edit Sendiri, & Setujui Kabid) --}}
                                    <!-- 1. Tombol Pratinjau (Ikon Mata) -->
                                    <button type="button" onclick="openDocPreviewModal({{ $report->id }})" class="inline-flex items-center gap-1 bg-blue-500/10 hover:bg-blue-500/20 text-blue-600 border border-blue-500/20 px-2.5 py-1.5 rounded-lg font-bold transition text-[10px]" title="Pratinjau Dokumen">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Pratinjau
                                    </button>

                                    <!-- 2. Tombol Edit (Jika milik user login atau verifikator) -->
                                    @if($report->user_id === Auth::id() || in_array(Auth::user()->role, ['admin_bidang', 'admin', 'super_admin']))
                                        <a href="{{ route('report.edit', $report->id) }}" class="inline-flex items-center gap-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 border border-amber-500/20 px-2.5 py-1.5 rounded-lg font-bold transition text-[10px]" title="Edit Dokumen">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            Edit
                                        </a>
                                    @endif

                                    <!-- 3. Tombol Setujui -->
                                    @if(Auth::user()->role === 'super_admin' || (in_array(Auth::user()->role, ['admin_bidang', 'admin']) && Auth::user()->bidang === $report->bidang))
                                        <form action="{{ route('report.approve', $report->id) }}" method="POST" onsubmit="return confirm('Setujui dokumen laporan ini?');" class="inline">
                                            @csrf
                                            <button
                                                type="submit"
                                                onmouseover="this.style.backgroundColor='#e2e8f0'"
                                                onmouseout="this.style.backgroundColor='#f1f5f9'"
                                                style="background-color: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; padding: 6px 10px; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; font-size: 10px; line-height: 1.2; cursor: pointer; transition: background-color 0.15s ease;"
                                                title="Setujui Laporan"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                Setujui
                                            </button>
                                        </form>
                                    @endif

                                @elseif($report->status === 'approved')
                                    {{-- STATUS: APPROVED (Pratinjau & Download Word) --}}
                                    <!-- 1. Tombol Pratinjau -->
                                    <button type="button" onclick="openDocPreviewModal({{ $report->id }})" class="inline-flex items-center gap-1 bg-blue-500/10 hover:bg-blue-500/20 text-blue-600 border border-blue-500/20 px-2.5 py-1.5 rounded-lg font-bold transition text-[10px]" title="Pratinjau Dokumen">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Pratinjau
                                    </button>

                                    <!-- 2. Tombol Unduh Word -->
                                    <a
                                        href="{{ route('report.downloadById', $report->id) }}"
                                        onmouseover="this.style.backgroundColor='#1d4ed8'"
                                        onmouseout="this.style.backgroundColor='#2563eb'"
                                        style="background-color: #2563eb; color: #ffffff; padding: 6px 10px; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; font-size: 10px; line-height: 1.2; text-decoration: none; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25); transition: background-color 0.15s ease;"
                                        title="Unduh File Word Resmi"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        Word
                                    </a>

                                @elseif($report->status === 'rejected')
                                    {{-- STATUS: REJECTED (Pratinjau & Edit jika Pemilik) --}}
                                    <!-- 1. Tombol Pratinjau -->
                                    <button type="button" onclick="openDocPreviewModal({{ $report->id }})" class="inline-flex items-center gap-1 bg-blue-500/10 hover:bg-blue-500/20 text-blue-600 border border-blue-500/20 px-2.5 py-1.5 rounded-lg font-bold transition text-[10px]" title="Pratinjau Dokumen">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Pratinjau
                                    </button>

                                    <!-- 2. Tombol Edit -->
                                    @if($report->user_id === Auth::id() || in_array(Auth::user()->role, ['admin_bidang', 'admin', 'super_admin']))
                                        <a href="{{ route('report.edit', $report->id) }}" class="inline-flex items-center gap-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 border border-amber-500/20 px-2.5 py-1.5 rounded-lg font-bold transition text-[10px]" title="Edit Dokumen">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            Edit
                                        </a>
                                    @endif

                                @else
                                    {{-- STATUS: DRAFT / LAINNYA --}}
                                    <button type="button" onclick="openDocPreviewModal({{ $report->id }})" class="inline-flex items-center gap-1 bg-blue-500/10 hover:bg-blue-500/20 text-blue-600 border border-blue-500/20 px-2.5 py-1.5 rounded-lg font-bold transition text-[10px]" title="Pratinjau Dokumen">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Pratinjau
                                    </button>

                                    @if($report->user_id === Auth::id() || in_array(Auth::user()->role, ['admin_bidang', 'admin', 'super_admin']))
                                        <a href="{{ route('report.edit', $report->id) }}" class="inline-flex items-center gap-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 border border-amber-500/20 px-2.5 py-1.5 rounded-lg font-bold transition text-[10px]" title="Edit Dokumen">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            Edit
                                        </a>
                                    @endif
                                @endif

                                {{-- Tombol Hapus Tetap Tersedia untuk Super Admin atau Pemilik Dokumen --}}
                                @if(Auth::user()->role === 'super_admin' || $report->user_id === Auth::id() || (in_array(Auth::user()->role, ['admin_bidang', 'admin']) && Auth::user()->bidang === $report->bidang))
                                <form action="{{ route('report.destroy', $report->id) }}" method="POST" onsubmit="return confirm('Hapus dokumen ini secara permanen?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center justify-center p-1.5 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-600 border border-red-500/20 transition text-[10px]" title="Hapus">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-16 text-center text-secondary">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 mb-3 opacity-40 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span class="font-bold text-sm text-primary">Tidak ada dokumen laporan yang sesuai filter</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL POP-UP PRATINJAU DOKUMEN WORD -->
<!-- ========================================== -->
<div id="docPreviewModal" style="position: fixed; inset: 0; z-index: 9990; display: none; align-items: center; justify-content: center; background-color: rgba(2, 6, 23, 0.85); padding: 16px;">
    <div style="width: 100%; max-width: 900px; height: 88vh; max-height: 88vh; display: flex; flex-direction: column; border-radius: 16px; overflow: hidden; background-color: #0f172a; border: 1px solid #334155;">
        
        <!-- Header Modal -->
        <div class="px-6 py-4 border-b border-divider flex items-center justify-between bg-sub-card" style="flex-shrink: 0;">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-600/10 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm md:text-base font-black text-primary flex items-center gap-2">
                        Pratinjau Dokumen Word
                        <span id="previewModalStatusBadge" class="text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-blue-500/10 text-blue-500 border border-blue-500/20">-</span>
                    </h3>
                    <p id="previewModalSubtitle" class="text-[11px] text-secondary truncate max-w-md">Memeriksa kelayakan format dan isi laporan kegiatan</p>
                </div>
            </div>
            
            <button type="button" onclick="closeDocPreviewModal()" class="w-8 h-8 rounded-full bg-slate-500/10 hover:bg-slate-500/20 text-secondary hover:text-primary flex items-center justify-center transition" title="Tutup">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Body Modal (Scrollable Preview Paper) -->
        <div id="previewModalContent" class="h-[55vh] min-h-[420px] overflow-auto" style="flex: 1 1 auto; overflow-y: auto; padding: 24px; background-color: #1e293b;">
            <!-- Konten pratinjau dimasukkan via AJAX -->
        </div>

        <!-- Footer Modal (Aksi Dinamis Berdasarkan Status) -->
        <div class="px-6 py-4 border-t border-divider bg-sub-card flex flex-col sm:flex-row items-center justify-between gap-3" style="flex-shrink: 0;">
            <p id="previewModalFooterNote" class="text-[11px] text-secondary flex items-center gap-1.5 order-2 sm:order-1">
                <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Pratinjau resmi Dinas Komunikasi dan Informatika Kab. OKI
            </p>
            
            <div id="previewModalFooterActions" class="flex items-center gap-3 w-full sm:w-auto justify-end order-1 sm:order-2 flex-wrap">
                <!-- Tombol aksi dimuat secara dinamis via JavaScript -->
            </div>
        </div>

    </div>
</div>

<!-- Form tersembunyi untuk Approve langsung dari modal pratinjau -->
<form id="quickApproveForm" method="POST" action="" style="display: none;">
    @csrf
</form>

<!-- ========================================== -->
<!-- MODAL TOLAK LAPORAN (CATATAN REVISI ADMIN BIDANG) -->
<!-- ========================================== -->
<div id="rejectModal" style="z-index: 9995;" class="fixed inset-0 items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden">
    <div class="glass-card rounded-3xl p-6 sm:p-8 max-w-lg w-full mx-4 shadow-2xl relative border border-divider">
        <div class="flex items-center gap-3 mb-4 text-rose-600">
            <div class="w-10 h-10 rounded-xl bg-rose-500/10 flex items-center justify-center border border-rose-500/20 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div>
                <h3 class="text-base font-black text-primary">Tolak Laporan & Minta Revisi</h3>
                <p id="modalJudulLaporan" class="text-xs text-secondary truncate max-w-xs mt-0.5"></p>
            </div>
        </div>

        <form id="rejectForm" method="POST" action="">
            @csrf
            <div class="mb-5">
                <label class="block text-xs font-bold text-secondary mb-2 uppercase tracking-wide">
                    Catatan Revisi untuk Pegawai <span class="text-rose-500">*</span>
                </label>
                <textarea name="catatan_revisi" rows="4" required class="input-field w-full p-3 rounded-xl text-xs md:text-sm leading-relaxed" placeholder="Tuliskan bagian mana yang perlu diperbaiki oleh pegawai..."></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2.5 rounded-xl border border-divider text-secondary hover:text-primary transition text-xs font-bold">
                    Batal
                </button>
                <button
                    type="submit"
                    onmouseover="this.style.backgroundColor='#be123c'"
                    onmouseout="this.style.backgroundColor='#e11d48'"
                    style="background-color: #e11d48; color: #ffffff; padding: 10px 20px; border-radius: 12px; font-weight: 700; font-size: 12px; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25); transition: background-color 0.15s ease;"
                >
                    Kirim Catatan Revisi & Tolak
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentPreviewReport = null;

    function openDocPreviewModal(reportId) {
        const modal = document.getElementById('docPreviewModal');
        const content = document.getElementById('previewModalContent');
        const statusBadge = document.getElementById('previewModalStatusBadge');
        const subtitle = document.getElementById('previewModalSubtitle');
        const footerActions = document.getElementById('previewModalFooterActions');

        if (!modal || !content) return;

        // Reset dan tampilkan spinner
        content.innerHTML = `
            <div class="flex flex-col items-center justify-center py-24 text-slate-400">
                <svg class="animate-spin h-9 w-9 text-blue-500 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-xs font-bold text-slate-300">Memuat pratinjau dokumen...</p>
            </div>
        `;
        footerActions.innerHTML = '';
        statusBadge.innerText = 'Memuat...';

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        fetch(`/report/preview/${reportId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => {
            if (!res.ok) throw new Error('Gagal memuat pratinjau (Kode: ' + res.status + ')');
            return res.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Gagal memuat pratinjau.');
            }

            currentPreviewReport = data.report;
            content.innerHTML = data.html;
            subtitle.innerText = `${data.report.jenis_dokumen} - ${data.report.judul}`;

            // Update status badge
            if (data.report.status === 'pending') {
                statusBadge.className = 'text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-blue-500/10 text-blue-500 border border-blue-500/20';
                statusBadge.innerText = 'Menunggu ACC';
            } else if (data.report.status === 'approved') {
                statusBadge.className = 'text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/20';
                statusBadge.innerText = 'Disetujui';
            } else if (data.report.status === 'rejected') {
                statusBadge.className = 'text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-rose-500/10 text-rose-500 border border-rose-500/20';
                statusBadge.innerText = 'Ditolak';
            } else {
                statusBadge.className = 'text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-500 border border-amber-500/20';
                statusBadge.innerText = 'Draft';
            }

            renderPreviewFooter(data.report);
        })
        .catch(err => {
            console.error('Error load preview:', err);
            content.innerHTML = `
                <div class="p-8 text-center text-rose-400 bg-rose-500/10 rounded-2xl border border-rose-500/20 max-w-md mx-auto my-12">
                    <p class="font-bold text-sm mb-1">Gagal Memuat Dokumen</p>
                    <p class="text-xs text-rose-300 leading-relaxed">${err.message || 'Terjadi kesalahan sistem.'}</p>
                    <button type="button" onclick="closeDocPreviewModal()" class="mt-4 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold transition">Tutup</button>
                </div>
            `;
            footerActions.innerHTML = `
                <button type="button" onclick="closeDocPreviewModal()" class="px-5 py-2.5 rounded-xl border border-divider text-secondary hover:text-primary text-xs font-bold transition">
                    Tutup
                </button>
            `;
        });
    }

    function closeDocPreviewModal() {
        const modal = document.getElementById('docPreviewModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    function renderPreviewFooter(report) {
        const footerActions = document.getElementById('previewModalFooterActions');
        if (!footerActions) return;

        let buttonsHtml = '';

        if (report.status === 'pending') {
            if (report.can_verify) {
                buttonsHtml += `
                    <button type="button" onclick="rejectFromPreview()" onmouseover="this.style.backgroundColor='#be123c'" onmouseout="this.style.backgroundColor='#e11d48'" style="background-color: #e11d48; color: #ffffff; padding: 10px 18px; border-radius: 12px; font-weight: 700; font-size: 12px; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25); transition: background-color 0.15s ease;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Tolak Laporan
                    </button>
                    <button type="button" onclick="approveFromPreview()" onmouseover="this.style.backgroundColor='#e2e8f0'" onmouseout="this.style.backgroundColor='#f1f5f9'" style="background-color: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; padding: 10px 22px; border-radius: 12px; font-weight: 700; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: background-color 0.15s ease;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Setujui Laporan
                    </button>
                `;
            }
            if (report.can_edit) {
                buttonsHtml += `
                    <a href="${report.edit_url}" onmouseover="this.style.backgroundColor='#d97706'" onmouseout="this.style.backgroundColor='#f59e0b'" style="background-color: #f59e0b; color: #020617; padding: 10px 18px; border-radius: 12px; font-weight: 700; font-size: 12px; text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25); transition: background-color 0.15s ease;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Edit Dokumen
                    </a>
                `;
            }
            if (!report.can_verify && !report.can_edit) {
                buttonsHtml += `
                    <button type="button" onclick="closeDocPreviewModal()" class="px-5 py-2.5 rounded-xl border border-divider text-secondary hover:text-primary text-xs font-bold transition">
                        Tutup
                    </button>
                `;
            }
        } else if (report.status === 'approved') {
            buttonsHtml += `
                <button type="button" onclick="closeDocPreviewModal()" class="px-5 py-2.5 rounded-xl border border-divider text-secondary hover:text-primary text-xs font-bold transition">
                    Tutup
                </button>
                <a href="${report.download_url}" onmouseover="this.style.backgroundColor='#1d4ed8'" onmouseout="this.style.backgroundColor='#2563eb'" style="background-color: #2563eb; color: #ffffff; padding: 10px 20px; border-radius: 12px; font-weight: 700; font-size: 12px; text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); transition: background-color 0.15s ease;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Download Word
                </a>
            `;
        } else {
            // Status Rejected atau Draft
            if (report.can_edit) {
                buttonsHtml += `
                    <a href="${report.edit_url}" onmouseover="this.style.backgroundColor='#d97706'" onmouseout="this.style.backgroundColor='#f59e0b'" style="background-color: #f59e0b; color: #020617; padding: 10px 18px; border-radius: 12px; font-weight: 700; font-size: 12px; text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25); transition: background-color 0.15s ease;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Edit Dokumen
                    </a>
                `;
            }
            buttonsHtml += `
                <button type="button" onclick="closeDocPreviewModal()" class="px-5 py-2.5 rounded-xl border border-divider text-secondary hover:text-primary text-xs font-bold transition">
                    Tutup
                </button>
            `;
        }

        footerActions.innerHTML = buttonsHtml;
    }

    function rejectFromPreview() {
        if (!currentPreviewReport) return;
        const reportId = currentPreviewReport.id;
        const reportJudul = currentPreviewReport.judul;
        closeDocPreviewModal();
        openRejectModal(reportId, reportJudul);
    }

    function approveFromPreview() {
        if (!currentPreviewReport) return;
        if (confirm('Setujui dokumen laporan ini?')) {
            const form = document.getElementById('quickApproveForm');
            form.action = `/report/${currentPreviewReport.id}/approve`;
            form.submit();
        }
    }

    // Modal Tolak (Catatan Revisi)
    function openRejectModal(reportId, reportJudul) {
        const modal = document.getElementById('rejectModal');
        const form = document.getElementById('rejectForm');
        const judulEl = document.getElementById('modalJudulLaporan');

        form.setAttribute('action', `/report/${reportId}/reject`);
        judulEl.innerText = reportJudul;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeRejectModal() {
        const modal = document.getElementById('rejectModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Listener klik di luar modal (backdrop) untuk menutup modal
    document.addEventListener("DOMContentLoaded", function() {
        const previewModal = document.getElementById('docPreviewModal');
        if (previewModal) {
            previewModal.addEventListener('click', function(e) {
                if (e.target === previewModal) {
                    closeDocPreviewModal();
                }
            });
        }
    });
</script>
@endsection