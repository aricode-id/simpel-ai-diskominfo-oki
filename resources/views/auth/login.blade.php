<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SIMPEL-AI Diskominfo</title>
    
    <!-- Tag PWA Wajib untuk Halaman Login -->
    <meta name="theme-color" content="#2563eb">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">

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

        <!-- Kotak Form Login -->
        <div class="glass-card rounded-3xl p-8">
            <h2 class="text-xl font-bold text-slate-800 mb-6 text-center">Selamat Datang Kembali</h2>

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

            @if (session('status') || session('error'))
                <div class="mb-5 text-xs font-bold {{ session('status') ? 'text-emerald-600 bg-emerald-50 border border-emerald-200' : 'text-red-600 bg-red-50 border border-red-200' }} p-3.5 rounded-xl shadow-sm">
                    {{ session('status') ?? session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="nip" class="block text-xs font-bold text-slate-700 mb-1.5">NIP / ID Jabatan</label>
                    <input id="nip" type="text" name="nip" value="{{ old('nip', old('email')) }}" minlength="4" maxlength="25" required autofocus autocomplete="username" class="input-field w-full px-4 py-3 rounded-xl outline-none text-sm font-medium font-mono" placeholder="Masukkan 4-25 digit NIP atau ID (contoh: TA-001, 111111)">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">Password Rahasia</label>
                    <div class="relative">
                        <input id="password" type="password" name="password" required autocomplete="current-password" style="padding-right: 46px;" class="input-field w-full px-4 py-3 rounded-xl outline-none text-sm" placeholder="••••••••">
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

                <div class="flex items-center pt-1 pb-2">
                    <label for="remember_me" class="flex items-center cursor-pointer group">
                        <input id="remember_me" type="checkbox" name="remember" class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500 w-4 h-4 transition">
                        <span class="ml-2 text-xs text-slate-600 font-bold group-hover:text-slate-800 transition">Ingat Sesi Saya</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-blue-600/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                        Masuk Sistem
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-200 text-center">
                <p class="text-xs text-slate-500 leading-relaxed font-medium">
                    Akun dikelola secara internal oleh Admin Bidang / Super Admin. Hubungi Admin jika lupa kata sandi.
                </p>
                <div class="mt-4">
                    <a href="{{ url('/') }}" class="text-xs text-blue-600 font-bold hover:text-blue-800 transition inline-flex items-center gap-1">
                        &larr; Kembali ke Beranda
                    </a>
                </div>
            </div>
            
        </div>

        <p class="text-center text-[10px] text-slate-400 mt-4 font-bold tracking-widest uppercase">&copy; 2026 Diskominfo Kab. OKI.</p>
    </div>

    <!-- Script Toggle Password -->
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

    <!-- Script Registrasi Service Worker PWA -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js');
            });
        }
    </script>

    <!-- ======== KODE POP-UP PWA ======== -->
    <style>
        #pwa-install-prompt {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background-color: #ffffff;
            padding: 16px 20px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
            z-index: 999999 !important;
            display: flex;
            flex-direction: column;
            gap: 12px;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            font-family: 'Inter', sans-serif;
            transform: translateY(100%);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #pwa-install-prompt.muncul {
            transform: translateY(0) !important;
        }
        .pwa-header {
            display: flex; align-items: center; gap: 12px; position: relative;
        }
        .pwa-logo {
            width: 44px; height: 44px; flex-shrink: 0;
            background: #f8fafc; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            border: 1px solid #e2e8f0;
        }
        .pwa-logo img { max-width: 100%; max-height: 100%; }
        .pwa-text { flex-grow: 1; padding-right: 24px; }
        .pwa-text h4 { margin: 0 0 2px 0; font-size: 14px; font-weight: 800; color: #000; }
        .pwa-text p { margin: 0; font-size: 11px; color: #64748b; line-height: 1.4; }
        .pwa-close {
            position: absolute; top: -5px; right: -5px;
            background: none; border: none; font-size: 18px; color: #94a3b8;
            cursor: pointer; padding: 4px; font-family: sans-serif; line-height: 1;
        }
        .pwa-actions { display: flex; justify-content: flex-end; gap: 10px; }
        .btn-nanti {
            background: #ffffff; border: 1px solid #e2e8f0; color: #64748b;
            padding: 8px 20px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;
        }
        .btn-install {
            background: #0d9488;
            border: none; color: #ffffff;
            padding: 8px 20px; border-radius: 6px; font-size: 12px; font-weight: 700;
            cursor: pointer; display: flex; align-items: center; gap: 6px;
        }
        @media (min-width: 768px) {
            #pwa-install-prompt {
                width: 380px; left: auto; right: 24px; bottom: 24px;
                border-radius: 12px; border: 1px solid #e2e8f0;
            }
        }
    </style>

    <div id="pwa-install-prompt">
        <div class="pwa-header">
            <div class="pwa-logo">
                <img src="{{ asset('logo-simpel-ai.svg') }}" alt="Logo SIMPEL-AI">
            </div>
            <div class="pwa-text">
                <h4>Install SIMPEL-AI</h4>
                <p>Pasang aplikasi ini di perangkat Anda untuk akses yang lebih cepat.</p>
            </div>
            <button id="pwa-close-x" class="pwa-close" aria-label="Tutup">✕</button>
        </div>
        <div class="pwa-actions">
            <button id="pwa-nanti-btn" class="btn-nanti">Nanti</button>
            <button id="pwa-install-btn" class="btn-install">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                  <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                </svg>
                Install
            </button>
        </div>
    </div>

    <script>
        let deferredPrompt;
        const installPrompt = document.getElementById('pwa-install-prompt');
        const btnInstall = document.getElementById('pwa-install-btn');
        const btnNanti = document.getElementById('pwa-nanti-btn');
        const btnClose = document.getElementById('pwa-close-x');

        const isPwaDismissed = localStorage.getItem('simpel_ai_pwa_dismissed');

        if (!isPwaDismissed && installPrompt) {
            setTimeout(() => {
                installPrompt.classList.add('muncul');
            }, 300);
        }

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
        });

        function dismissPopup() {
            if (installPrompt) installPrompt.classList.remove('muncul');
            localStorage.setItem('simpel_ai_pwa_dismissed', 'true');
        }

        btnInstall.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    dismissPopup(); 
                }
                deferredPrompt = null;
            } else {
                alert("Sedang menyinkronkan data instalasi... Mohon klik sekali lagi dalam 2 detik.");
            }
        });

        btnNanti.addEventListener('click', dismissPopup);
        btnClose.addEventListener('click', dismissPopup);

        window.addEventListener('appinstalled', () => {
            dismissPopup();
        });
    </script>
    <!-- ======== AKHIR KODE POP-UP ======== -->

</body>
</html>