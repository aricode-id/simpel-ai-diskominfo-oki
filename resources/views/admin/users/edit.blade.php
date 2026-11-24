@extends('layouts.admin')

@section('page_title', 'Edit Data Pengguna')

@section('content')
<div class="flex justify-center items-start pt-4 pb-12 px-4 sm:px-0">
    <div class="w-full max-w-xl glass-card rounded-3xl p-6 sm:p-8 relative overflow-hidden shadow-sm">
        
        <!-- Header Form -->
        <div class="flex items-center gap-4 mb-8 relative z-10 border-b border-divider pb-6">
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center border border-blue-500/20 text-blue-600 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            </div>
            <div>
                <h2 class="text-xl sm:text-2xl font-black text-primary">Edit Data Pengguna</h2>
                <p class="text-[11px] sm:text-xs text-secondary mt-1">Perbarui informasi NIP, bidang, role, atau kata sandi.</p>
            </div>
        </div>

        <form action="{{ route('admin.users.update', $user->id) }}" method="POST" class="space-y-5 relative z-10">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-[11px] font-bold text-secondary mb-2 uppercase tracking-widest">Nama Lengkap <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="input-field w-full px-4 py-3 rounded-xl text-sm outline-none transition">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-secondary mb-2 uppercase tracking-widest">Status Kepegawaian <span class="text-rose-500">*</span></label>
                <select name="status_pegawai" id="status_pegawai_edit" onchange="updateNipFieldState('edit')" required class="input-field w-full px-4 py-3 rounded-xl text-sm outline-none transition appearance-none">
                    <option value="ASN" class="text-slate-800" {{ old('status_pegawai', $user->status_pegawai ?? 'ASN') === 'ASN' ? 'selected' : '' }}>ASN (PNS / PPPK)</option>
                    <option value="Tenaga Ahli" class="text-slate-800" {{ old('status_pegawai', $user->status_pegawai ?? 'ASN') === 'Tenaga Ahli' ? 'selected' : '' }}>Tenaga Ahli</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-secondary mb-2 uppercase tracking-widest">NIP / NIK / ID LOGIN <span class="text-rose-500">*</span></label>
                <input type="text" name="nip" id="nip_input_edit" value="{{ old('nip', $user->nip) }}" minlength="4" maxlength="25" required class="input-field w-full px-4 py-3 rounded-xl text-sm outline-none transition font-mono" placeholder="Contoh: TA-001, 111111, 2221-2226, atau 18 digit NIP">
                <p id="nip_help_edit" class="text-[10px] text-blue-500 font-semibold mt-1">Digunakan untuk login dan dicetak pada dokumen resmi (min 4, maks 25 karakter).</p>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-secondary mb-2 uppercase tracking-widest">Jabatan</label>
                <input type="text" name="jabatan" id="jabatan_input_edit" value="{{ old('jabatan', $user->jabatan) }}" class="input-field w-full px-4 py-3 rounded-xl text-sm outline-none transition" placeholder="Contoh: Kepala Bidang E-Gov / Pranata Komputer / Tenaga Ahli">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-secondary mb-2 uppercase tracking-widest">Email Pengguna</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="input-field w-full px-4 py-3 rounded-xl text-sm outline-none transition">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-2 uppercase tracking-widest">Role / Hak Akses <span class="text-rose-500">*</span></label>
                    <select name="role" required class="input-field w-full px-4 py-3 rounded-xl text-sm outline-none transition appearance-none">
                        <option value="pegawai" class="text-slate-800" {{ $user->role === 'pegawai' ? 'selected' : '' }}>Pegawai (Staf)</option>
                        @if(Auth::user()->role === 'super_admin')
                            <option value="admin_bidang" class="text-slate-800" {{ in_array($user->role, ['admin_bidang', 'admin']) ? 'selected' : '' }}>Admin Bidang</option>
                            <option value="super_admin" class="text-slate-800" {{ $user->role === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                        @endif
                    </select>
                </div>

                @if(Auth::user()->role === 'super_admin')
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-2 uppercase tracking-widest">Bidang / Unit</label>
                    <select name="bidang" class="input-field w-full px-4 py-3 rounded-xl text-sm outline-none transition appearance-none">
                        <option value="" class="text-slate-800">-- Tanpa Bidang / Super Admin --</option>
                        @foreach(['Sekretariat', 'Statistik', 'PKP', 'TIK', 'E-Gov', 'Persandian'] as $b)
                            <option value="{{ $b }}" class="text-slate-800" {{ $user->bidang === $b ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                <div>
                    <label class="block text-[11px] font-bold text-secondary mb-2 uppercase tracking-widest">Bidang</label>
                    <div class="input-field w-full px-4 py-3 rounded-xl text-sm font-bold opacity-80 cursor-not-allowed">
                        Bidang {{ $user->bidang }}
                    </div>
                </div>
                @endif
            </div>

            <div>
                <label class="block text-[11px] font-bold text-secondary mb-2 uppercase tracking-widest">Password Baru (Opsional)</label>
                <input type="password" name="password" minlength="4" placeholder="Kosongkan jika tidak ingin mengubah password (min 4 karakter)" class="input-field w-full px-4 py-3 rounded-xl text-sm outline-none transition">
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-6 mt-8 border-t border-divider">
                <a href="{{ route('admin.users.index') }}" class="w-full sm:w-1/3 flex items-center justify-center bg-slate-500/10 hover:bg-slate-500/20 text-primary font-bold py-3.5 rounded-xl transition text-xs sm:text-sm border border-divider">
                    Batal
                </a>
                <button type="submit" class="w-full sm:w-2/3 flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-blue-500/30 text-xs sm:text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>

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
        updateNipFieldState('edit');
    });
</script>
@endsection
