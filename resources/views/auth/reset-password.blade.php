<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Password Baru | SIMPEL-AI</title>
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
<body class="antialiased selection:bg-blue-600 selection:text-white flex items-center justify-center min-h-screen relative overflow-hidden">

    <div class="absolute inset-0 z-[-1] opacity-5 bg-[url('{{ asset('foto_kantor.jpg') }}')] bg-cover bg-center"></div>

    <div class="w-full max-w-md p-4 relative z-10">
        <!-- Logo & Header -->
        <div class="flex flex-col items-center justify-center mb-8">
            <div class="flex items-center gap-3 mb-4">
                <img src="{{ asset('logo_oki.webp') }}" alt="Logo OKI" class="h-10 w-auto drop-shadow-sm">
                <div class="h-8 w-px border-l-2 border-slate-300"></div>
                <img src="{{ asset('logo-simpel-ai.svg') }}" alt="SIMPEL-AI" class="h-8 w-auto drop-shadow-sm">
            </div>
            <h1 class="font-black text-3xl tracking-tight text-slate-900 mb-1">SIMPEL<span class="text-blue-600">-AI</span></h1>
            <p class="text-xs text-slate-500 font-bold uppercase tracking-widest text-center">Buat Password Baru</p>
        </div>

        <div class="glass-card rounded-3xl p-8">
            
            @if ($errors->any())
                <div class="mb-5 text-xs text-red-600 bg-red-50 border border-red-200 p-3.5 rounded-xl font-bold flex items-start gap-2 shadow-sm">
                    <ul class="list-none space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Email Terdaftar</label>
                    <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" required readonly class="input-field w-full px-4 py-3 rounded-xl outline-none text-sm opacity-70 cursor-not-allowed">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">Password Baru</label>
                    <input id="password" type="password" name="password" required autofocus class="input-field w-full px-4 py-3 rounded-xl outline-none text-sm" placeholder="Minimal 8 karakter">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5">Konfirmasi Password Baru</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required class="input-field w-full px-4 py-3 rounded-xl outline-none text-sm" placeholder="Ketik ulang password baru">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-blue-600/30">
                        Simpan Password Baru
                    </button>
                </div>
            </form>
        </div>
        <p class="text-center text-[10px] text-slate-400 mt-8 font-bold tracking-widest uppercase">&copy; 2026 Diskominfo Kab. OKI.</p>
    </div>
</body>
</html>