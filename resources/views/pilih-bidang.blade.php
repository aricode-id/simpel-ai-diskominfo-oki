<!DOCTYPE html>
<html lang="id">
<head>
    <title>Lengkapi Profil | SIMPEL-AI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 flex items-center justify-center h-screen p-4">
    <div class="bg-white p-8 rounded-2xl shadow-lg max-w-md w-full">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-black text-slate-800">Satu Langkah Lagi! 🚀</h2>
            <p class="text-sm text-slate-500 mt-2">Pilih bidang penempatan anda sekarang biar sistem bisa nyesuaikan laporan mu.</p>
        </div>

        <form action="{{ route('simpan.bidang') }}" method="POST">
            @csrf
            <div class="mb-5">
                <label class="block text-sm font-bold text-slate-700 mb-2">Bidang Penempatan</label>
                <select name="bidang" required class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="" disabled selected>-- Pilih Bidang Anda --</option>
                    <option value="Sekretariat">Sekretariat</option>
                    <option value="Statistik">Statistik</option>
                    <option value="PKP">PKP (Pengelolaan Komunikasi Publik)</option>
                    <option value="TIK">TIK (Infrastruktur Teknologi Informasi)</option>
                    <option value="E-Gov">E-Gov (Layanan e-Government / Aptika)</option>
                    <option value="Persandian">Persandian (Keamanan Informasi)</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition">
                Simpan & Masuk Dashboard
            </button>
        </form>
    </div>
</body>
</html>