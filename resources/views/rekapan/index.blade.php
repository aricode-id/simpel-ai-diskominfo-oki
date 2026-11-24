@extends('layouts.admin')

@section('page_title', 'Menu Rekapan Laporan Disetujui')

@section('content')
<div class="space-y-6">
    
    <!-- Title Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-2">
        <div>
            <h2 class="text-xl md:text-2xl font-black text-primary">Rekapan Laporan Disetujui</h2>
            <p class="text-[11px] md:text-xs text-secondary mt-1">
                @if(Auth::user()->role === 'super_admin')
                    Rekapitulasi arsip seluruh dokumen resmi lintas 6 bidang yang telah diverifikasi dan disetujui.
                @elseif(Auth::user()->role === 'pegawai')
                    Rekapitulasi arsip laporan kerja pribadi Anda yang telah disetujui oleh Kepala Bidang.
                @else
                    Rekapitulasi arsip laporan resmi Bidang {{ Auth::user()->bidang }} yang telah diverifikasi dan disetujui.
                @endif
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 text-xs font-bold shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Total: {{ $reports->count() }} Dokumen Ter-ACC
            </span>
        </div>
    </div>

    <!-- FILTER CARD -->
    <div class="glass-card rounded-3xl p-5 md:p-6 shadow-sm border border-divider">
        <form action="{{ route('report.rekapan') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            
            @if(Auth::user()->role === 'super_admin')
            <div>
                <label class="block text-[10px] md:text-[11px] font-bold text-secondary mb-1.5 uppercase tracking-wide">Pilih Bidang</label>
                <select name="bidang" class="input-field w-full px-3 py-2.5 rounded-xl outline-none text-xs md:text-sm font-semibold transition appearance-none">
                    <option value="Semua" {{ request('bidang') == 'Semua' ? 'selected' : '' }}>-- Semua Bidang --</option>
                    @foreach($bidangList ?? ['Sekretariat', 'Statistik', 'PKP', 'TIK', 'E-Gov', 'Persandian'] as $b)
                        <option value="{{ $b }}" {{ request('bidang') == $b ? 'selected' : '' }}>{{ $b }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <div>
                <label class="block text-[10px] md:text-[11px] font-bold text-secondary mb-1.5 uppercase tracking-wide">Bidang</label>
                <div class="input-field w-full px-3 py-2.5 rounded-xl text-xs md:text-sm font-semibold opacity-80 cursor-not-allowed">
                    Bidang {{ Auth::user()->bidang }}
                </div>
            </div>
            @endif

            <div>
                <label class="block text-[10px] md:text-[11px] font-bold text-secondary mb-1.5 uppercase tracking-wide">Jenis Dokumen</label>
                <select name="jenis_dokumen" class="input-field w-full px-3 py-2.5 rounded-xl outline-none text-xs md:text-sm font-semibold transition appearance-none">
                    <option value="Semua" {{ request('jenis_dokumen') == 'Semua' ? 'selected' : '' }}>-- Semua Jenis Dokumen --</option>
                    <option value="Laporan Harian" {{ request('jenis_dokumen') == 'Laporan Harian' ? 'selected' : '' }}>Laporan Harian</option>
                    <option value="Laporan Mingguan" {{ request('jenis_dokumen') == 'Laporan Mingguan' ? 'selected' : '' }}>Laporan Mingguan</option>
                    <option value="Notulensi Rapat" {{ request('jenis_dokumen') == 'Notulensi Rapat' ? 'selected' : '' }}>Notulensi Rapat</option>
                    <option value="Laporan Perjalanan Dinas" {{ request('jenis_dokumen') == 'Laporan Perjalanan Dinas' ? 'selected' : '' }}>Laporan Perjalanan Dinas (LHPD)</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] md:text-[11px] font-bold text-secondary mb-1.5 uppercase tracking-wide">Periode Bulan & Tahun</label>
                <input type="month" name="periode" value="{{ request('periode') }}" class="input-field w-full px-3 py-2 rounded-xl outline-none text-xs md:text-sm font-semibold transition">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-xl transition shadow-md shadow-blue-500/20 text-xs md:text-sm flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    Terapkan Filter
                </button>
                <a href="{{ route('report.rekapan') }}" class="bg-slate-500/10 hover:bg-slate-500/20 text-primary border border-divider font-bold px-3 py-2.5 rounded-xl transition text-xs md:text-sm" title="Reset Filter">
                    Reset
                </a>
            </div>

        </form>
    </div>

    <!-- TABEL REKAPAN -->
    <div class="glass-card rounded-3xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto w-full">
            <table class="w-full text-left min-w-[900px]">
                <thead>
                    <tr class="text-secondary text-[9px] md:text-[10px] font-black uppercase tracking-wider table-border bg-slate-500/5">
                        <th class="p-3 md:p-4 pl-4 md:pl-6 w-12 text-center">No</th>
                        <th class="p-3 md:p-4 w-32">Tanggal Laporan</th>
                        <th class="p-3 md:p-4 w-28 text-center">Jenis</th>
                        <th class="p-3 md:p-4">Judul Dokumen</th>
                        <th class="p-3 md:p-4 w-36">Bidang</th>
                        <th class="p-3 md:p-4 w-36">Pembuat</th>
                        <th class="p-3 md:p-4 w-44">Disetujui Oleh</th>
                        <th class="p-3 md:p-4 pr-4 md:pr-6 text-center w-32">Unduh .docx</th>
                    </tr>
                </thead>
                <tbody class="text-[11px] md:text-xs divide-y divide-divider border-divider">
                    @forelse($reports as $index => $report)
                    <tr class="table-border hover:bg-slate-500/5 transition">
                        <td class="p-3 md:p-4 pl-4 md:pl-6 text-center font-bold text-secondary">
                            {{ $index + 1 }}
                        </td>

                        <td class="p-3 md:p-4 font-bold text-primary">
                            {{ \Carbon\Carbon::parse($report->tanggal)->format('d M Y') }}
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
                        </td>

                        <td class="p-3 md:p-4 font-semibold text-secondary">
                            {{ $report->bidang ?? '-' }}
                        </td>

                        <td class="p-3 md:p-4">
                            <p class="font-bold text-primary">{{ $report->user->name ?? 'Pegawai' }}</p>
                            <p class="text-[10px] text-secondary">NIP: {{ $report->user->nip ?? '-' }}</p>
                        </td>

                        <td class="p-3 md:p-4">
                            @if($report->approver)
                                <p class="font-bold text-emerald-600 leading-tight">✓ {{ $report->approver->name }}</p>
                                <p class="text-[10px] text-secondary mt-0.5">{{ $report->approved_at ? \Carbon\Carbon::parse($report->approved_at)->format('d M Y H:i') : 'Disetujui' }}</p>
                            @else
                                <p class="font-semibold text-emerald-600">✓ Disetujui</p>
                            @endif
                        </td>

                        <td class="p-3 md:p-4 pr-4 md:pr-6 text-center">
                            <a href="{{ route('report.downloadById', $report->id) }}" class="inline-flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-xl font-bold transition text-[11px] shadow-sm shadow-blue-500/20" title="Unduh File Resmi Word">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Word (.docx)
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-16 text-center text-secondary">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 mb-3 opacity-40 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span class="font-bold text-sm text-primary">Belum ada dokumen rekapan yang disetujui</span>
                                <span class="text-xs text-secondary mt-1">Dokumen yang telah diverifikasi dan disetujui oleh Kepala Bidang atau Super Admin akan muncul di sini.</span>
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
