@extends('layouts.admin')

@section('page_title', 'Dashboard Pegawai')

@section('content')
<div class="space-y-6">
    
    <!-- Title Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
        <div>
            <h1 class="text-xl md:text-2xl font-black text-primary">
                Selamat Datang, {{ Auth::user()->name }}
            </h1>
            <p class="text-xs text-secondary mt-1 font-medium">
                {{ Auth::user()->jabatan ?? 'Pegawai' }} - {{ Auth::user()->instansi ?? 'Dinas Komunikasi dan Informatika Kabupaten Ogan Komering Ilir' }}
            </p>
        </div>
        <a href="{{ route('report.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2.5 rounded-xl transition shadow-lg shadow-blue-500/20 text-xs shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Buat Laporan Baru
        </a>
    </div>

    <!-- Alert Success / Error -->
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

    <!-- GRID 4 KARTU STATISTIK -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
        
        <div class="glass-card rounded-2xl p-4 md:p-5 flex items-center justify-between transition-transform hover:-translate-y-1">
            <div>
                <p class="text-[9px] md:text-[10px] font-black text-secondary uppercase tracking-widest leading-tight">Draf<br>Tersimpan</p>
                <h3 class="text-2xl md:text-3xl font-black text-amber-500 mt-1">{{ $total_draft ?? 0 }}</h3>
            </div>
            <div class="w-9 h-9 md:w-11 md:h-11 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center border border-amber-500/20 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-4 md:p-5 flex items-center justify-between transition-transform hover:-translate-y-1">
            <div>
                <p class="text-[9px] md:text-[10px] font-black text-secondary uppercase tracking-widest leading-tight">Menunggu<br>ACC Admin Bidang</p>
                <h3 class="text-2xl md:text-3xl font-black text-blue-500 mt-1">{{ $total_pending ?? 0 }}</h3>
            </div>
            <div class="w-9 h-9 md:w-11 md:h-11 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center border border-blue-500/20 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-4 md:p-5 flex items-center justify-between transition-transform hover:-translate-y-1">
            <div>
                <p class="text-[9px] md:text-[10px] font-black text-secondary uppercase tracking-widest leading-tight">Laporan<br>Disetujui</p>
                <h3 class="text-2xl md:text-3xl font-black text-emerald-500 mt-1">{{ $total_approved ?? 0 }}</h3>
            </div>
            <div class="w-9 h-9 md:w-11 md:h-11 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center border border-emerald-500/20 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-4 md:p-5 flex items-center justify-between transition-transform hover:-translate-y-1">
            <div>
                <p class="text-[9px] md:text-[10px] font-black text-secondary uppercase tracking-widest leading-tight">Perlu<br>Revisi</p>
                <h3 class="text-2xl md:text-3xl font-black text-rose-500 mt-1">{{ $total_rejected ?? 0 }}</h3>
            </div>
            <div class="w-9 h-9 md:w-11 md:h-11 rounded-xl bg-rose-500/10 text-rose-500 flex items-center justify-center border border-rose-500/20 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
        </div>

    </div>

    <!-- TABEL LAPORAN SAYA TERBARU -->
    <div class="glass-card rounded-3xl overflow-hidden shadow-sm">
        
        <div class="p-4 md:p-5 border-b border-divider flex justify-between items-center bg-slate-500/5">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h3 class="text-sm md:text-base font-black text-primary">Riwayat Laporan Saya Terbaru</h3>
            </div>
            <a href="{{ route('report.history') }}" class="text-xs font-bold text-blue-600 hover:text-blue-700 transition">
                Lihat Semua &rarr;
            </a>
        </div>

        <div class="overflow-x-auto w-full">
            <table class="w-full text-left min-w-[700px]">
                <thead>
                    <tr class="text-secondary text-[9px] md:text-[10px] font-black uppercase tracking-wider table-border">
                        <th class="p-3 md:p-4 pl-4 md:pl-6 w-36">Tanggal</th>
                        <th class="p-3 md:p-4 w-32 text-center">Jenis</th>
                        <th class="p-3 md:p-4">Judul Dokumen</th>
                        <th class="p-3 md:p-4 text-center w-36">Status</th>
                        <th class="p-3 md:p-4 pr-4 md:pr-6 text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-[11px] md:text-xs">
                    @forelse($reports_terbaru ?? [] as $report)
                    <tr class="table-border hover:bg-slate-500/5 transition">
                        <td class="p-3 md:p-4 pl-4 md:pl-6">
                            <p class="font-bold text-primary">{{ \Carbon\Carbon::parse($report->tanggal)->format('d M Y') }}</p>
                            <p class="text-[10px] text-secondary">{{ \Carbon\Carbon::parse($report->created_at)->diffForHumans() }}</p>
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
                            <p class="font-semibold text-primary leading-snug">{{ $report->judul }}</p>
                            @if($report->status === 'rejected' && $report->catatan_revisi)
                                <div class="mt-1.5 p-2 bg-rose-500/10 border border-rose-500/20 rounded-lg text-rose-600 text-[10px]">
                                    <span class="font-bold">Catatan Revisi Admin Bidang:</span> {{ $report->catatan_revisi }}
                                </div>
                            @endif
                        </td>

                        <td class="p-3 md:p-4 text-center">
                            @if($report->status === 'approved')
                                <span class="bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 px-2.5 py-1 rounded-full font-bold text-[9px] uppercase tracking-wider whitespace-nowrap">✓ Disetujui</span>
                            @elseif($report->status === 'pending')
                                <span class="bg-blue-500/10 text-blue-600 border border-blue-500/20 px-2.5 py-1 rounded-full font-bold text-[9px] uppercase tracking-wider whitespace-nowrap">Menunggu ACC Admin Bidang</span>
                            @elseif($report->status === 'rejected')
                                <span class="bg-rose-500/10 text-rose-600 border border-rose-500/20 px-2.5 py-1 rounded-full font-bold text-[9px] uppercase tracking-wider whitespace-nowrap">Ditolak / Revisi</span>
                            @else
                                <span class="bg-amber-500/10 text-amber-600 border border-amber-500/20 px-2.5 py-1 rounded-full font-bold text-[9px] uppercase tracking-wider whitespace-nowrap">Draft</span>
                            @endif
                        </td>

                        <td class="p-3 md:p-4 pr-4 md:pr-6 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                @if(in_array($report->status, ['draft', 'rejected']))
                                    <a href="{{ route('report.edit', $report->id) }}" class="inline-flex items-center gap-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 border border-amber-500/20 px-2.5 py-1 rounded-lg font-bold transition text-[10px]" title="Edit / Perbaiki">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        Edit
                                    </a>
                                @endif

                                @if($report->status === 'approved')
                                    <a href="{{ route('report.downloadById', $report->id) }}" class="inline-flex items-center gap-1 bg-blue-500/10 hover:bg-blue-500/20 text-blue-600 border border-blue-500/20 px-2.5 py-1 rounded-lg font-bold transition text-[10px]" title="Unduh File Word">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        Word
                                    </a>
                                @else
                                    <button type="button" disabled class="inline-flex items-center gap-1 bg-slate-500/5 text-slate-400 border border-slate-500/10 px-2.5 py-1 rounded-lg font-semibold text-[10px] cursor-not-allowed opacity-60" title="Belum disetujui Admin Bidang">
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                        Terkunci
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-secondary">
                            Belum ada laporan yang dibuat. Klik tombol "Buat Laporan Baru" untuk memulai.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
