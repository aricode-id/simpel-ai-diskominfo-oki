<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun | SIMPEL-AI Diskominfo</title>
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
        /* Sembunyikan ikon mata bawaan Edge/IE agar tidak dobel */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }
    </style>
</head>
<body class="antialiased selection:bg-blue-600 selection:text-white flex items-center justify-center min-h-screen relative py-12">

    <!-- Background selaras dengan Login -->
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

        <div class="glass-card rounded-3xl p-8">
            <h2 class="text-xl font-bold text-slate-800 mb-6 text-center">Daftar Akun Pegawai</h2>

            <!-- Blok Error Validasi -->
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

            {{--      
            <!-- Tombol Google Login -->
            <a href="{{ route('auth.google') }}" class="w-full flex items-center justify-center gap-3 px-4 py-3.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold rounded-xl transition shadow-sm mb-6 hover:shadow-md">
                <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
                    <path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.6l3.1-3.1C17.3 1.7 14.8 1 12 1 7.5 1 3.7 3.6 1.9 7.3l3.7 2.9C6.5 7.3 9 5 12 5z"/>
                    <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.8z"/>
                    <path fill="#FBBC05" d="M5.6 14.8c-.2-.7-.4-1.5-.4-2.3s.2-1.6.4-2.3L1.9 7.3C.7 9.7 0 10.8 0 12s.7 2.3 1.9 4.7l3.7-1.9z"/>
                    <path fill="#34A853" d="M12 23c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3 0-5.5-2.3-6.4-5.2L1.9 16c1.8 3.7 5.6 7 10.1 7z"/>
                </svg>
                <span class="text-sm md:text-base leading-tight text-center">Masuk/Daftar Cepat dengan Google</span>
            </a>


            <div class="relative flex py-3 items-center mb-6">
                <div class="flex-grow border-t border-slate-200"></div>
                <span class="flex-shrink mx-4 text-slate-400 text-[10px] font-black uppercase tracking-widest">Atau Isi Manual</span>
                <div class="flex-grow border-t border-slate-200"></div>
            </div>
            --}}

            <!-- FIX: Pastikan method POST, action route register, dan ada @csrf -->
            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf
                
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap & Gelar</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="input-field w-full px-4 py-3 rounded-xl outline-none text-sm" placeholder="Contoh: Budi Santoso, S.Kom">
                </div>

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Email Resmi / Pribadi</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="input-field w-full px-4 py-3 rounded-xl outline-none text-sm" placeholder="anda@oki.go.id">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">Password</label>
                    <div class="relative">
                        <input id="password" type="password" name="password" required autocomplete="new-password" style="padding-right: 46px;" class="input-field w-full px-4 py-3 rounded-xl outline-none text-sm" placeholder="Minimal 8 karakter">
                        <button type="button" onclick="togglePassword('password', 'eye-password', 'eye-off-password')" style="right: 16px;" class="absolute inset-y-0 flex items-center text-slate-400 hover:text-blue-600 transition focus:outline-none" title="Lihat/Sembunyikan Password">
                            <svg id="eye-password" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg id="eye-off-password" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- FIX: Tambahan Konfirmasi Password (Wajib untuk validasi bawaan Laravel) -->
                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5">Ulangi Password</label>
                    <div class="relative">
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" style="padding-right: 46px;" class="input-field w-full px-4 py-3 rounded-xl outline-none text-sm" placeholder="Ketik ulang password">
                        <button type="button" onclick="togglePassword('password_confirmation', 'eye-confirm', 'eye-off-confirm')" style="right: 16px;" class="absolute inset-y-0 flex items-center text-slate-400 hover:text-blue-600 transition focus:outline-none" title="Lihat/Sembunyikan Password">
                            <svg id="eye-confirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg id="eye-off-confirm" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-blue-600/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        Daftar Akun Baru
                    </button>
                </div>
            </form>

            <!-- Footer Bawah Form -->
            <div class="mt-8 pt-5 border-t border-slate-200 flex items-center justify-between text-xs sm:text-sm">
                <a href="{{ url('/') }}" class="text-blue-600 font-bold hover:text-blue-800 transition flex items-center gap-1">
                    &larr; Utama
                </a>
                <div class="text-slate-500">
                    Sudah ada akun? <a href="{{ route('login') }}" class="font-bold text-slate-900 hover:underline hover:text-blue-600 transition">Masuk</a>
                </div>
            </div>
            
        </div>
        
        <p class="text-center text-[10px] text-slate-400 mt-8 font-bold tracking-widest uppercase">&copy; 2026 Diskominfo Kab. OKI.</p>
    </div>

    <script>
        function togglePassword(inputId, eyeId, eyeOffId) {
            const input = document.getElementById(inputId);
            const eye = document.getElementById(eyeId);
            const eyeOff = document.getElementById(eyeOffId);

            if (input.type === 'password') {
                input.type = 'text';
                eye.classList.add('hidden');
                eyeOff.classList.remove('hidden');
            } else {
                input.type = 'password';
                eye.classList.remove('hidden');
                eyeOff.classList.add('hidden');
            }
        }
    </script>

</body>
</html>