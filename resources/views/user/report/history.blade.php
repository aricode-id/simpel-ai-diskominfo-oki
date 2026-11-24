@extends('layouts.admin')

@section('page_title', 'Riwayat Laporan')

@section('content')
<div class="space-y-6">
    
    <!-- Title & Action Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
        <div>
            <h2 class="text-xl md:text-2xl font-black text-primary">Riwayat Pelaporan Kerja</h2>
            <p class="text-[11px] md:text-xs text-secondary mt-1">
                Daftar seluruh laporan yang telah Anda simpan sebagai draf atau diajukan ke Kepala Bidang.
            </p>
        </div>
        <a href="{{ in_array(Auth::user()->role, ['super_admin', 'admin_bidang', 'admin']) ? route('admin.laporan.create') : route('report.create') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2.5 rounded-xl transition shadow-lg shadow-blue-500/20 text-xs shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Buat Laporan Baru
        </a>
    </div>

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
            <table class="w-full text-left min-w-[750px]">
                <thead>
                    <tr class="text-secondary text-[9px] md:text-[10px] font-black uppercase tracking-wider table-border bg-slate-500/5">
                        <th class="p-3 md:p-4 pl-4 md:pl-6 w-12 text-center">No</th>
                        <th class="p-3 md:p-4 w-36">Tanggal</th>
                        <th class="p-3 md:p-4 w-32 text-center">Jenis Dokumen</th>
                        <th class="p-3 md:p-4">Judul / Kegiatan</th>
                        <th class="p-3 md:p-4 text-center w-40">Status Verifikasi</th>
                        <th class="p-3 md:p-4 pr-4 md:pr-6 text-center w-44">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-[11px] md:text-xs divide-y divide-divider border-divider">
                    @forelse($reports as $index => $report)
                    <tr class="table-border hover:bg-slate-500/5 transition">
                        <td class="p-3 md:p-4 pl-4 md:pl-6 text-center font-bold text-secondary">
                            {{ $index + 1 }}
                        </td>

                        <td class="p-3 md:p-4">
                            <p class="font-bold text-primary">{{ \Carbon\Carbon::parse($report->tanggal)->format('d M Y') }}</p>
                            <p class="text-[10px] text-secondary">{{ \Carbon\Carbon::parse($report->created_at)->diffForHumans() }}</p>
                        </td>

                        <td class="p-3 md:p-4 text-center">
                            @if($report->jenis_dokumen === 'Laporan Mingguan')
                                <span class="bg-purple-500/10 text-purple-600 border border-purple-500/20 px-2.5 py-1 rounded-lg font-bold text-[9px] uppercase tracking-wider">Mingguan</span>
                            @elseif($report->jenis_dokumen === 'Notulensi Rapat')
                                <span class="bg-orange-500/10 text-orange-600 border border-orange-500/20 px-2.5 py-1 rounded-lg font-bold text-[9px] uppercase tracking-wider">Notulensi</span>
                            @elseif($report->jenis_dokumen === 'Laporan Perjalanan Dinas')
                                <span class="bg-blue-500/10 text-blue-600 border border-blue-500/20 px-2.5 py-1 rounded-lg font-bold text-[9px] uppercase tracking-wider">LHPD</span>
                            @else
                                <span class="bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 px-2.5 py-1 rounded-lg font-bold text-[9px] uppercase tracking-wider">Harian</span>
                            @endif
                        </td>

                        <td class="p-3 md:p-4">
                            <p class="font-bold text-primary text-sm leading-snug">{{ $report->judul }}</p>
                            <p class="text-[10px] text-secondary mt-0.5">{{ $report->bidang ?? 'Diskominfo OKI' }}</p>
                            
                            {{-- Kotak Peringatan Catatan Revisi jika ditolak --}}
                            @if($report->status === 'rejected' && $report->catatan_revisi)
                                <div class="mt-2.5 p-3 bg-rose-500/10 border border-rose-500/20 rounded-xl text-rose-600 text-xs shadow-sm">
                                    <div class="flex items-center gap-1.5 font-bold mb-1">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        Catatan Revisi dari Admin Bidang:
                                    </div>
                                    <p class="leading-relaxed whitespace-pre-line">{{ $report->catatan_revisi }}</p>
                                    <p class="mt-1.5 text-[10px] font-semibold text-rose-500">Silakan klik tombol <b>Edit / Perbaiki</b> di sebelah kanan untuk memperbarui dan mengajukan ulang dokumen.</p>
                                </div>
                            @endif

                            @if($report->status === 'approved' && $report->approved_at)
                                <p class="text-[10px] text-emerald-600 mt-1 font-semibold flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Disetujui pada {{ \Carbon\Carbon::parse($report->approved_at)->format('d M Y H:i') }}
                                </p>
                            @endif
                        </td>

                        <td class="p-3 md:p-4 text-center">
                            @if($report->status === 'approved')
                                <span class="inline-flex items-center gap-1 bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 px-3 py-1.5 rounded-full font-bold text-[9px] uppercase tracking-wider shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Disetujui
                                </span>
                            @elseif($report->status === 'pending')
                                <span class="inline-flex items-center gap-1 bg-blue-500/10 text-blue-600 border border-blue-500/20 px-3 py-1.5 rounded-full font-bold text-[9px] uppercase tracking-wider shadow-sm">
                                    <svg class="w-3 h-3 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Menunggu ACC Admin Bidang
                                </span>
                            @elseif($report->status === 'rejected')
                                <span class="inline-flex items-center gap-1 bg-rose-500/10 text-rose-600 border border-rose-500/20 px-3 py-1.5 rounded-full font-bold text-[9px] uppercase tracking-wider shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    Ditolak
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 bg-amber-500/10 text-amber-600 border border-amber-500/20 px-3 py-1.5 rounded-full font-bold text-[9px] uppercase tracking-wider shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    Draft
                                </span>
                            @endif
                        </td>

                        <td class="p-3 md:p-4 pr-4 md:pr-6 text-center">
                            <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                {{-- Pegawai hanya boleh edit jika statusnya draft atau rejected. Admin boleh edit juga jika pending --}}
                                @if(in_array($report->status, ['draft', 'rejected']) || (Auth::user()->role === 'admin' && $report->status === 'pending'))
                                    <a href="{{ route('report.edit', $report->id) }}" class="inline-flex items-center gap-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 border border-amber-500/20 px-2.5 py-1.5 rounded-xl font-bold transition text-[11px]" title="Edit / Perbaiki Laporan">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        Edit
                                    </a>
                                @endif

                                @if($report->status === 'approved')
                                    <a href="{{ route('report.downloadById', $report->id) }}" class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-2.5 py-1.5 rounded-xl font-bold transition text-[11px] shadow-sm shadow-blue-500/20" title="Unduh File Resmi .docx">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        Word
                                    </a>
                                @else
                                    <button type="button" disabled class="inline-flex items-center gap-1 bg-slate-500/10 text-slate-400 border border-slate-500/20 px-2.5 py-1.5 rounded-xl font-semibold text-[11px] cursor-not-allowed opacity-60" title="Dokumen belum disetujui Admin Bidang. Menu unduh akan aktif setelah status Disetujui.">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                        Terkunci
                                    </button>
                                @endif

                                @if(in_array($report->status, ['draft', 'rejected']) || (Auth::user()->role === 'admin' && $report->status === 'pending'))
                                    <form action="{{ route('report.destroy', $report->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus draf laporan ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center p-1.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-600 border border-red-500/20 transition text-[11px]" title="Hapus">
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
                                <span class="font-bold text-sm text-primary">Belum ada riwayat dokumen laporan</span>
                                <span class="text-xs text-secondary mt-1">Laporan yang Anda buat dan ajukan akan tercatat rapi di halaman ini.</span>
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
