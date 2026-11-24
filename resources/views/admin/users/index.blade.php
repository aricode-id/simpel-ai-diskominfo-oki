@extends('layouts.admin')

@section('page_title', 'Kelola Pengguna')

@section('content')
<div class="space-y-6">
    
    <div class="mb-2">
        <h2 class="text-xl md:text-2xl font-black text-primary">Manajemen Akun & Hak Akses</h2>
        <p class="text-[11px] md:text-xs text-secondary mt-1">
            @if(Auth::user()->role === 'super_admin')
                Kelola seluruh akun pengguna lintas 6 bidang di Diskominfo OKI.
            @else
                Kelola akun Pegawai khusus lingkup {{ Auth::user()->bidang }}.
            @endif
        </p>
    </div>

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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- FORM TAMBAH PENGGUNA -->
        <div class="glass-card rounded-3xl p-5 md:p-6 h-fit order-2 lg:order-1">
            <div class="flex items-center gap-2 mb-6 border-b border-divider pb-4">
                <div class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-600 flex items-center justify-center border border-blue-500/20 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                </div>
                <h2 class="text-base md:text-lg font-black text-primary">Tambah Akun Baru</h2>
            </div>

            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] md:text-[11px] font-bold text-secondary mb-1.5 uppercase tracking-wide">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="input-field w-full px-4 py-2.5 rounded-xl outline-none text-xs md:text-sm transition" placeholder="Contoh: Ahmad Subagja, S.Kom">
                </div>

                <div>
                    <label class="block text-[10px] md:text-[11px] font-bold text-secondary mb-1.5 uppercase tracking-wide">Status Kepegawaian <span class="text-rose-500">*</span></label>
                    <select name="status_pegawai" id="status_pegawai_create" onchange="updateNipFieldState('create')" required class="input-field w-full px-4 py-2.5 rounded-xl outline-none text-xs md:text-sm transition appearance-none">
                        <option value="ASN" {{ old('status_pegawai') === 'ASN' ? 'selected' : '' }} class="text-slate-800">ASN (PNS / PPPK)</option>
                        <option value="Tenaga Ahli" {{ old('status_pegawai') === 'Tenaga Ahli' ? 'selected' : '' }} class="text-slate-800">Tenaga Ahli</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] md:text-[11px] font-bold text-secondary mb-1.5 uppercase tracking-wide">NIP / NIK / ID LOGIN <span class="text-rose-500">*</span></label>
                    <input type="text" name="nip" id="nip_input_create" value="{{ old('nip') }}" minlength="4" maxlength="25" required class="input-field w-full px-4 py-2.5 rounded-xl outline-none text-xs md:text-sm transition font-mono" placeholder="Contoh: TA-001, 111111, 2221-2226, atau 18 digit NIP">
                    <p id="nip_help_create" class="text-[10px] text-blue-500 font-semibold mt-1">Digunakan untuk login dan dicetak pada dokumen resmi (min 4, maks 25 karakter).</p>
                </div>

                <div>
                    <label class="block text-[10px] md:text-[11px] font-bold text-secondary mb-1.5 uppercase tracking-wide">Jabatan</label>
                    <input type="text" name="jabatan" id="jabatan_input_create" value="{{ old('jabatan') }}" class="input-field w-full px-4 py-2.5 rounded-xl outline-none text-xs md:text-sm transition" placeholder="Contoh: Kepala Bidang E-Gov / Pranata Komputer / Tenaga Ahli">
                </div>

                <div>
                    <label class="block text-[10px] md:text-[11px] font-bold text-secondary mb-1.5 uppercase tracking-wide">Email (Opsional)</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="input-field w-full px-4 py-2.5 rounded-xl outline-none text-xs md:text-sm transition" placeholder="Kosongkan jika otomatis (NIP@diskominfo.local)">
                </div>

                <div>
                    <label class="block text-[10px] md:text-[11px] font-bold text-secondary mb-1.5 uppercase tracking-wide">Hak Akses (Role) <span class="text-rose-500">*</span></label>
                    <select name="role" required class="input-field w-full px-4 py-2.5 rounded-xl outline-none text-xs md:text-sm transition appearance-none">
                        <option value="pegawai" class="text-slate-800">Pegawai (Staf Pembuat Laporan)</option>
                        @if(Auth::user()->role === 'super_admin')
                            <option value="admin_bidang" class="text-slate-800">Admin Bidang</option>
                            <option value="super_admin" class="text-slate-800">Super Admin (Seluruh Akses)</option>
                        @endif
                    </select>
                </div>
                
                @if(Auth::user()->role === 'super_admin')
                <div>
                    <label class="block text-[10px] md:text-[11px] font-bold text-secondary mb-1.5 uppercase tracking-wide">Bidang</label>
                    <select name="bidang" class="input-field w-full px-4 py-2.5 rounded-xl outline-none text-xs md:text-sm transition appearance-none">
                        <option value="">-- Tanpa Bidang / Super Admin --</option>
                        @foreach(['Sekretariat', 'Statistik', 'PKP', 'TIK', 'E-Gov', 'Persandian'] as $b)
                            <option value="{{ $b }}" class="text-slate-800">{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                <div>
                    <label class="block text-[10px] md:text-[11px] font-bold text-secondary mb-1.5 uppercase tracking-wide">Bidang Terkunci</label>
                    <div class="input-field w-full px-4 py-2.5 rounded-xl text-xs md:text-sm font-bold opacity-80 cursor-not-allowed">
                        Bidang {{ Auth::user()->bidang }}
                    </div>
                </div>
                @endif
                
                <div class="pt-4">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 md:py-3.5 rounded-xl transition shadow-lg hover:shadow-blue-500/30 text-xs md:text-sm flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        Simpan Akun
                    </button>
                </div>
            </form>
        </div>

        <!-- TABEL DAFTAR PENGGUNA -->
        <div class="glass-card rounded-3xl p-0 overflow-hidden lg:col-span-2 h-fit order-1 lg:order-2">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 p-4 md:p-5 border-b border-divider bg-slate-500/5">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <h2 class="text-base md:text-lg font-black text-primary">Daftar Akun Pengguna</h2>
                </div>

                @if(Auth::user()->role === 'super_admin')
                <form action="{{ route('admin.users.index') }}" method="GET" class="w-full sm:w-auto">
                    <select name="bidang" onchange="this.form.submit()" class="input-field px-3 py-1.5 rounded-lg text-xs font-bold transition">
                        <option value="Semua" {{ request('bidang') == 'Semua' ? 'selected' : '' }}>-- Semua Bidang --</option>
                        @foreach(['Sekretariat', 'Statistik', 'PKP', 'TIK', 'E-Gov', 'Persandian'] as $b)
                            <option value="{{ $b }}" {{ request('bidang') == $b ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>
                </form>
                @endif
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-left min-w-[700px]">
                    <thead>
                        <tr class="text-[9px] md:text-[10px] font-black uppercase tracking-wider table-border text-secondary bg-slate-500/5">
                            <th class="p-3 md:p-4 pl-4 md:pl-6">Nama & Identitas</th>
                            <th class="p-3 md:p-4">Email</th>
                            <th class="p-3 md:p-4">Bidang</th>
                            <th class="p-3 md:p-4 text-center">Role</th>
                            <th class="p-3 md:p-4 pr-4 md:pr-6 text-center w-48">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-[11px] md:text-xs divide-y divide-divider border-divider">
                        @forelse($users as $u)
                        <tr class="table-border hover:bg-slate-500/5 transition">
                            <td class="p-3 md:p-4 pl-4 md:pl-6 font-bold text-primary max-w-[200px]">
                                <p class="truncate leading-tight">{{ $u->name }}</p>
                                @if(!empty($u->jabatan))
                                    <p class="text-[10px] text-blue-500 font-semibold truncate mt-0.5">{{ $u->jabatan }}</p>
                                @endif
                                @if(($u->status_pegawai ?? 'ASN') === 'Tenaga Ahli')
                                    <p class="text-[10px] text-secondary font-mono mt-0.5">ID: {{ $u->nip ?? '-' }} <span class="text-amber-500 font-bold ml-1">(Tenaga Ahli)</span></p>
                                @else
                                    <p class="text-[10px] text-secondary font-mono mt-0.5">NIP: {{ $u->nip ?? '-' }}</p>
                                @endif
                            </td>

                            <td class="p-3 md:p-4 text-secondary font-medium truncate max-w-[150px]">
                                {{ $u->email }}
                            </td>

                            <td class="p-3 md:p-4 text-secondary font-medium">
                                {{ $u->bidang ?? '-' }}
                            </td>

                            <td class="p-3 md:p-4 text-center">
                                @if($u->role === 'super_admin')
                                    <span class="bg-purple-500/10 text-purple-600 border border-purple-500/20 px-2.5 py-1 rounded-full font-bold text-[9px] uppercase tracking-wider whitespace-nowrap">Super Admin</span>
                                @elseif(in_array($u->role, ['admin_bidang', 'admin']))
                                    <span class="bg-blue-500/10 text-blue-600 border border-blue-500/20 px-2.5 py-1 rounded-full font-bold text-[9px] uppercase tracking-wider whitespace-nowrap">Admin Bidang</span>
                                @else
                                    <span class="bg-slate-500/10 text-secondary border border-slate-500/20 px-2.5 py-1 rounded-full font-bold text-[9px] uppercase tracking-wider whitespace-nowrap">Pegawai</span>
                                @endif
                            </td>

                            <td class="p-3 md:p-4 pr-4 md:pr-6 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    
                                    <!-- Tombol Edit -->
                                    <a href="{{ route('admin.users.edit', $u->id) }}" class="inline-flex items-center gap-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 border border-amber-500/20 px-2 md:px-2.5 py-1 rounded-lg font-bold transition text-[10px]" title="Edit Akun">
                                        Edit
                                    </a>

                                    <!-- Tombol Reset Password ke NIP -->
                                    <form action="{{ route('admin.users.resetPassword', $u->id) }}" method="POST" onsubmit="return confirm('Reset password {{ $u->name }} ke NIP / ID Login ({{ $u->nip }})?');" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-600 border border-indigo-500/20 px-2 md:px-2.5 py-1 rounded-lg font-bold transition text-[10px]" title="Reset Password ke NIP/ID Login">
                                            Reset PW
                                        </button>
                                    </form>

                                    <!-- Tombol Hapus -->
                                    @if($u->id !== Auth::id() && $u->email !== 'ariirawan022021@gmail.com')
                                    <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Hapus akun pegawai {{ $u->name }}?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center p-1 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-600 border border-red-500/20 transition text-[10px]" title="Hapus Akun">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-secondary">
                                Tidak ada akun pengguna yang ditemukan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    function updateNipFieldState(ctx) {
        const select = document.getElementById('status_pegawai_' + ctx);
        const input = document.getElementById('nip_input_' + ctx);
        const help = document.getElementById('nip_help_' + ctx);
        if (!select || !input || !help) return;
        
        if (select.value === 'Tenaga Ahli') {
            input.placeholder = 'Masukkan 16 digit NIK atau ID (contoh: TA-001)';
            help.innerText = 'Hanya digunakan sebagai ID Login & Password awal (tidak dicetak sebagai NIP di dokumen).';
            help.className = 'text-[10px] text-amber-500 font-semibold mt-1';
        } else {
            input.placeholder = 'Contoh: TA-001, 111111, 2221-2226, atau 18 digit NIP';
            help.innerText = 'Digunakan untuk login dan dicetak pada dokumen resmi (min 4, maks 25 karakter).';
            help.className = 'text-[10px] text-blue-500 font-semibold mt-1';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateNipFieldState('create');
    });
</script>
@endsection
