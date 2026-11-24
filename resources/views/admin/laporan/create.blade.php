@extends('layouts.admin')

@section('page_title', isset($draft) ? 'Edit Laporan' : 'Buat Laporan Baru')

@section('content')
<style> 
    .day-label { background-color: #EFF6FF; color: #2563EB; border: 1px solid #BFDBFE; }
    body.dark-mode .day-label { background-color: rgba(37, 99, 235, 0.2); color: #93C5FD; border: 1px solid rgba(59, 130, 246, 0.3); }
</style>

@php
    $oldData = isset($draft) ? ($draft->form_data ?? []) : [];
    $isPegawai = (Auth::user()->role === 'pegawai');
    $jenisDokumen = isset($draft) ? $draft->jenis_dokumen : ($isPegawai ? 'Laporan Harian' : 'Laporan Mingguan');
    $usersDbList = \App\Models\User::select('name', 'nip', 'status_pegawai', 'jabatan')->orderBy('name')->get();

    // Data Kepala Bidang default sesuai bidang pengguna login
    $defaultKabid = \App\Http\Controllers\ReportController::findKepalaBidang(Auth::user()->bidang);
    $defaultKabidNama = $defaultKabid ? $defaultKabid->name : '';
    $defaultKabidNip = ($defaultKabid && $defaultKabid->status_pegawai !== 'Tenaga Ahli' && !empty($defaultKabid->nip) && !str_starts_with($defaultKabid->nip, 'TA-')) ? $defaultKabid->nip : '';
    $defaultKabidJabatan = ($defaultKabid && !empty($defaultKabid->jabatan)) ? $defaultKabid->jabatan : ('Kepala Bidang ' . (Auth::user()->bidang ?? ''));
@endphp

<div class="max-w-4xl mx-auto glass-card rounded-3xl p-6 md:p-10 relative z-10 shadow-sm">
    
    <div class="flex justify-between items-center border-b border-divider pb-5 mb-8">
        <div>
            <h1 class="text-xl md:text-2xl font-black text-primary">{{ isset($draft) ? 'Edit Dokumen Laporan' : 'Buat Laporan Baru' }}</h1>
            <p class="text-xs text-secondary mt-1 tracking-wide uppercase font-semibold">
                @if(Auth::user()->role === 'super_admin')
                    Super Admin Diskominfo OKI
                @elseif(in_array(Auth::user()->role, ['admin_bidang', 'admin']))
                    Admin Bidang {{ Auth::user()->bidang }}
                @else
                    Pegawai Bidang {{ Auth::user()->bidang ?? 'Diskominfo OKI' }}
                @endif
            </p>
        </div>
        
        <div class="flex items-center gap-3">
            @if($isPegawai)
                <a href="{{ route('report.history') }}" class="flex items-center gap-1.5 text-xs font-bold text-secondary hover:text-primary transition bg-slate-500/5 px-3 py-2 rounded-xl">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Riwayat Laporan
                </a>
            @else
                <a href="{{ route('admin.laporan.history') }}" class="flex items-center gap-1.5 text-xs font-bold text-secondary hover:text-primary transition bg-slate-500/5 px-3 py-2 rounded-xl">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Daftar Arsip
                </a>
            @endif
        </div>
    </div>

    @if(isset($draft) && $draft->status === 'rejected' && $draft->catatan_revisi)
        <div class="mb-6 p-4 bg-rose-500/10 border border-rose-500/20 text-rose-600 rounded-2xl text-xs shadow-sm">
            <div class="flex items-center gap-2 font-black mb-1 text-sm">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                Perhatian: Dokumen Ini Perlu Direvisi
            </div>
            <p class="leading-relaxed whitespace-pre-line">{{ $draft->catatan_revisi }}</p>
            <p class="mt-2 text-[11px] font-bold text-rose-500">Perbaiki catatan di bawah dan klik "Ajukan ke Admin Bidang" untuk mengirim ulang verifikasi.</p>
        </div>
    @endif

    <form id="reportForm" action="{{ route('report.generate', [], false) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf
        
        <input type="hidden" name="report_id" id="report_id_field" value="{{ isset($draft) ? $draft->id : '' }}">

        <div class="space-y-5">
            <div class="bg-blue-500/5 p-5 rounded-2xl border border-blue-500/20">
                <label for="pilih_jenis" class="block text-xs font-black text-primary mb-2 uppercase tracking-wide">Pilih Jenis Dokumen</label>
                <select id="pilih_jenis" name="jenis_dokumen" onchange="aturForm()" class="input-field w-full px-4 py-3 rounded-xl text-sm font-semibold transition cursor-pointer">
                    <option value="Laporan Harian" {{ $jenisDokumen === 'Laporan Harian' ? 'selected' : '' }}>Laporan Harian (Individu / e-Kinerja)</option>
                    <option value="Laporan Mingguan" {{ $jenisDokumen === 'Laporan Mingguan' ? 'selected' : '' }}>Laporan Mingguan (Rekap Senin - Jumat)</option>
                    <option value="Notulensi Rapat" {{ $jenisDokumen === 'Notulensi Rapat' ? 'selected' : '' }}>Notulensi Rapat Resmi</option>
                    <option value="Laporan Perjalanan Dinas" {{ $jenisDokumen === 'Laporan Perjalanan Dinas' ? 'selected' : '' }}>Laporan Hasil Perjalanan Dinas (LHPD)</option>
                </select>
            </div>

            <div id="wrap_kop_ttd_row" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div id="wrap_kop_select">
                    <label for="pakai_kop" class="block text-[11px] font-bold text-secondary mb-1.5">Format Kop Surat</label>
                    <select name="pakai_kop" id="pakai_kop" onchange="aturForm()" class="input-field w-full p-2.5 rounded-xl text-sm transition">
                        <option value="ya" {{ ($oldData['pakai_kop'] ?? '') === 'ya' ? 'selected' : '' }}>Kop Resmi OKI</option>
                        <option value="tidak" {{ ($oldData['pakai_kop'] ?? 'tidak') === 'tidak' ? 'selected' : '' }}>Polos (Tanpa Kop)</option>
                    </select>
                </div>
                <div id="wrap_ttd_select">
                    <label for="pakai_ttd" class="block text-[11px] font-bold text-secondary mb-1.5">Penandatangan Pimpinan</label>
                    <select name="pakai_ttd" id="pakai_ttd" onchange="aturForm()" class="input-field w-full p-2.5 rounded-xl text-sm transition">
                        <option value="tidak" {{ ($oldData['pakai_ttd'] ?? 'tidak') === 'tidak' ? 'selected' : '' }}>Tanpa TTD Mengetahui (Hanya Penyusun)</option>
                        <option value="ya" {{ ($oldData['pakai_ttd'] ?? '') === 'ya' ? 'selected' : '' }}>Gunakan TTD Mengetahui</option>
                    </select>
                </div>
            </div>

            <div id="wrap_ttd_detail" class="hidden bg-sub-card p-5 rounded-2xl space-y-4">
                <h3 class="text-xs font-bold text-primary border-b border-divider pb-2">Informasi Penandatanganan Pimpinan (Mengetahui)</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-secondary uppercase mb-1">Jabatan</label>
                        <input type="text" name="ttd_jabatan" id="ttd_jabatan" value="{{ $oldData['ttd_jabatan'] ?? $defaultKabidJabatan }}" class="input-field w-full p-2 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-secondary uppercase mb-1">Nama Lengkap & Gelar</label>
                        <input type="text" list="list_petugas_sppd" name="ttd_nama" id="ttd_nama" oninput="onTtdNamaInput(this)" value="{{ $oldData['ttd_nama'] ?? $defaultKabidNama }}" placeholder="Pilih / Nama Lengkap Pimpinan" class="input-field w-full p-2 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-secondary uppercase mb-1">NIP <span class="text-slate-400 font-normal">(Kosongkan jika Tenaga Ahli)</span></label>
                        <input type="text" name="ttd_nip" id="ttd_nip" value="{{ $oldData['ttd_nip'] ?? $defaultKabidNip }}" placeholder="NIP Pimpinan (Opsional)" class="input-field w-full p-2 rounded-lg text-sm font-mono">
                        <input type="hidden" name="ttd_status_pegawai" id="ttd_status_pegawai" value="{{ $oldData['ttd_status_pegawai'] ?? 'ASN' }}">
                    </div>
                </div>
            </div>

            <div id="wrap_bidang">
                <label class="block text-[11px] font-bold text-secondary mb-1.5">Bidang / Divisi Pelaksana</label>
                
                @if(Auth::user()->role === 'super_admin')
                    <select name="bidang" class="input-field w-full p-2.5 rounded-xl text-sm font-semibold transition appearance-none cursor-pointer">
                        @foreach(['Sekretariat', 'Statistik', 'PKP', 'TIK', 'E-Gov', 'Persandian'] as $b)
                            <option value="{{ $b }}" {{ ($oldData['bidang'] ?? (isset($draft) ? $draft->bidang : 'Sekretariat')) === $b ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>
                    <p class="text-[9px] text-blue-500 mt-1.5 font-bold flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Mode Super Admin: Anda dapat memilih bidang pelaksana dokumen.
                    </p>
                @else
                    <div class="input-field w-full flex items-center gap-2.5 px-3 py-2 rounded-xl cursor-not-allowed opacity-80">
                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        <input type="text" value="Bidang {{ Auth::user()->bidang ?? 'Diskominfo OKI' }}" disabled class="w-full bg-transparent outline-none text-sm font-bold text-secondary cursor-not-allowed">
                        <input type="hidden" name="bidang" value="{{ Auth::user()->bidang }}">
                    </div>
                @endif
            </div>
        </div>

        <hr class="border-divider">

        <!-- ===================== FORM LAPORAN HARIAN ===================== -->
        <div id="form_harian" class="space-y-5 {{ $jenisDokumen === 'Laporan Harian' ? '' : 'hidden' }} bg-sub-card rounded-2xl p-5 md:p-6 border border-divider">
            <div class="bg-blue-500/5 p-4 rounded-xl border border-blue-500/20 mb-4">
                <p class="text-xs text-blue-700 dark:text-blue-400 font-bold flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Format Laporan Harian e-Kinerja (Otomatis Tersusun Poin A–C + Lampiran Grid 2 Kolom & Siap Unduh)
                </p>
            </div>

            <div id="container_kegiatan_harian">
                @php
                    $kegiatans_harian = isset($oldData['kegiatan']) ? $oldData['kegiatan'] : [0 => []];
                @endphp

                @foreach($kegiatans_harian as $kegIdx => $keg)
                <div class="{{ $kegIdx > 0 ? 'mt-6 pt-6 border-t border-dashed border-slate-300 relative' : '' }}">
                    @if($kegIdx > 0)
                        <button type="button" onclick="this.parentElement.remove()" class="absolute -top-3 right-0 bg-red-100 hover:bg-red-200 text-red-600 px-2 py-1 rounded text-[10px] font-bold transition">Hapus Kegiatan Ini</button>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-[11px] font-bold text-secondary mb-1.5">Judul Kegiatan {{ $kegIdx + 1 }}:</label>
                            <input type="text" name="kegiatan[{{$kegIdx}}][judul]" value="{{ $keg['judul'] ?? '' }}" placeholder="Kosongkan agar AI membuat judul..." class="input-field w-full p-2.5 rounded-xl text-sm transition">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-secondary mb-1.5">Lokasi / Tempat:</label>
                            <input type="text" name="kegiatan[{{$kegIdx}}][lokasi]" value="{{ $keg['lokasi'] ?? '' }}" placeholder="Contoh: Halaman / Kantor Dinas Kominfo Kab. OKI" class="input-field w-full p-2.5 rounded-xl text-sm transition">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-[11px] font-bold text-secondary mb-1.5">Tanggal Pelaksanaan:</label>
                            <input type="date" name="kegiatan[{{$kegIdx}}][tanggal]" value="{{ $keg['tanggal'] ?? ($oldData['tanggal'] ?? date('Y-m-d')) }}" class="input-field w-full p-2.5 rounded-xl text-sm transition">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-secondary mb-1.5">Waktu Mulai:</label>
                            <input type="time" name="kegiatan[{{$kegIdx}}][waktu_mulai]" value="{{ $keg['waktu_mulai'] ?? '08:00' }}" class="input-field w-full p-2.5 rounded-xl text-sm transition">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-secondary mb-1.5">Waktu Selesai: <span class="text-gray-400 font-normal">(opsional)</span></label>
                            <input type="time" name="kegiatan[{{$kegIdx}}][waktu_selesai]" value="{{ $keg['waktu_selesai'] ?? '' }}" class="input-field w-full p-2.5 rounded-xl text-sm transition">
                        </div>
                    </div>

                    <div class="mb-5">
                        <div class="flex justify-between items-end mb-1.5">
                            <label class="block text-[11px] font-bold text-secondary">Catatan Singkat / Poin Pelaksanaan Kegiatan:</label>
                            <label class="flex items-center gap-2 cursor-pointer bg-slate-500/5 px-2.5 py-1.5 rounded border border-divider hover:bg-slate-500/10 transition">
                                <input type="checkbox" name="kegiatan[{{$kegIdx}}][bypass_ai]" value="1" {{ !empty($keg['bypass_ai']) ? 'checked' : '' }} class="w-3.5 h-3.5 text-blue-600 rounded">
                                <span class="text-[10px] font-bold text-primary">Bypass AI (Manual)</span>
                            </label>
                        </div>
                        <textarea name="kegiatan[{{$kegIdx}}][hasil_kegiatan]" rows="4" placeholder="Ketik poin kegiatan, arahan, atau hasil yang dicapai... AI akan otomatis menyusunnya ke format A–C e-Kinerja." class="input-field w-full p-3 rounded-xl text-sm transition">{{ $keg['hasil_kegiatan'] ?? '' }}</textarea>
                    </div>

                    <div class="border-t border-divider pt-4 mt-4">
                        <label class="block text-[10px] font-bold text-secondary mb-4 uppercase tracking-widest">Lampiran Dokumentasi Kegiatan {{ $kegIdx + 1 }} <span class="text-slate-400 font-normal">(Masuk ke Grid 2 Kolom Halaman Lampiran)</span></label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div class="bg-sub-card p-4 border-dashed border-divider rounded-xl transition hover:border-blue-400">
                                <label class="font-bold text-[10px] text-primary block mb-2 uppercase">Foto Dokumentasi 1:</label>
                                <input type="file" name="foto_{{$kegIdx}}_1" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                                @if(isset($keg['foto_1_path'])) <p class="text-[10px] text-green-600 mt-2 font-semibold">✓ Tersimpan</p> @endif
                            </div>
                            <div class="bg-sub-card p-4 border-dashed border-divider rounded-xl transition hover:border-blue-400">
                                <label class="font-bold text-[10px] text-primary block mb-2 uppercase">Foto Dokumentasi 2 <span class="text-slate-400 font-normal">(Opsional)</span>:</label>
                                <input type="file" name="foto_{{$kegIdx}}_2" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                                @if(isset($keg['foto_2_path'])) <p class="text-[10px] text-green-600 mt-2 font-semibold">✓ Tersimpan</p> @endif
                            </div>
                            <div class="bg-sub-card p-4 border-dashed border-divider rounded-xl transition hover:border-blue-400">
                                <label class="font-bold text-[10px] text-primary block mb-2 uppercase">Foto Dokumentasi 3 <span class="text-slate-400 font-normal">(Opsional)</span>:</label>
                                <input type="file" name="foto_{{$kegIdx}}_3" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                                @if(isset($keg['foto_3_path'])) <p class="text-[10px] text-green-600 mt-2 font-semibold">✓ Tersimpan</p> @endif
                            </div>
                            <div class="bg-sub-card p-4 border-dashed border-divider rounded-xl transition hover:border-blue-400">
                                <label class="font-bold text-[10px] text-primary block mb-2 uppercase">Foto Dokumentasi 4 <span class="text-slate-400 font-normal">(Opsional)</span>:</label>
                                <input type="file" name="foto_{{$kegIdx}}_4" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                                @if(isset($keg['foto_4_path'])) <p class="text-[10px] text-green-600 mt-2 font-semibold">✓ Tersimpan</p> @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            <div class="mt-4 text-center">
                <button type="button" onclick="tambahKegiatanHarian()" class="inline-flex items-center gap-1.5 bg-blue-500/10 hover:bg-blue-500/20 text-blue-600 font-bold px-4 py-2 rounded-lg text-[11px] transition uppercase tracking-wider">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Kegiatan Harian Lain
                </button>
            </div>
        </div>

        <!-- ===================== FORM PERJALANAN DINAS ===================== -->
        <div id="form_perjalanan_dinas" class="space-y-6 hidden bg-sub-card rounded-2xl p-5 md:p-6 border border-divider">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1">Tanggal Mulai Berangkat</label>
                    <input type="date" name="sppd_tanggal" value="{{ $oldData['sppd_tanggal'] ?? date('Y-m-d') }}" class="input-field w-full p-2.5 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1">Tujuan Lokasi</label>
                    <input type="text" name="sppd_tujuan" value="{{ $oldData['sppd_tujuan'] ?? '' }}" placeholder="Contoh: Kantor Camat Teluk Gelam" class="input-field w-full p-2.5 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1">Lama Perjalanan (Hari)</label>
                    <input type="text" name="sppd_lama" value="{{ $oldData['sppd_lama'] ?? '1 (satu) hari' }}" placeholder="Contoh: 1 (satu) hari" class="input-field w-full p-2.5 rounded-xl text-sm">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-secondary mb-1">Maksud Perjalanan</label>
                <input type="text" name="sppd_maksud" value="{{ $oldData['sppd_maksud'] ?? '' }}" placeholder="Contoh: Koordinasi Jaringan Komunikasi" class="input-field w-full p-2.5 rounded-xl text-sm">
            </div>

            <div class="border-t border-divider pt-4">
                <div class="flex justify-between items-center mb-3">
                    <label class="block text-[11px] font-bold text-secondary uppercase tracking-widest">Daftar Petugas Berangkat</label>
                    <select id="sppd_jumlah_petugas" name="sppd_jumlah_petugas" onchange="aturJumlahPetugas()" class="input-field text-xs p-1.5 rounded-lg">
                        @for($i=1; $i<=6; $i++)
                            <option value="{{ $i }}" {{ ($oldData['sppd_jumlah_petugas'] ?? 2) == $i ? 'selected' : '' }}>{{ $i }} Petugas</option>
                        @endfor
                    </select>
                </div>

                <datalist id="list_petugas_sppd">
                    @foreach($usersDbList as $u)
                        <option value="{{ $u->name }}" data-nip="{{ $u->nip }}" data-status="{{ $u->status_pegawai ?? 'ASN' }}">{{ $u->name }} ({{ $u->status_pegawai ?? 'ASN' }})</option>
                    @endforeach
                </datalist>

                @for($i=0; $i<6; $i++)
                <div id="petugas_row_{{ $i }}" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3" style="display: {{ $i < ($oldData['sppd_jumlah_petugas'] ?? 2) ? 'flex' : 'none' }};">
                    <div>
                        <label class="block text-[10px] font-bold text-secondary mb-1">Nama Petugas {{ $i+1 }}</label>
                        <input type="text" list="list_petugas_sppd" name="sppd_petugas[{{ $i }}][nama]" id="sppd_petugas_nama_{{ $i }}" oninput="onPetugasInput(this, {{ $i }})" value="{{ $oldData['sppd_petugas'][$i]['nama'] ?? '' }}" placeholder="Pilih / Masukkan Nama Petugas" class="input-field w-full p-2 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-secondary mb-1">NIP Petugas {{ $i+1 }} <span class="text-slate-400 font-normal">(Kosongkan jika Tenaga Ahli)</span></label>
                        <input type="text" name="sppd_petugas[{{ $i }}][nip]" id="sppd_petugas_nip_{{ $i }}" value="{{ $oldData['sppd_petugas'][$i]['nip'] ?? '' }}" placeholder="NIP Petugas (Opsional)" class="input-field w-full p-2 rounded-lg text-sm font-mono">
                        <input type="hidden" name="sppd_petugas[{{ $i }}][status_pegawai]" id="sppd_petugas_status_{{ $i }}" value="{{ $oldData['sppd_petugas'][$i]['status_pegawai'] ?? 'ASN' }}">
                    </div>
                </div>
                @endfor
            </div>

            <div class="border-t border-divider pt-4 mt-4">
                <div class="flex justify-between items-end mb-2">
                    <label class="block text-[11px] font-bold text-secondary">Catatan Mentah (Hasil Perjalanan)</label>
                    <label class="flex items-center gap-2 cursor-pointer bg-slate-500/5 px-2 py-1 rounded border border-divider hover:bg-slate-500/10 transition">
                        <input type="checkbox" name="bypass_ai" value="1" {{ (isset($draft) && $draft->is_manual_edit) ? 'checked' : '' }} class="w-3 h-3 text-blue-600 rounded border-slate-300">
                        <span class="text-[10px] font-bold text-primary">Bypass AI (Manual)</span>
                    </label>
                </div>
                <textarea name="sppd_hasil" rows="5" class="input-field w-full p-3 rounded-xl text-sm leading-relaxed" placeholder="Tuliskan poin penting, siapa yang ditemui, dan hasil koordinasinya... AI akan menjadikannya narasi 5W1H.">{{ $oldData['sppd_hasil'] ?? '' }}</textarea>
            </div>

            <div class="border-t border-divider pt-6 mt-6">
                <label class="block text-[11px] font-bold text-secondary mb-3 uppercase tracking-widest">Lampiran Dokumentasi Perjalanan</label>
                
                <div class="mb-5">
                    <label class="block text-[11px] font-bold text-secondary mb-1">Judul Halaman Dokumentasi</label>
                    <input type="text" name="sppd_judul_dokumentasi" value="{{ $oldData['sppd_judul_dokumentasi'] ?? '' }}" placeholder="Contoh: DOKUMENTASI PERJALANAN DINAS KONSULTASI PROGRAM..." class="input-field w-full p-2.5 rounded-xl text-sm transition">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="bg-sub-card p-4 border-dashed border-divider rounded-xl transition hover:border-blue-400">
                        <label class="font-bold text-[10px] text-primary block mb-2 uppercase">Foto Dokumentasi 1 (4:3):</label>
                        <input type="file" name="sppd_foto_1" accept="image/jpeg, image/png, image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                        @if(isset($oldData['sppd_foto_1_path'])) <p class="text-[10px] text-green-600 mt-2 font-semibold">✓ Tersimpan</p> @endif
                    </div>
                    <div class="bg-sub-card p-4 border-dashed border-divider rounded-xl transition hover:border-blue-400">
                        <label class="font-bold text-[10px] text-primary block mb-2 uppercase">Foto Dokumentasi 2 (4:3):</label>
                        <input type="file" name="sppd_foto_2" accept="image/jpeg, image/png, image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                        @if(isset($oldData['sppd_foto_2_path'])) <p class="text-[10px] text-green-600 mt-2 font-semibold">✓ Tersimpan</p> @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== FORM MINGGUAN ===================== -->
        <div id="form_mingguan" class="space-y-8 {{ $jenisDokumen === 'Laporan Mingguan' ? '' : 'hidden' }}">
            <div class="bg-blue-500/5 p-4 rounded-2xl border border-blue-500/20">
                <p class="text-xs text-blue-700 dark:text-blue-400 font-bold flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Format Naskah Dinas Resmi (Poin A–E + Tabel Kegiatan + Lampiran Dokumentasi Grid 2 Kolom)
                </p>
            </div>

            @php $hariArr = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']; @endphp
            @foreach($hariArr as $idx => $hari)
            <div class="bg-sub-card rounded-2xl p-5 md:p-6 relative pt-8 border border-divider">
                <div class="absolute top-0 left-6 -translate-y-1/2 day-label px-4 py-1 rounded-full text-xs font-black uppercase tracking-wider shadow-sm">
                    {{ $hari }}
                </div>
                
                <input type="hidden" id="hari_nama_{{$idx}}" name="mingguan[{{$idx}}][hari]" value="{{$hari}}">
                
                <div id="container_kegiatan_{{$idx}}">
                    @php
                        $kegiatans = isset($oldData['mingguan'][$idx]['kegiatan']) ? $oldData['mingguan'][$idx]['kegiatan'] : [0 => []];
                    @endphp

                    @foreach($kegiatans as $kegIdx => $keg)
                    <div class="{{ $kegIdx > 0 ? 'mt-6 pt-6 border-t border-dashed border-slate-300 relative' : '' }}">
                        @if($kegIdx > 0)
                            <button type="button" onclick="this.parentElement.remove()" class="absolute -top-3 right-0 bg-red-100 hover:bg-red-200 text-red-600 px-2 py-1 rounded text-[10px] font-bold transition">Hapus Kegiatan Ini</button>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-[11px] font-bold text-secondary mb-1">Judul Kegiatan {{ $kegIdx + 1 }} <span class="text-slate-400 font-normal">(Keterangan Foto Lampiran)</span></label>
                                <input type="text" name="mingguan[{{$idx}}][kegiatan][{{$kegIdx}}][judul]" value="{{ $keg['judul'] ?? '' }}" placeholder="Kosongkan agar AI membuat judul otomatis..." class="input-field w-full p-2.5 rounded-xl text-sm">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-secondary mb-1">Lokasi</label>
                                <input type="text" name="mingguan[{{$idx}}][kegiatan][{{$kegIdx}}][lokasi]" value="{{ $keg['lokasi'] ?? '' }}" placeholder="Contoh: Ruang Rapat / Kantor Diskominfo" class="input-field w-full p-2.5 rounded-xl text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-[11px] font-bold text-secondary mb-1">Tanggal</label>
                                <input type="date" name="mingguan[{{$idx}}][kegiatan][{{$kegIdx}}][tanggal]" value="{{ $keg['tanggal'] ?? '' }}" class="input-field w-full p-2 rounded-xl text-sm">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-secondary mb-1">Waktu Mulai</label>
                                <input type="time" name="mingguan[{{$idx}}][kegiatan][{{$kegIdx}}][waktu_mulai]" value="{{ $keg['waktu_mulai'] ?? '08:00' }}" class="input-field w-full p-2 rounded-xl text-sm">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-secondary mb-1">Waktu Selesai</label>
                                <input type="time" name="mingguan[{{$idx}}][kegiatan][{{$kegIdx}}][waktu_selesai]" value="{{ $keg['waktu_selesai'] ?? '' }}" class="input-field w-full p-2 rounded-xl text-sm">
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex justify-between items-end mb-2">
                                <label class="block text-[11px] font-bold text-secondary">Uraian Kegiatan (Catatan Mentah)</label>
                                <label class="flex items-center gap-2 cursor-pointer bg-slate-500/5 px-2 py-1 rounded border border-divider hover:bg-slate-500/10 transition">
                                    <input type="checkbox" name="mingguan[{{$idx}}][kegiatan][{{$kegIdx}}][bypass_ai]" value="1" {{ !empty($keg['bypass_ai']) ? 'checked' : '' }} class="w-3 h-3 text-blue-600 rounded border-slate-300">
                                    <span class="text-[10px] font-bold text-primary">Bypass AI (Manual)</span>
                                </label>
                            </div>
                            <textarea name="mingguan[{{$idx}}][kegiatan][{{$kegIdx}}][hasil_kegiatan]" rows="3" class="input-field w-full p-3 rounded-xl text-sm" placeholder="Catat poin penting kegiatan yang dilaksanakan...">{{ $keg['hasil_kegiatan'] ?? '' }}</textarea>
                        </div>

                        <div class="mb-5">
                            <label class="block text-[11px] font-bold text-secondary mb-1">Hasil / Output Kegiatan <span class="text-slate-400 font-normal">(Opsional — kosongkan agar dirangkum AI ke kolom tabel)</span></label>
                            <input type="text" name="mingguan[{{$idx}}][kegiatan][{{$kegIdx}}][output]" value="{{ $keg['output'] ?? '' }}" placeholder="Contoh: Tersusunnya daftar pemeriksaan dan pembagian tugas tim." class="input-field w-full p-2.5 rounded-xl text-sm">
                        </div>

                        <div class="border-t border-divider pt-4">
                            <p class="text-[10px] font-bold text-secondary uppercase tracking-widest mb-2">Foto Lampiran Dokumentasi Kegiatan {{ $kegIdx + 1 }} <span class="text-slate-400 font-normal">(Masuk ke Grid 2 Kolom Halaman Lampiran)</span></p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-primary mb-1">Foto Dokumentasi 1</label>
                                    <input type="file" name="foto_mingguan_{{$idx}}_{{$kegIdx}}_1" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                                    @if(isset($keg['foto_1_path'])) <p class="text-[10px] text-green-600 mt-1 font-semibold">✓ Tersimpan</p> @endif
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-primary mb-1">Foto Dokumentasi 2 <span class="text-slate-400 font-normal">(Opsional)</span></label>
                                    <input type="file" name="foto_mingguan_{{$idx}}_{{$kegIdx}}_2" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                                    @if(isset($keg['foto_2_path'])) <p class="text-[10px] text-green-600 mt-1 font-semibold">✓ Tersimpan</p> @endif
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-primary mb-1">Foto Dokumentasi 3 <span class="text-slate-400 font-normal">(Opsional)</span></label>
                                    <input type="file" name="foto_mingguan_{{$idx}}_{{$kegIdx}}_3" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                                    @if(isset($keg['foto_3_path'])) <p class="text-[10px] text-green-600 mt-1 font-semibold">✓ Tersimpan</p> @endif
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-primary mb-1">Foto Dokumentasi 4 <span class="text-slate-400 font-normal">(Opsional)</span></label>
                                    <input type="file" name="foto_mingguan_{{$idx}}_{{$kegIdx}}_4" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                                    @if(isset($keg['foto_4_path'])) <p class="text-[10px] text-green-600 mt-1 font-semibold">✓ Tersimpan</p> @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <div class="mt-4 text-center">
                    <button type="button" onclick="tambahKegiatan({{$idx}})" class="inline-flex items-center gap-1.5 bg-blue-500/10 hover:bg-blue-500/20 text-blue-600 font-bold px-3 py-1.5 rounded-lg text-xs transition">
                        + Tambah Kegiatan Lain di Hari {{ $hari }}
                    </button>
                </div>
            </div>
            @endforeach

            <!-- Bagian D: Kendala dan Tindak Lanjut (Opsional) -->
            <div class="bg-sub-card rounded-2xl p-5 md:p-6 border border-divider space-y-4">
                <div class="flex items-center justify-between border-b border-divider pb-3">
                    <div>
                        <h3 class="text-xs font-black text-primary uppercase tracking-wide">D. Kendala dan Tindak Lanjut Mingguan</h3>
                        <p class="text-[11px] text-secondary mt-0.5">Opsional — jika dikosongkan, AI akan menyusun bagian ini secara otomatis berdasarkan kegiatan di atas.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1">1. Catatan Kendala yang Ditemukan</label>
                    <textarea name="mingguan_kendala" id="mingguan_kendala" rows="2" class="input-field w-full p-3 rounded-xl text-sm" placeholder="Contoh: Ditemukan dua tautan menu website yang tidak mengarah ke halaman tujuan (kosongkan jika tidak ada)...">{{ $oldData['mingguan_kendala'] ?? ($oldData['mingguan_ringkasan']['kendala'] ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1">2. Rencana Tindak Lanjut</label>
                    <textarea name="mingguan_tindak_lanjut" id="mingguan_tindak_lanjut" rows="2" class="input-field w-full p-3 rounded-xl text-sm" placeholder="Contoh: Melaksanakan pemeriksaan tautan secara berkala dan pencadangan basis data rutin...">{{ $oldData['mingguan_tindak_lanjut'] ?? ($oldData['mingguan_ringkasan']['tindak_lanjut'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <!-- ===================== FORM NOTULENSI ===================== -->
        <div id="form_notulensi" class="space-y-6 hidden bg-sub-card rounded-2xl p-5 md:p-6 border border-divider">
            <div>
                <label class="block text-[11px] font-bold text-secondary mb-1">Judul / Acara Rapat</label>
                <input type="text" name="notulen_judul" value="{{ $oldData['notulen_judul'] ?? '' }}" placeholder="Contoh: Rapat Koordinasi Penyusunan Dokumen SPBE" class="input-field w-full p-2.5 rounded-xl text-sm">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1">Tanggal</label>
                    <input type="date" name="notulen_tanggal" value="{{ $oldData['notulen_tanggal'] ?? date('Y-m-d') }}" class="input-field w-full p-2.5 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1">Waktu Mulai</label>
                    <input type="time" name="notulen_waktu_mulai" value="{{ $oldData['notulen_waktu_mulai'] ?? '09:00' }}" class="input-field w-full p-2.5 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1">Waktu Selesai <span class="text-gray-400 font-normal">(opsional)</span></label>
                    <input type="time" name="notulen_waktu_selesai" value="{{ $oldData['notulen_waktu_selesai'] ?? '' }}" class="input-field w-full p-2.5 rounded-xl text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1">Pimpinan Rapat (Ketua)</label>
                    <input type="text" name="notulen_ketua" value="{{ $oldData['notulen_ketua'] ?? '' }}" class="input-field w-full p-2.5 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1">Sekretaris</label>
                    <input type="text" name="notulen_sekretaris" value="{{ $oldData['notulen_sekretaris'] ?? '' }}" class="input-field w-full p-2.5 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1">Pencatat (Notulis)</label>
                    <input type="text" name="notulen_pencatat" value="{{ $oldData['notulen_pencatat'] ?? '' }}" class="input-field w-full p-2.5 rounded-xl text-sm">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-secondary mb-1">Daftar Hadir</label>
                <textarea name="notulen_peserta" rows="3" class="input-field w-full p-3 rounded-xl text-sm leading-relaxed" placeholder="1. Bapak A&#10;2. Ibu B">{{ $oldData['notulen_peserta'] ?? '' }}</textarea>
            </div>

            <div>
                <div class="flex justify-between items-end mb-2">
                    <label class="block text-[11px] font-bold text-secondary">Isi Pembahasan & Keputusan</label>
                    <label class="flex items-center gap-2 cursor-pointer bg-slate-500/5 px-2 py-1 rounded border border-divider hover:bg-slate-500/10 transition">
                        <input type="checkbox" name="bypass_ai" value="1" {{ (isset($draft) && $draft->is_manual_edit) ? 'checked' : '' }} class="w-3 h-3 text-blue-600 rounded border-slate-300">
                        <span class="text-[10px] font-bold text-primary">Bypass AI (Manual)</span>
                    </label>
                </div>
                <textarea name="notulen_hasil" rows="5" class="input-field w-full p-3 rounded-xl text-sm leading-relaxed" placeholder="Tuliskan jalannya rapat di sini...">{{ $oldData['notulen_hasil'] ?? '' }}</textarea>
            </div>

            <div class="border-t border-divider pt-4">
                <label class="block text-[10px] font-bold text-secondary mb-3 uppercase tracking-widest">Foto Dokumentasi Notulensi Rapat <span class="text-slate-400 font-normal">(Maksimal 2 Foto)</span></label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="bg-sub-card p-4 border-dashed border-divider rounded-xl transition hover:border-blue-400">
                        <label class="font-bold text-[10px] text-primary block mb-2 uppercase">Foto Dokumentasi 1:</label>
                        <input type="file" name="notulen_foto_1" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                        @if(isset($oldData['notulen_foto_1_path'])) <p class="text-[10px] text-green-600 mt-2 font-semibold">✓ Tersimpan</p> @endif
                    </div>
                    <div class="bg-sub-card p-4 border-dashed border-divider rounded-xl transition hover:border-blue-400">
                        <label class="font-bold text-[10px] text-primary block mb-2 uppercase">Foto Dokumentasi 2 <span class="text-slate-400 font-normal">(Opsional)</span>:</label>
                        <input type="file" name="notulen_foto_2" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                        @if(isset($oldData['notulen_foto_2_path'])) <p class="text-[10px] text-green-600 mt-2 font-semibold">✓ Tersimpan</p> @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- ===================== TOMBOL AKSI BERDASARKAN ROLE & JENIS ===================== -->
        <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-divider">
            <!-- Tombol Utama: Generate & Pratinjau Dokumen -->
            <button type="button" id="btn-generate-preview" class="w-full sm:w-1/2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 px-6 rounded-xl shadow-lg shadow-blue-600/30 text-sm flex items-center justify-center gap-2 transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                Generate & Pratinjau Dokumen
            </button>
            
            <!-- Tombol Sekunder: Simpan Laporan Harian / Ajukan ke Kabid -->
            @if($jenisDokumen === 'Laporan Harian')
                <button type="submit" id="btn-submit-direct" name="action" value="approved" class="w-full sm:w-1/2 bg-slate-500/10 hover:bg-slate-500/20 border border-slate-500/30 text-primary font-bold py-3.5 px-6 rounded-xl transition text-sm flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Simpan Laporan Harian
                </button>
            @elseif(in_array(Auth::user()->role, ['admin_bidang', 'admin', 'super_admin']))
                <button type="submit" id="btn-submit-direct" name="action" value="approved" class="w-full sm:w-1/2 bg-slate-500/10 hover:bg-slate-500/20 border border-slate-500/30 text-primary font-bold py-3.5 px-6 rounded-xl transition text-sm flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Finalisasi & Setujui Langsung
                </button>
            @else
                <button type="submit" id="btn-submit-direct" name="action" value="pending" class="w-full sm:w-1/2 bg-slate-500/10 hover:bg-slate-500/20 border border-slate-500/30 text-primary font-bold py-3.5 px-6 rounded-xl transition text-sm flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    Ajukan Langsung ke Admin Bidang
                </button>
            @endif
        </div>
    </form>
</div>

<!-- ========================================== -->
<!-- MODAL POP-UP PRATINJAU DOKUMEN WORD -->
<!-- ========================================== -->
<div id="modal-preview-doc" style="position: fixed; inset: 0; z-index: 9999; display: none; align-items: center; justify-content: center; background-color: rgba(2, 6, 23, 0.85); padding: 16px;">
    <div style="width: 100%; max-width: 900px; height: 88vh; max-height: 88vh; display: flex; flex-direction: column; border-radius: 16px; overflow: hidden; background-color: #0f172a; border: 1px solid #334155;">
        
        <!-- Header Modal -->
        <div class="px-6 py-4 border-b border-divider flex items-center justify-between bg-sub-card" style="flex-shrink: 0;">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-600/10 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div>
                    <h3 class="text-sm md:text-base font-black text-primary flex items-center gap-2">
                        Pratinjau Dokumen Word
                        <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-600 border border-amber-500/20">Draf Tersimpan</span>
                    </h3>
                    <p class="text-[11px] text-secondary">Periksa kelayakan narasi dan tata letak sebelum menyimpan/mengajukan</p>
                </div>
            </div>
            
            <button type="button" onclick="closePreviewModal()" class="w-8 h-8 rounded-full bg-slate-500/10 hover:bg-slate-500/20 text-secondary hover:text-primary flex items-center justify-center transition" title="Tutup">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Body Modal (Scrollable Preview Paper) -->
        <div id="preview-content-container" style="flex: 1 1 auto; overflow-y: auto; padding: 24px; background-color: #1e293b;">
            <!-- Konten pratinjau dimasukkan via AJAX -->
        </div>

        <!-- Footer Modal (2 Tombol Aksi) -->
        <div class="px-6 py-4 border-t border-divider bg-sub-card flex flex-col sm:flex-row items-center justify-between gap-3" style="flex-shrink: 0;">
            <p class="text-[11px] text-secondary flex items-center gap-1.5 order-2 sm:order-1">
                <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Draf dokumen telah disimpan aman di database server.
            </p>
            
            <div class="flex items-center gap-3 w-full sm:w-auto order-1 sm:order-2">
                <!-- Tombol 1: Edit Lagi -->
                <button type="button" onclick="closePreviewModal()" class="w-1/2 sm:w-auto px-5 py-2.5 rounded-xl border border-divider text-secondary hover:text-primary hover:bg-slate-500/10 text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Edit Lagi
                </button>

                <!-- Tombol 2: Selesai & Simpan / Ajukan ke Admin Bidang / Finalisasi -->
                @if($jenisDokumen === 'Laporan Harian')
                    <button type="button" id="btn-modal-submit-kabid" onclick="submitToKabidFromModal()" class="w-1/2 sm:w-auto px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-lg shadow-blue-600/30 flex items-center justify-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span id="txt-modal-submit">Selesai & Simpan</span>
                    </button>
                @elseif(in_array(Auth::user()->role, ['admin_bidang', 'admin', 'super_admin']))
                    <button type="button" id="btn-modal-submit-kabid" onclick="submitToKabidFromModal()" class="w-1/2 sm:w-auto px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-lg shadow-blue-600/30 flex items-center justify-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span id="txt-modal-submit">Finalisasi & Setujui</span>
                    </button>
                @else
                    <button type="button" id="btn-modal-submit-kabid" onclick="submitToKabidFromModal()" class="w-1/2 sm:w-auto px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-lg shadow-blue-600/30 flex items-center justify-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        <span id="txt-modal-submit">Ajukan ke Admin Bidang</span>
                    </button>
                @endif
            </div>
        </div>

    </div>
</div>

<!-- ========================================== -->
<!-- MODAL LOADING OVERLAY -->
<!-- ========================================== -->
<div id="loading-overlay" style="display: none; z-index: 99999;" class="fixed inset-0 items-center justify-center bg-slate-900/80 backdrop-blur-md">
    <div class="bg-white dark:bg-slate-800 p-8 rounded-3xl shadow-2xl flex flex-col items-center max-w-sm w-full mx-4 border border-slate-200 dark:border-slate-700">
        <svg class="animate-spin h-12 w-12 text-blue-600 mb-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <h3 id="loading-title" class="text-lg font-black text-slate-900 dark:text-white text-center mb-2">Memproses Dokumen...</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 text-center leading-relaxed">
            Harap tunggu sebentar. Sistem sedang merangkum narasi dengan Gemini AI dan membuat file dokumen Word (.docx).
        </p>
    </div>
</div>

<script>
    let currentPreviewReportId = {{ isset($draft) ? $draft->id : 'null' }};
    let latestAiFormData = null;

    const usersDbList = @json($usersDbList);
    const defaultKabid = {
        nama: @json($defaultKabidNama),
        nip: @json($defaultKabidNip),
        jabatan: @json($defaultKabidJabatan)
    };

    function onPetugasInput(inputEl, index) {
        const val = (inputEl.value || '').trim().toLowerCase();
        const nipEl = document.getElementById(`sppd_petugas_nip_${index}`);
        const statusEl = document.getElementById(`sppd_petugas_status_${index}`);
        if (!nipEl) return;

        const match = usersDbList.find(u => u.name && u.name.trim().toLowerCase() === val);
        if (match) {
            if (match.status_pegawai === 'Tenaga Ahli') {
                nipEl.value = '';
                if (statusEl) statusEl.value = 'Tenaga Ahli';
            } else {
                nipEl.value = match.nip || '';
                if (statusEl) statusEl.value = 'ASN';
            }
        }
    }

    function onTtdNamaInput(inputEl) {
        const val = (inputEl.value || '').trim().toLowerCase();
        const nipEl = document.getElementById('ttd_nip');
        const statusEl = document.getElementById('ttd_status_pegawai');
        const jabEl = document.querySelector('input[name="ttd_jabatan"]');
        if (!nipEl) return;

        const match = usersDbList.find(u => u.name && u.name.trim().toLowerCase() === val);
        if (match) {
            if (match.status_pegawai === 'Tenaga Ahli') {
                nipEl.value = '';
                if (statusEl) statusEl.value = 'Tenaga Ahli';
            } else {
                nipEl.value = match.nip || '';
                if (statusEl) statusEl.value = 'ASN';
            }
            if (match.jabatan && jabEl && (!jabEl.value || jabEl.value === 'Kepala Bidang')) {
                jabEl.value = match.jabatan;
            }
        }
    }

    function applyAiFormDataToForm(formData) {
        if (!formData) return;
        
        if (Array.isArray(formData.kegiatan)) {
            formData.kegiatan.forEach((keg, idx) => {
                const ta = document.querySelector(`textarea[name="kegiatan[${idx}][hasil_kegiatan]"]`);
                if (ta && keg.hasil_kegiatan) {
                    ta.value = keg.hasil_kegiatan;
                }
                const inputJudul = document.querySelector(`input[name="kegiatan[${idx}][judul]"]`);
                if (inputJudul && keg.judul) {
                    inputJudul.value = keg.judul;
                }
            });
        }

        if (formData.mingguan && typeof formData.mingguan === 'object') {
            Object.keys(formData.mingguan).forEach(hIdx => {
                const hari = formData.mingguan[hIdx];
                if (hari && Array.isArray(hari.kegiatan)) {
                    hari.kegiatan.forEach((keg, kIdx) => {
                        const inputJudul = document.querySelector(`input[name="mingguan[${hIdx}][kegiatan][${kIdx}][judul]"]`);
                        if (inputJudul && keg.judul) {
                            inputJudul.value = keg.judul;
                        }
                    });
                }
            });
        }

        if (formData.mingguan_ringkasan && typeof formData.mingguan_ringkasan === 'object') {
            const ring = formData.mingguan_ringkasan;
            if (Array.isArray(ring.tabel_kegiatan)) {
                ring.tabel_kegiatan.forEach(row => {
                    const hIdx = row.hari_idx;
                    const kIdx = row.keg_idx;
                    if (hIdx !== undefined && kIdx !== undefined) {
                        const inputJudul = document.querySelector(`input[name="mingguan[${hIdx}][kegiatan][${kIdx}][judul]"]`);
                        if (inputJudul && !inputJudul.value && row.judul) {
                            inputJudul.value = row.judul;
                        }
                        const inputOutput = document.querySelector(`input[name="mingguan[${hIdx}][kegiatan][${kIdx}][output]"]`);
                        if (inputOutput && !inputOutput.value && row.output) {
                            inputOutput.value = row.output;
                        }
                    }
                });
            }
            const taKendala = document.getElementById('mingguan_kendala');
            if (taKendala && !taKendala.value.trim() && ring.kendala) {
                taKendala.value = ring.kendala;
            }
            const taTindak = document.getElementById('mingguan_tindak_lanjut');
            if (taTindak && !taTindak.value.trim() && ring.tindak_lanjut) {
                taTindak.value = ring.tindak_lanjut;
            }
        }

        if (formData.notulen_hasil) {
            const taNotulen = document.querySelector('textarea[name="notulen_hasil"]');
            if (taNotulen) taNotulen.value = formData.notulen_hasil;
        }
        if (formData.notulen_judul) {
            const inpJudul = document.querySelector('input[name="notulen_judul"]');
            if (inpJudul) inpJudul.value = formData.notulen_judul;
        }

        if (formData.sppd_hasil) {
            const taSppd = document.querySelector('textarea[name="sppd_hasil"]');
            if (taSppd) taSppd.value = formData.sppd_hasil;
        }
        if (formData.sppd_maksud) {
            const inpMaksud = document.querySelector('input[name="sppd_maksud"]');
            if (inpMaksud) inpMaksud.value = formData.sppd_maksud;
        }

        if (formData.pakai_ttd) {
            const pakaiTtdEl = document.getElementById('pakai_ttd');
            if (pakaiTtdEl) pakaiTtdEl.value = formData.pakai_ttd;
        }
        if (formData.ttd_jabatan) {
            const jabEl = document.getElementById('ttd_jabatan') || document.querySelector('input[name="ttd_jabatan"]');
            if (jabEl) jabEl.value = formData.ttd_jabatan;
        }
        if (formData.ttd_nama) {
            const namaEl = document.getElementById('ttd_nama');
            if (namaEl) namaEl.value = formData.ttd_nama;
        }
        if (formData.ttd_nip) {
            const nipEl = document.getElementById('ttd_nip');
            if (nipEl) nipEl.value = formData.ttd_nip;
        }
        aturForm();
    }

    function closePreviewModal() {
        const modal = document.getElementById('modal-preview-doc');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
        if (latestAiFormData) {
            applyAiFormDataToForm(latestAiFormData);
        }
    }

    function submitToKabidFromModal() {
        const reportId = currentPreviewReportId || document.getElementById('report_id_field')?.value;
        if (!reportId) {
            alert('ID Dokumen belum tersedia.');
            return;
        }

        const jenis = document.getElementById("pilih_jenis")?.value || '';
        const isHarian = (jenis === 'Laporan Harian');

        const btn = document.getElementById('btn-modal-submit-kabid');
        const txt = document.getElementById('txt-modal-submit');
        if (btn) btn.disabled = true;
        if (txt) txt.innerText = 'Memproses...';

        const overlay = document.getElementById('loading-overlay');
        const loadingTitle = document.getElementById('loading-title');
        if (overlay && loadingTitle) {
            loadingTitle.innerText = isHarian ? "Menyimpan Laporan Harian..." : "Mengirimkan status verifikasi laporan...";
            overlay.style.display = 'flex';
        }

        const submitKabidUrl = `/report/${reportId}/submit-kabid`;

        fetch(submitKabidUrl, {
            method: 'POST',
            body: JSON.stringify({ status: isHarian ? 'approved' : 'pending' }),
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(async res => {
            const contentType = res.headers.get('content-type') || '';
            let data;
            if (contentType.includes('application/json')) {
                data = await res.json();
            } else {
                const text = await res.text();
                try { data = JSON.parse(text); } catch(e) { data = { success: false, message: text }; }
            }
            if (!res.ok || data.success === false) {
                throw new Error(data.message || ('Error ' + res.status));
            }
            return data;
        })
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect_url;
            } else {
                alert(data.message || 'Gagal memproses laporan.');
                if (overlay) overlay.style.display = 'none';
                if (btn) btn.disabled = false;
                if (txt) txt.innerText = isHarian ? 'Selesai & Simpan' : 'Ajukan ke Admin Bidang';
            }
        })
        .catch(err => {
            console.error('Error submit to admin bidang:', err);
            alert('Gagal memproses laporan:\n' + (err.message || 'Terjadi kesalahan sistem'));
            if (overlay) overlay.style.display = 'none';
            if (btn) btn.disabled = false;
            if (txt) txt.innerText = isHarian ? 'Selesai & Simpan' : 'Ajukan ke Admin Bidang';
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        aturForm();
        aturJumlahPetugas();

        const form = document.getElementById('reportForm');
        const overlay = document.getElementById('loading-overlay');
        const loadingTitle = document.getElementById('loading-title');
        let isSubmitting = false; 

        const btnGeneratePreview = document.getElementById('btn-generate-preview');
        if (btnGeneratePreview && form) {
            btnGeneratePreview.addEventListener('click', function(e) {
                e.preventDefault();

                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                if (overlay && loadingTitle) {
                    loadingTitle.innerText = "Sedang Merangkum AI & Men-generate Pratinjau Dokumen...";
                    overlay.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                }

                const previewUrl = "{{ route('report.generate', [], false) }}";
                const formData = new FormData(form);
                formData.set('action', 'preview');

                if (formData.get('pakai_ttd') === 'tidak') {
                    formData.set('ttd_nama', '');
                    formData.set('ttd_nip', '');
                    formData.set('ttd_jabatan', '');
                    formData.set('ttd_status_pegawai', '');
                }

                const currentId = currentPreviewReportId || document.getElementById('report_id_field')?.value;
                if (currentId) {
                    formData.set('report_id', currentId);
                }

                fetch(previewUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(async res => {
                    const contentType = res.headers.get('content-type') || '';
                    let data;
                    if (contentType.includes('application/json')) {
                        data = await res.json();
                    } else {
                        const rawText = await res.text();
                        try {
                            data = JSON.parse(rawText);
                        } catch(e) {
                            data = { success: false, message: rawText || ('HTTP Error ' + res.status) };
                        }
                    }

                    if (!res.ok || data.success === false) {
                        let errMsg = data.message || ('Terjadi kesalahan pada server (Kode: ' + res.status + ')');
                        if (data.errors) {
                            const errList = Object.values(data.errors).flat().join('\n');
                            errMsg += '\n' + errList;
                        }
                        throw new Error(errMsg);
                    }

                    return data;
                })
                .then(data => {
                    if (overlay) overlay.style.display = 'none';

                    if (data.success) {
                        currentPreviewReportId = data.report_id;
                        latestAiFormData = data.form_data || null;

                        if (latestAiFormData) {
                            applyAiFormDataToForm(latestAiFormData);
                        }

                        let reportIdField = document.getElementById('report_id_field');
                        if (!reportIdField) {
                            reportIdField = form.querySelector('input[name="report_id"]');
                        }
                        if (reportIdField) {
                            reportIdField.value = data.report_id;
                        }

                        if (window.history && window.history.replaceState && !window.location.pathname.includes('/edit/')) {
                            window.history.replaceState(null, '', `/report/edit/${data.report_id}`);
                        }

                        const container = document.getElementById('preview-content-container');
                        if (container) {
                            container.innerHTML = data.preview_html;
                        }

                        const modal = document.getElementById('modal-preview-doc');
                        if (modal) {
                            modal.style.display = 'flex';
                            document.body.style.overflow = 'hidden';
                        }
                    } else {
                        document.body.style.overflow = '';
                        alert(data.message || 'Gagal memproses dokumen.');
                    }
                })
                .catch(err => {
                    console.error('Error generate preview:', err);
                    if (overlay) overlay.style.display = 'none';
                    document.body.style.overflow = '';
                    alert('Gagal memproses dokumen:\n' + (err.message || 'Terjadi kesalahan sistem.'));
                });
            });
        }

        const modalPreview = document.getElementById('modal-preview-doc');
        if (modalPreview) {
            modalPreview.addEventListener('click', function(e) {
                if (e.target === modalPreview) {
                    closePreviewModal();
                }
            });
        }

        if (form && overlay) {
            const submitBtns = form.querySelectorAll('button[type="submit"]');

            submitBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    if (form.checkValidity()) {
                        e.preventDefault(); 

                        if (isSubmitting) return;
                        isSubmitting = true;

                        const jenis = document.getElementById("pilih_jenis")?.value || '';
                        if (jenis === 'Laporan Harian') {
                            loadingTitle.innerText = "Sedang Merangkum AI & Menyimpan Laporan Harian...";
                        } else if (this.value === 'approved') {
                            loadingTitle.innerText = "Sedang Merangkum AI & Menyetujui Laporan...";
                        } else if (this.value === 'pending') {
                            loadingTitle.innerText = "Sedang Merangkum AI & Mengajukan ke Admin Bidang...";
                        } else {
                            loadingTitle.innerText = "Sedang Merangkum AI & Menyimpan Draf...";
                        }

                        const pakaiTtdEl = document.getElementById('pakai_ttd');
                        if (pakaiTtdEl && pakaiTtdEl.value === 'tidak') {
                            const namaEl = document.getElementById("ttd_nama");
                            const nipEl = document.getElementById("ttd_nip");
                            const jabEl = document.getElementById("ttd_jabatan") || document.querySelector('input[name="ttd_jabatan"]');
                            const statEl = document.getElementById('ttd_status_pegawai');
                            if (namaEl) namaEl.value = '';
                            if (nipEl) nipEl.value = '';
                            if (jabEl) jabEl.value = '';
                            if (statEl) statEl.value = '';
                        }

                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = this.name;
                        hiddenInput.value = this.value;
                        form.appendChild(hiddenInput);

                        overlay.style.display = 'flex';
                        document.body.style.overflow = 'hidden';

                        setTimeout(() => {
                            form.submit();
                        }, 150);
                    }
                });
            });
        }
    });

    function aturForm() {
        let jenis = document.getElementById("pilih_jenis").value;

        document.getElementById("form_mingguan").style.display = "none";
        document.getElementById("form_notulensi").style.display = "none";
        document.getElementById("form_perjalanan_dinas").style.display = "none";
        document.getElementById("form_harian").style.display = "none";

        const wrapKopTtdRow = document.getElementById("wrap_kop_ttd_row");
        const wrapKopSelect = document.getElementById("wrap_kop_select");
        const wrapTtdSelect = document.getElementById("wrap_ttd_select");
        const wrapTtdDetail = document.getElementById("wrap_ttd_detail");
        const pakaiKopEl = document.getElementById("pakai_kop");
        const pakaiTtdEl = document.getElementById("pakai_ttd");
        const btnSubmitDirect = document.getElementById("btn-submit-direct");
        const btnModalSubmit = document.getElementById("btn-modal-submit-kabid");
        const userRole = "{{ Auth::user()->role }}";

        if (wrapKopTtdRow) wrapKopTtdRow.style.display = "grid";
        if (wrapTtdSelect) wrapTtdSelect.style.display = "block";

        if (jenis === "Laporan Harian") {
            if (wrapKopSelect) wrapKopSelect.style.display = "none";
            if (pakaiKopEl) pakaiKopEl.value = "tidak";
        } else {
            if (wrapKopSelect) wrapKopSelect.style.display = "block";
        }

        let ttd = pakaiTtdEl ? pakaiTtdEl.value : "tidak";
        if (wrapTtdDetail) {
            wrapTtdDetail.style.display = (ttd === "ya") ? "block" : "none";
        }
        if (ttd === "ya") {
            const namaEl = document.getElementById("ttd_nama");
            const nipEl = document.getElementById("ttd_nip");
            const jabEl = document.getElementById("ttd_jabatan") || document.querySelector('input[name="ttd_jabatan"]');
            if (namaEl && !namaEl.value.trim() && defaultKabid.nama) {
                namaEl.value = defaultKabid.nama;
            }
            if (nipEl && !nipEl.value.trim() && defaultKabid.nip) {
                nipEl.value = defaultKabid.nip;
            }
            if (jabEl && (!jabEl.value.trim() || jabEl.value === 'Kepala Bidang') && defaultKabid.jabatan) {
                jabEl.value = defaultKabid.jabatan;
            }
        }

        if (jenis === "Laporan Harian") {
            if (btnSubmitDirect) {
                btnSubmitDirect.value = "approved";
                btnSubmitDirect.className = "w-full sm:w-1/2 bg-slate-500/10 hover:bg-slate-500/20 border border-slate-500/30 text-primary font-bold py-3.5 px-6 rounded-xl transition text-sm flex items-center justify-center gap-2 cursor-pointer";
                btnSubmitDirect.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Simpan Laporan Harian`;
            }
            if (btnModalSubmit) {
                btnModalSubmit.className = "w-1/2 sm:w-auto px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-lg shadow-blue-600/30 flex items-center justify-center gap-1.5 cursor-pointer";
                btnModalSubmit.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> <span id="txt-modal-submit">Selesai & Simpan</span>`;
            }
        } else {
            if (btnSubmitDirect) {
                btnSubmitDirect.className = "w-full sm:w-1/2 bg-slate-500/10 hover:bg-slate-500/20 border border-slate-500/30 text-primary font-bold py-3.5 px-6 rounded-xl transition text-sm flex items-center justify-center gap-2 cursor-pointer";
                if (['admin_bidang', 'admin', 'super_admin'].includes(userRole)) {
                    btnSubmitDirect.value = "approved";
                    btnSubmitDirect.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Finalisasi & Setujui Langsung`;
                } else {
                    btnSubmitDirect.value = "pending";
                    btnSubmitDirect.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg> Ajukan Langsung ke Admin Bidang`;
                }
            }
            if (btnModalSubmit) {
                btnModalSubmit.className = "w-1/2 sm:w-auto px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-lg shadow-blue-600/30 flex items-center justify-center gap-1.5 cursor-pointer";
                if (['admin_bidang', 'admin', 'super_admin'].includes(userRole)) {
                    btnModalSubmit.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> <span id="txt-modal-submit">Finalisasi & Setujui</span>`;
                } else {
                    btnModalSubmit.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg> <span id="txt-modal-submit">Ajukan ke Admin Bidang</span>`;
                }
            }
        }

        if (jenis === "Notulensi Rapat") {
            document.getElementById("wrap_bidang").style.display = "none";
        } else {
            document.getElementById("wrap_bidang").style.display = "block";
        }

        if (jenis === "Laporan Mingguan") {
            document.getElementById("form_mingguan").style.display = "block";
        } else if (jenis === "Notulensi Rapat") {
            document.getElementById("form_notulensi").style.display = "block";
        } else if (jenis === "Laporan Perjalanan Dinas") {
            document.getElementById("form_perjalanan_dinas").style.display = "block";
        } else if (jenis === "Laporan Harian") {
            document.getElementById("form_harian").style.display = "block";
        }
    }

    function aturJumlahPetugas() {
        let el = document.getElementById("sppd_jumlah_petugas");
        if (!el) return;
        let jumlah = parseInt(el.value);
        for(let i = 0; i < 6; i++) {
            let row = document.getElementById(`petugas_row_${i}`);
            if(row) { row.style.display = (i < jumlah) ? "flex" : "none"; }
        }
    }

    function tambahKegiatan(hariIdx) {
        let container = document.getElementById(`container_kegiatan_${hariIdx}`);
        let kegIdx = container.children.length;
        let html = `
        <div class="mt-6 pt-6 border-t border-dashed border-slate-300 relative">
            <button type="button" onclick="this.parentElement.remove()" class="absolute -top-3 right-0 bg-red-100 hover:bg-red-200 text-red-600 px-2 py-1 rounded text-[10px] font-bold transition">Hapus Kegiatan Ini</button>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1">Judul Kegiatan ${kegIdx + 1} <span class="text-slate-400 font-normal">(Keterangan Foto Lampiran)</span></label>
                    <input type="text" name="mingguan[${hariIdx}][kegiatan][${kegIdx}][judul]" placeholder="Kosongkan agar AI membuat judul otomatis..." class="input-field w-full p-2.5 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1">Lokasi</label>
                    <input type="text" name="mingguan[${hariIdx}][kegiatan][${kegIdx}][lokasi]" placeholder="Contoh: Ruang Rapat / Kantor Diskominfo" class="input-field w-full p-2.5 rounded-xl text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1">Tanggal</label>
                    <input type="date" name="mingguan[${hariIdx}][kegiatan][${kegIdx}][tanggal]" class="input-field w-full p-2 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1">Waktu Mulai</label>
                    <input type="time" name="mingguan[${hariIdx}][kegiatan][${kegIdx}][waktu_mulai]" class="input-field w-full p-2 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1">Waktu Selesai</label>
                    <input type="time" name="mingguan[${hariIdx}][kegiatan][${kegIdx}][waktu_selesai]" class="input-field w-full p-2 rounded-xl text-sm">
                </div>
            </div>

            <div class="mb-4">
                <div class="flex justify-between items-end mb-2">
                    <label class="block text-[11px] font-bold text-secondary">Uraian Kegiatan (Catatan Mentah)</label>
                    <label class="flex items-center gap-2 cursor-pointer bg-slate-500/5 px-2 py-1 rounded border border-divider hover:bg-slate-500/10 transition">
                        <input type="checkbox" name="mingguan[${hariIdx}][kegiatan][${kegIdx}][bypass_ai]" value="1" class="w-3 h-3 text-blue-600 rounded border-slate-300">
                        <span class="text-[10px] font-bold text-primary">Bypass AI (Manual)</span>
                    </label>
                </div>
                <textarea name="mingguan[${hariIdx}][kegiatan][${kegIdx}][hasil_kegiatan]" rows="3" class="input-field w-full p-3 rounded-xl text-sm" placeholder="Catat poin penting kegiatan yang dilaksanakan..."></textarea>
            </div>

            <div class="mb-5">
                <label class="block text-[11px] font-bold text-secondary mb-1">Hasil / Output Kegiatan <span class="text-slate-400 font-normal">(Opsional — kosongkan agar dirangkum AI ke kolom tabel)</span></label>
                <input type="text" name="mingguan[${hariIdx}][kegiatan][${kegIdx}][output]" placeholder="Contoh: Tersusunnya daftar pemeriksaan dan pembagian tugas tim." class="input-field w-full p-2.5 rounded-xl text-sm">
            </div>

            <div class="border-t border-divider pt-4">
                <p class="text-[10px] font-bold text-secondary uppercase tracking-widest mb-2">Foto Lampiran Dokumentasi Kegiatan ${kegIdx + 1} <span class="text-slate-400 font-normal">(Masuk ke Grid 2 Kolom Halaman Lampiran)</span></p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-primary mb-1">Foto Dokumentasi 1</label>
                        <input type="file" name="foto_mingguan_${hariIdx}_${kegIdx}_1" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-primary mb-1">Foto Dokumentasi 2 <span class="text-slate-400 font-normal">(Opsional)</span></label>
                        <input type="file" name="foto_mingguan_${hariIdx}_${kegIdx}_2" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-primary mb-1">Foto Dokumentasi 3 <span class="text-slate-400 font-normal">(Opsional)</span></label>
                        <input type="file" name="foto_mingguan_${hariIdx}_${kegIdx}_3" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-primary mb-1">Foto Dokumentasi 4 <span class="text-slate-400 font-normal">(Opsional)</span></label>
                        <input type="file" name="foto_mingguan_${hariIdx}_${kegIdx}_4" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                    </div>
                </div>
            </div>
        </div>`;
        container.insertAdjacentHTML('beforeend', html);
    }

    function tambahKegiatanHarian() {
        let container = document.getElementById('container_kegiatan_harian');
        let kegIdx = container.children.length;
        let defaultTgl = document.querySelector('input[name="kegiatan[0][tanggal]"]')?.value || "{{ date('Y-m-d') }}";
        
        let html = `
        <div class="mt-6 pt-6 border-t border-dashed border-slate-300 relative">
            <button type="button" onclick="this.parentElement.remove()" class="absolute -top-3 right-0 bg-red-100 hover:bg-red-200 text-red-600 px-2 py-1 rounded text-[10px] font-bold transition">Hapus Kegiatan Ini</button>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1.5">Judul Kegiatan ${kegIdx + 1}:</label>
                    <input type="text" name="kegiatan[${kegIdx}][judul]" placeholder="Kosongkan agar AI membuat judul..." class="input-field w-full p-2.5 rounded-xl text-sm transition">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1.5">Lokasi / Tempat:</label>
                    <input type="text" name="kegiatan[${kegIdx}][lokasi]" placeholder="Contoh: Halaman / Kantor Dinas Kominfo Kab. OKI" class="input-field w-full p-2.5 rounded-xl text-sm transition">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1.5">Tanggal Pelaksanaan:</label>
                    <input type="date" name="kegiatan[${kegIdx}][tanggal]" value="${defaultTgl}" class="input-field w-full p-2.5 rounded-xl text-sm transition">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1.5">Waktu Mulai:</label>
                    <input type="time" name="kegiatan[${kegIdx}][waktu_mulai]" value="08:00" class="input-field w-full p-2.5 rounded-xl text-sm transition">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-1.5">Waktu Selesai: <span class="text-gray-400 font-normal">(opsional)</span></label>
                    <input type="time" name="kegiatan[${kegIdx}][waktu_selesai]" class="input-field w-full p-2.5 rounded-xl text-sm transition">
                </div>
            </div>

            <div class="mb-5">
                <div class="flex justify-between items-end mb-1.5">
                    <label class="block text-[11px] font-bold text-secondary">Catatan Singkat / Poin Pelaksanaan Kegiatan:</label>
                    <label class="flex items-center gap-2 cursor-pointer bg-slate-500/5 px-2.5 py-1.5 rounded border border-divider hover:bg-slate-500/10 transition">
                        <input type="checkbox" name="kegiatan[${kegIdx}][bypass_ai]" value="1" class="w-3.5 h-3.5 text-blue-600 rounded">
                        <span class="text-[10px] font-bold text-primary">Bypass AI (Manual)</span>
                    </label>
                </div>
                <textarea name="kegiatan[${kegIdx}][hasil_kegiatan]" rows="4" placeholder="Ketik poin kegiatan, arahan, atau hasil yang dicapai... AI akan otomatis menyusunnya ke format A–C e-Kinerja." class="input-field w-full p-3 rounded-xl text-sm transition"></textarea>
            </div>

            <div class="border-t border-divider pt-4 mt-4">
                <label class="block text-[10px] font-bold text-secondary mb-4 uppercase tracking-widest">Lampiran Dokumentasi Kegiatan ${kegIdx + 1} <span class="text-slate-400 font-normal">(Masuk ke Grid 2 Kolom Halaman Lampiran)</span></label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="bg-sub-card p-4 border-dashed border-divider rounded-xl transition hover:border-blue-400">
                        <label class="font-bold text-[10px] text-primary block mb-2 uppercase">Foto Dokumentasi 1:</label>
                        <input type="file" name="foto_${kegIdx}_1" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                    </div>
                    <div class="bg-sub-card p-4 border-dashed border-divider rounded-xl transition hover:border-blue-400">
                        <label class="font-bold text-[10px] text-primary block mb-2 uppercase">Foto Dokumentasi 2 <span class="text-slate-400 font-normal">(Opsional)</span>:</label>
                        <input type="file" name="foto_${kegIdx}_2" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                    </div>
                    <div class="bg-sub-card p-4 border-dashed border-divider rounded-xl transition hover:border-blue-400">
                        <label class="font-bold text-[10px] text-primary block mb-2 uppercase">Foto Dokumentasi 3 <span class="text-slate-400 font-normal">(Opsional)</span>:</label>
                        <input type="file" name="foto_${kegIdx}_3" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                    </div>
                    <div class="bg-sub-card p-4 border-dashed border-divider rounded-xl transition hover:border-blue-400">
                        <label class="font-bold text-[10px] text-primary block mb-2 uppercase">Foto Dokumentasi 4 <span class="text-slate-400 font-normal">(Opsional)</span>:</label>
                        <input type="file" name="foto_${kegIdx}_4" accept="image/jpeg,image/png,image/jpg" class="block w-full text-xs text-secondary border border-divider rounded-xl cursor-pointer bg-slate-500/5 focus:outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-[10px] file:font-bold file:bg-blue-500/10 file:text-blue-600 hover:file:bg-blue-500/20 transition">
                    </div>
                </div>
            </div>
        </div>`;
        
        container.insertAdjacentHTML('beforeend', html);
    }
</script>
@endsection