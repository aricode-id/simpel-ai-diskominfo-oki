@extends('layouts.admin')

@section('page_title', 'Monitoring Kinerja Pegawai')

@section('content')
<div class="space-y-6">
    
    <!-- Title & Filter Section -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-500/10 text-blue-600 border border-blue-500/20">
                    {{ $selectedBidang === 'Semua' ? 'Seluruh Bidang' : 'Bidang ' . $selectedBidang }}
                </span>
                @if(!empty($periode) && $periode !== 'all')
                    @php
                        $ts = strtotime($periode . '-01');
                        $bulanIndo = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                        $namaPeriode = $bulanIndo[(int)date('n', $ts)] . ' ' . date('Y', $ts);
                    @endphp
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-500/10 text-secondary border border-divider">
                        Periode: {{ $namaPeriode }}
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-500/10 text-secondary border border-divider">
                        Semua Periode
                    </span>
                @endif
            </div>
            <h2 class="text-xl md:text-2xl font-black text-primary">Monitoring Kinerja Staf Pegawai</h2>
            <p class="text-[11px] md:text-xs text-secondary mt-0.5">
                Pantau kedisiplinan dan rekapitulasi pelaporan kegiatan seluruh staf {{ $selectedBidang === 'Semua' ? 'seluruh bidang' : 'Bidang ' . $selectedBidang }}.
            </p>
        </div>

        <!-- Form Filter Periode & Bidang -->
        <form action="{{ route('admin.monitoring') }}" method="GET" class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
            @if(request('search'))
                <input type="hidden" name="search" value="{{ request('search') }}">
            @endif

            @if(Auth::user()->role === 'super_admin')
                <div>
                    <select name="bidang" onchange="this.form.submit()" class="input-field px-3 py-2 rounded-xl text-xs font-bold transition">
                        <option value="Semua" {{ $selectedBidang === 'Semua' ? 'selected' : '' }}>Semua Bidang</option>
                        @foreach($bidangList as $b)
                            <option value="{{ $b }}" {{ $selectedBidang === $b ? 'selected' : '' }}>Bidang {{ $b }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="flex items-center gap-2">
                <div class="relative">
                    <input type="month" name="periode" value="{{ $periode !== 'all' ? $periode : '' }}" onchange="this.form.submit()" class="input-field px-3 py-2 rounded-xl text-xs font-semibold transition" title="Pilih Periode Bulan">
                </div>

                @if($periode !== 'all')
                    <a href="{{ route('admin.monitoring', array_merge(request()->except('periode'), ['periode' => 'all'])) }}" class="bg-slate-500/10 hover:bg-slate-500/20 text-secondary border border-divider px-3 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap" title="Tampilkan Semua Periode">
                        Semua Periode
                    </a>
                @else
                    <a href="{{ route('admin.monitoring', array_merge(request()->except('periode'), ['periode' => date('Y-m')])) }}" class="bg-blue-600/10 hover:bg-blue-600/20 text-blue-600 border border-blue-500/20 px-3 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap" title="Filter Bulan Ini">
                        Bulan Ini
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- KPI STATS SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Pegawai -->
        <div class="glass-card rounded-3xl p-5 shadow-sm border border-divider flex items-center justify-between">
            <div>
                <p class="text-[10px] md:text-[11px] font-black text-secondary uppercase tracking-widest">Total Staf Pegawai</p>
                <h3 class="text-2xl font-black text-primary mt-1">{{ $summary['total_pegawai'] }}</h3>
                <p class="text-[10px] text-secondary font-medium mt-0.5">
                    Staf Pegawai Wajib Lapor
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            </div>
        </div>

        <!-- Pegawai Aktif Melapor -->
        <div class="glass-card rounded-3xl p-5 shadow-sm border border-divider flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5">
                    <p class="text-[10px] md:text-[11px] font-black text-secondary uppercase tracking-widest">Sudah Melapor</p>
                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-emerald-500/10 text-emerald-600">
                        {{ $summary['persen_aktif'] }}%
                    </span>
                </div>
                <h3 class="text-2xl font-black text-emerald-600 mt-1">{{ $summary['aktif_melapor'] }} <span class="text-xs text-secondary font-normal">/ {{ $summary['total_pegawai'] }}</span></h3>
                <p class="text-[10px] text-secondary font-medium mt-0.5">Membuat laporan di periode ini</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

        <!-- Pegawai Belum Melapor -->
        <div class="glass-card rounded-3xl p-5 shadow-sm border border-divider flex items-center justify-between">
            <div>
                <p class="text-[10px] md:text-[11px] font-black text-secondary uppercase tracking-widest">Belum Melapor</p>
                <h3 class="text-2xl font-black {{ $summary['belum_melapor'] > 0 ? 'text-amber-500' : 'text-slate-400' }} mt-1">{{ $summary['belum_melapor'] }}</h3>
                <p class="text-[10px] text-secondary font-medium mt-0.5">{{ $summary['belum_melapor'] > 0 ? 'Perlu diingatkan' : 'Semua sudah melapor' }}</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
        </div>

        <!-- Total Laporan Masuk -->
        <div class="glass-card rounded-3xl p-5 shadow-sm border border-divider flex items-center justify-between">
            <div>
                <p class="text-[10px] md:text-[11px] font-black text-secondary uppercase tracking-widest">Total Dokumen</p>
                <h3 class="text-2xl font-black text-blue-600 mt-1">{{ $summary['total_laporan'] }}</h3>
                <p class="text-[10px] text-secondary font-medium mt-0.5">
                    <span class="text-emerald-500 font-bold">{{ $summary['total_approved'] }} Disetujui</span> &bull; 
                    <span class="text-amber-500 font-bold">{{ $summary['total_pending'] }} Pending</span>
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            </div>
        </div>

    </div>

    <!-- TABEL MONITORING PER PEGAWAI -->
    <div class="glass-card rounded-3xl overflow-hidden shadow-sm border border-divider">
        
        <div class="p-4 md:p-5 border-b border-divider bg-slate-500/5 space-y-3.5">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <h3 class="text-base font-black text-primary">Daftar Pegawai & Status Pelaporan</h3>
                </div>

                <!-- Search box dengan icon kaca pembesar rapi di kanan -->
                <form action="{{ route('admin.monitoring') }}" method="GET" class="w-full sm:w-80">
                    @if(request('periode'))
                        <input type="hidden" name="periode" value="{{ request('periode') }}">
                    @endif
                    @if(request('bidang'))
                        <input type="hidden" name="bidang" value="{{ request('bidang') }}">
                    @endif
                    <div class="relative w-full" style="position: relative;">
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Cari nama, NIP, atau jabatan..."
                            class="input-field w-full rounded-xl text-xs font-medium outline-none transition"
                            style="padding: 9px 38px 9px 14px;"
                        >
                        <button
                            type="submit"
                            title="Cari Pegawai"
                            class="text-secondary hover:text-blue-600 transition flex items-center justify-center"
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; padding: 2px; cursor: pointer;"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Filter Cepat Per Bidang (Khusus Super Admin) -->
            @if(Auth::user()->role === 'super_admin')
                <div class="flex items-center gap-1.5 flex-wrap pt-1">
                    <span class="text-[10px] font-black uppercase tracking-wider text-secondary mr-1">Filter Bidang:</span>
                    <a
                        href="{{ route('admin.monitoring', array_merge(request()->except('bidang'), ['bidang' => 'Semua'])) }}"
                        class="px-3 py-1.5 rounded-xl text-[11px] font-bold transition {{ $selectedBidang === 'Semua' ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/25' : 'bg-slate-500/10 hover:bg-slate-500/20 text-secondary border border-divider' }}"
                    >
                        Semua Bidang
                    </a>
                    @foreach($bidangList as $b)
                        <a
                            href="{{ route('admin.monitoring', array_merge(request()->except('bidang'), ['bidang' => $b])) }}"
                            class="px-3 py-1.5 rounded-xl text-[11px] font-bold transition {{ $selectedBidang === $b ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/25' : 'bg-slate-500/10 hover:bg-slate-500/20 text-secondary border border-divider' }}"
                        >
                            {{ $b }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="overflow-x-auto w-full">
            <table class="w-full text-left min-w-[950px]">
                <thead>
                    <tr class="text-[9px] md:text-[10px] font-black uppercase tracking-wider table-border text-secondary bg-slate-500/5">
                        <th class="p-3 md:p-4 pl-4 md:pl-6 w-12 text-center">No</th>
                        <th class="p-3 md:p-4 w-60">Nama & Jabatan</th>
                        <th class="p-3 md:p-4 w-32 text-center">Keaktifan</th>
                        <th class="p-3 md:p-4 w-48 text-center">Rincian Dokumen</th>
                        <th class="p-3 md:p-4 w-44 text-center">Status Verifikasi</th>
                        <th class="p-3 md:p-4">Laporan Terakhir</th>
                        <th class="p-3 md:p-4 pr-4 md:pr-6 text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-[11px] md:text-xs divide-y divide-divider border-divider">
                    @forelse($monitoringData as $idx => $row)
                        @php
                            $user = $row['user'];
                            $latest = $row['latest_report'];
                        @endphp
                        <tr class="table-border hover:bg-slate-500/5 transition">
                            
                            <!-- No -->
                            <td class="p-3 md:p-4 pl-4 md:pl-6 text-center font-bold text-secondary">
                                {{ $idx + 1 }}
                            </td>

                            <!-- Nama & Jabatan -->
                            <td class="p-3 md:p-4 font-bold text-primary max-w-[240px]">
                                <p class="truncate leading-tight text-xs md:text-sm font-black">{{ $user->name }}</p>
                                @if(!empty($user->jabatan))
                                    <p class="text-[10px] text-blue-500 font-semibold truncate mt-0.5">{{ $user->jabatan }}</p>
                                @endif
                                
                                <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                    @if($selectedBidang === 'Semua')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-500/10 text-purple-600 border border-purple-500/20">
                                            Bidang {{ $user->bidang ?? '-' }}
                                        </span>
                                    @endif
                                    @if(($user->status_pegawai ?? 'ASN') === 'Tenaga Ahli')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20 font-mono">
                                            ID: {{ $user->nip ?? '-' }} (Tenaga Ahli)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium bg-slate-500/10 text-secondary border border-divider font-mono">
                                            NIP: {{ $user->nip ?? '-' }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Keaktifan Periode Ini -->
                            <td class="p-3 md:p-4 text-center">
                                @if($row['is_active'])
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif Melapor
                                    </span>
                                    <p class="text-[9px] text-secondary font-semibold mt-1">
                                        {{ $row['total_laporan'] }} laporan
                                    </p>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Belum Melapor
                                    </span>
                                    <p class="text-[9px] text-secondary font-semibold mt-1">
                                        0 laporan periode ini
                                    </p>
                                @endif
                            </td>

                            <!-- Rincian Dokumen -->
                            <td class="p-3 md:p-4 text-center">
                                <div class="grid grid-cols-2 gap-1 max-w-[160px] mx-auto text-[10px]">
                                    <span class="px-1.5 py-0.5 rounded bg-blue-500/10 text-blue-600 border border-blue-500/20 font-bold" title="Laporan Harian">
                                        Harian: {{ $row['harian_count'] }}
                                    </span>
                                    <span class="px-1.5 py-0.5 rounded bg-indigo-500/10 text-indigo-600 border border-indigo-500/20 font-bold" title="Laporan Mingguan">
                                        Mingguan: {{ $row['mingguan_count'] }}
                                    </span>
                                    <span class="px-1.5 py-0.5 rounded bg-purple-500/10 text-purple-600 border border-purple-500/20 font-bold" title="Notulensi Rapat">
                                        Notulensi: {{ $row['notulen_count'] }}
                                    </span>
                                    <span class="px-1.5 py-0.5 rounded bg-teal-500/10 text-teal-600 border border-teal-500/20 font-bold" title="Laporan Perjalanan Dinas">
                                        LHPD: {{ $row['lhpd_count'] }}
                                    </span>
                                </div>
                            </td>

                            <!-- Status Verifikasi -->
                            <td class="p-3 md:p-4 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap text-[10px]">
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 font-bold border border-emerald-500/20" title="Disetujui">
                                        ✓ {{ $row['approved_count'] }} ACC
                                    </span>
                                    <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-500 font-bold border border-amber-500/20" title="Menunggu Verifikasi">
                                        ⏱ {{ $row['pending_count'] }} Pending
                                    </span>
                                    @if($row['rejected_count'] > 0)
                                        <span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-500 font-bold border border-rose-500/20" title="Perlu Revisi">
                                            ✕ {{ $row['rejected_count'] }} Revisi
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Laporan Terakhir -->
                            <td class="p-3 md:p-4 max-w-[220px]">
                                @if($latest)
                                    <p class="font-bold text-primary truncate leading-tight text-xs" title="{{ $latest->judul }}">
                                        {{ $latest->judul ?? '-' }}
                                    </p>
                                    <div class="flex items-center gap-1.5 text-[10px] text-secondary mt-1">
                                        <span class="font-semibold">{{ $latest->jenis_dokumen }}</span>
                                        <span>&bull;</span>
                                        <span>{{ date('d/m/Y', strtotime($latest->tanggal ?? $latest->created_at)) }}</span>
                                    </div>
                                @else
                                    <p class="text-xs text-secondary italic">Belum ada dokumen yang dibuat</p>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="p-3 md:p-4 pr-4 md:pr-6 text-center">
                                <a href="{{ route('admin.laporan.history', ['user_id' => $user->id]) }}" class="inline-flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold px-3 py-1.5 rounded-xl transition shadow-md shadow-blue-500/20 text-xs whitespace-nowrap" title="Buka Riwayat Lengkap Laporan Pegawai Ini">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    Lihat Riwayat
                                </a>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-secondary">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-secondary opacity-40 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    <p class="font-bold text-sm text-primary">Tidak Ada Data Pegawai</p>
                                    <p class="text-xs text-secondary mt-0.5">Belum ada akun pegawai yang terdaftar pada {{ $selectedBidang === 'Semua' ? 'seluruh bidang' : 'Bidang ' . $selectedBidang }}.</p>
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
