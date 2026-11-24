@extends('layouts.admin')

@section('page_title', 'Pusat Kendali Admin')

@section('content')
<div class="space-y-6">
    
    <!-- Title Section -->
    <div class="mb-6">
        <h2 class="text-xl md:text-2xl font-black text-primary">Ringkasan Sistem Terpadu</h2>
        <p class="text-[11px] md:text-xs text-secondary mt-1">
            @if(Auth::user()->role === 'super_admin')
                Pantau total aktivitas pegawai dan pembuatan dokumen dari seluruh bidang di Diskominfo OKI.
            @else
                Pantau total aktivitas pembuatan dokumen khusus pada lingkup {{ Auth::user()->bidang ?? 'bidang Anda' }}.
            @endif
        </p>
    </div>

    <!-- GRID KARTU STATISTIK (Responsive: 2 di HP, 3 di Tablet, 5 di Laptop) -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 md:gap-4">
        
        <div class="glass-card rounded-2xl p-4 md:p-5 flex items-center justify-between transition-transform hover:-translate-y-1">
            <div>
                <p class="text-[8px] md:text-[9px] font-black text-secondary uppercase tracking-widest leading-tight">Total<br>Pegawai</p>
                <h3 class="text-2xl md:text-3xl font-black text-primary mt-1">{{ $total_pegawai }}</h3>
            </div>
            <div class="w-8 h-8 md:w-10 md:h-10 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center border border-indigo-500/20 shrink-0">
                <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-4 md:p-5 flex items-center justify-between transition-transform hover:-translate-y-1">
            <div>
                <p class="text-[8px] md:text-[9px] font-black text-secondary uppercase tracking-widest leading-tight">Lap.<br>Harian</p>
                <h3 class="text-2xl md:text-3xl font-black text-primary mt-1">{{ $total_harian }}</h3>
            </div>
            <div class="w-8 h-8 md:w-10 md:h-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center border border-emerald-500/20 shrink-0">
                <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-4 md:p-5 flex items-center justify-between transition-transform hover:-translate-y-1">
            <div>
                <p class="text-[8px] md:text-[9px] font-black text-secondary uppercase tracking-widest leading-tight">Lap.<br>Mingguan</p>
                <h3 class="text-2xl md:text-3xl font-black text-primary mt-1">{{ $total_mingguan }}</h3>
            </div>
            <div class="w-8 h-8 md:w-10 md:h-10 rounded-xl bg-purple-500/10 text-purple-500 flex items-center justify-center border border-purple-500/20 shrink-0">
                <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-4 md:p-5 flex items-center justify-between transition-transform hover:-translate-y-1">
            <div>
                <p class="text-[8px] md:text-[9px] font-black text-secondary uppercase tracking-widest leading-tight">Doc<br>Notulensi</p>
                <h3 class="text-2xl md:text-3xl font-black text-primary mt-1">{{ $total_notulen }}</h3>
            </div>
            <div class="w-8 h-8 md:w-10 md:h-10 rounded-xl bg-orange-500/10 text-orange-500 flex items-center justify-center border border-orange-500/20 shrink-0">
                <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-4 md:p-5 flex items-center justify-between transition-transform hover:-translate-y-1 col-span-2 md:col-span-1">
            <div>
                <p class="text-[8px] md:text-[9px] font-black text-secondary uppercase tracking-widest leading-tight">Doc<br>LHPD</p>
                <h3 class="text-2xl md:text-3xl font-black text-primary mt-1">{{ $total_lpd }}</h3>
            </div>
            <div class="w-8 h-8 md:w-10 md:h-10 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center border border-blue-500/20 shrink-0">
                <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </div>

    <!-- TABEL AKTIVITAS TERBARU -->
    <div class="glass-card rounded-3xl overflow-hidden shadow-sm">
        
        <!-- Header Tabel & Filter -->
        <div class="p-4 md:p-5 border-b border-divider flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-slate-500/5">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h3 class="text-sm md:text-base font-black text-primary">Aktivitas Dokumen Terbaru</h3>
            </div>

            <form action="{{ route('admin.dashboard') }}" method="GET" class="w-full sm:w-auto">
                <select name="sort" onchange="this.form.submit()" class="input-field w-full sm:w-48 px-3 py-2 rounded-lg outline-none text-[10px] md:text-[11px] font-bold transition appearance-none cursor-pointer">
                    <option value="created_at_desc" {{ (isset($sortOption) && $sortOption === 'created_at_desc') ? 'selected' : '' }} class="text-slate-800">↓ Terbaru Dibuat</option>
                    <option value="updated_at_desc" {{ (isset($sortOption) && $sortOption === 'updated_at_desc') ? 'selected' : '' }} class="text-slate-800">↻ Terakhir Diedit</option>
                </select>
            </form>
        </div>
        
        <!-- Area Tabel -->
        <div class="overflow-x-auto w-full">
            <table class="w-full text-left min-w-[700px]">
                <thead>
                    <tr class="text-secondary text-[9px] md:text-[10px] font-black uppercase tracking-wider table-border">
                        <th class="p-3 md:p-4 pl-4 md:pl-6 w-48">Pembuat</th>
                        <th class="p-3 md:p-4 w-32">Bidang</th>
                        <th class="p-3 md:p-4 text-center w-28 md:w-32">Jenis</th>
                        <th class="p-3 md:p-4">Judul Dokumen</th>
                        <th class="p-3 md:p-4 text-center w-20 md:w-24">Status</th>
                        <th class="p-3 md:p-4 pr-4 md:pr-6 text-center w-24 md:w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-[11px] md:text-xs">
                    @forelse($reports_terbaru ?? [] as $report)
                    <tr class="table-border hover:bg-slate-500/5 transition">
                        <td class="p-3 md:p-4 pl-4 md:pl-6 font-bold text-primary truncate max-w-[150px]">{{ $report->user->name ?? 'Pegawai' }}</td>
                        
                        <td class="p-3 md:p-4 text-secondary font-medium">{{ $report->bidang ?? '-' }}</td>

                        <td class="p-3 md:p-4 text-center">
                            @if($report->jenis_dokumen === 'Laporan Mingguan')
                                <span class="bg-purple-500/10 text-purple-500 border border-purple-500/20 px-2 py-0.5 md:py-1 rounded font-bold text-[8px] md:text-[9px] uppercase tracking-wider">Mingguan</span>
                            @elseif($report->jenis_dokumen === 'Notulensi Rapat')
                                <span class="bg-orange-500/10 text-orange-500 border border-orange-500/20 px-2 py-0.5 md:py-1 rounded font-bold text-[8px] md:text-[9px] uppercase tracking-wider">Notulensi</span>
                            @elseif($report->jenis_dokumen === 'Laporan Perjalanan Dinas')
                                <span class="bg-blue-500/10 text-blue-500 border border-blue-500/20 px-2 py-0.5 md:py-1 rounded font-bold text-[8px] md:text-[9px] uppercase tracking-wider">LHPD</span>
                            @else
                                <span class="bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 px-2 py-0.5 md:py-1 rounded font-bold text-[8px] md:text-[9px] uppercase tracking-wider">Harian</span>
                            @endif
                        </td>
                        
                        <td class="p-3 md:p-4 font-semibold text-primary leading-snug truncate max-w-[200px]">{{ $report->judul }}</td>
                        
                        <td class="p-3 md:p-4 text-center">
                            @if($report->status === 'final')
                                <span class="bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 px-2 py-1 rounded-md font-bold text-[8px] md:text-[9px] uppercase tracking-wider">Final</span>
                            @else
                                <span class="bg-amber-500/10 text-amber-600 border border-amber-500/20 px-2 py-1 rounded-md font-bold text-[8px] md:text-[9px] uppercase tracking-wider">Draft</span>
                            @endif
                        </td>
                        
                        <td class="p-3 md:p-4 pr-4 md:pr-6 text-center flex justify-center gap-1.5 md:gap-2">
                            @if($report->status === 'approved' || in_array(Auth::user()->role, ['admin_bidang', 'admin', 'super_admin']))
                                <a href="{{ route('report.downloadById', $report->id) }}" title="{{ $report->status === 'approved' ? 'Unduh Word Resmi' : 'Unduh untuk Pemeriksaan' }}" class="inline-flex items-center justify-center bg-blue-500/10 hover:bg-blue-500/20 text-blue-600 border border-blue-500/20 w-7 h-7 md:w-8 md:h-8 rounded-lg font-bold transition">
                                    <svg class="w-3.5 h-3.5 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                </a>
                            @else
                                <button type="button" disabled title="Belum disetujui Admin Bidang" class="inline-flex items-center justify-center bg-slate-500/5 text-slate-400 border border-slate-500/10 w-7 h-7 md:w-8 md:h-8 rounded-lg font-bold cursor-not-allowed opacity-60">
                                    <svg class="w-3.5 h-3.5 md:w-4 md:h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-10 md:p-16 text-center">
                            <div class="flex flex-col items-center justify-center text-secondary">
                                <svg class="w-8 h-8 md:w-10 md:h-10 mb-2 md:mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span class="font-medium text-xs md:text-sm">Belum ada aktivitas dokumen terbaru.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection