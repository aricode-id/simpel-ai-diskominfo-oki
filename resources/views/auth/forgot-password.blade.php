<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password | SIMPEL-AI Diskominfo</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- ASYNC GOOGLE FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .glass-card { background-color: rgba(255, 255, 255, 0.9); backdrop-filter: blur(16px); border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }
        .input-field { background-color: #f1f5f9; border: 1px solid #cbd5e1; color: #1e293b; transition: all 0.2s ease; }
        .input-field:focus { background-color: #ffffff; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    </style>
</head>
<body class="antialiased selection:bg-blue-600 selection:text-white flex items-center justify-center min-h-screen relative py-12">

    <!-- Background -->
    <div class="fixed inset-0 z-[-1] opacity-5 bg-[url('{{ asset('foto_kantor.jpg') }}')] bg-cover bg-center"></div>

    <div class="w-full max-w-md p-4 relative z-10">
        <!-- Logo & Header -->
        <div class="flex flex-col items-center justify-center mb-8">
            <div class="flex items-center gap-3 mb-4">
                <img src="{{ asset('logo_oki.webp') }}" alt="Logo OKI" class="h-10 w-auto drop-shadow-sm">
                <div class="h-10 w-px border-l-2 border-slate-300"></div>
                <img src="{{ asset('logo-simpel-ai.svg') }}" alt="SIMPEL-AI" class="h-10 w-auto drop-shadow-sm">
            </div>
            <h1 class="font-black text-3xl tracking-tight text-slate-900 mb-1">SIMPEL<span class="text-blue-600">-AI</span></h1>
            <p class="text-xs text-slate-500 font-bold uppercase tracking-widest text-center">Sistem Manajemen Pelaporan</p>
        </div>

        <!-- Kotak Form Lupa Password -->
        <div class="glass-card rounded-3xl p-8">
            <h2 class="text-xl font-bold text-slate-800 mb-3 text-center">Reset Password</h2>
            <p class="text-xs text-slate-500 text-center mb-6 leading-relaxed">
                Lupa password? Masukkan email instansi / terdaftar Anda, dan kami akan mengirimkan tautan instruksi pemulihan.
            </p>

            @if ($errors->any())
                <div class="mb-5 text-xs text-red-600 bg-red-50 border border-red-200 p-3.5 rounded-xl font-bold flex items-start gap-2 shadow-sm">
                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <ul class="list-none space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('status'))
                <div class="mb-5 text-xs font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 p-3.5 rounded-xl shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Email Terdaftar / Instansi</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="input-field w-full px-4 py-3 rounded-xl outline-none text-sm" placeholder="anda@oki.go.id">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-blue-600/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        Kirim Tautan Reset
                    </button>
                </div>
            </form>

            <div class="mt-8 pt-5 border-t border-slate-200 flex items-center justify-between text-xs sm:text-sm">
                <a href="{{ route('login') }}" class="text-blue-600 font-bold hover:text-blue-800 transition flex items-center gap-1">
                    &larr; Kembali ke Login
                </a>
            </div>
            
        </div>
        
        <p class="text-center text-[10px] text-slate-400 mt-8 font-bold tracking-widest uppercase">&copy; 2026 Diskominfo Kab. OKI.</p>
    </div>

</body>
</html>