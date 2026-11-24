<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Tag PWA -->
    <meta name="theme-color" content="#2563eb">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">
    <title>@yield('page_title', 'SIMPEL-AI') | Diskominfo OKI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- ASYNC GOOGLE FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <style> 
        body { font-family: 'Inter', sans-serif; transition: background-color 0.3s ease; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        
        /* BACKGROUND UNTUK MODE GELAP */
        .bg-dark-img { display: none; position: fixed; inset: 0; z-index: -2; background: url("{{ asset('foto_kantor.jpg') }}") center/cover no-repeat; }
        .bg-dark-overlay { display: none; position: fixed; inset: 0; z-index: -1; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); }
        
        /* STYLE LIGHT MODE */
        body.light-mode { background-color: #f4f7fe; color: #1e293b; }
        body.light-mode .sidebar-bg { background-color: #ffffff; border-right: 1px solid #e2e8f0; }
        body.light-mode .main-bg { background-color: #f4f7fe; }
        body.light-mode .glass-card { background-color: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        body.light-mode .text-primary { color: #1e293b; }
        body.light-mode .text-secondary { color: #64748b; }
        body.light-mode .table-border, body.light-mode .border-divider { border-color: #e2e8f0; }
        body.light-mode .nav-link:hover { background-color: #f1f5f9; }
        body.light-mode .nav-link.active { background-color: #eff6ff; color: #2563eb; font-weight: 700; border: 1px solid #bfdbfe; }
        body.light-mode .input-field { background-color: #ffffff; border: 1px solid #cbd5e1; color: #1e293b; }
        body.light-mode .input-field:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

        /* STYLE DARK MODE */
        body.dark-mode { color: #f8fafc; }
        body.dark-mode .bg-dark-img, body.dark-mode .bg-dark-overlay { display: block; }
        body.dark-mode .sidebar-bg { background-color: rgba(15, 23, 42, 0.75); backdrop-filter: blur(16px); border-right: 1px solid rgba(255, 255, 255, 0.1); }
        body.dark-mode .main-bg { background-color: transparent; }
        body.dark-mode .glass-card { background-color: rgba(255, 255, 255, 0.05); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2); }
        body.dark-mode .text-primary { color: #ffffff; }
        body.dark-mode .text-secondary { color: #94a3b8; }
        body.dark-mode .table-border, body.dark-mode .border-divider { border-color: rgba(255,255,255,0.1); }
        body.dark-mode .nav-link:hover { background-color: rgba(255,255,255,0.05); }
        body.dark-mode .nav-link.active { background-color: rgba(59, 130, 246, 0.2); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.3); }
        body.dark-mode .input-field { background-color: rgba(15, 23, 42, 0.5); border: 1px solid rgba(255, 255, 255, 0.2); color: #ffffff; }
        body.dark-mode .input-field:focus { border-color: #60a5fa; box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.15); }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (localStorage.getItem("theme") === "dark") { enableDarkMode(); } else { enableLightMode(); }
        });
        function toggleTheme() {
            if (document.body.classList.contains("dark-mode")) { enableLightMode(); } else { enableDarkMode(); }
        }
        function enableDarkMode() {
            document.body.classList.remove("light-mode"); document.body.classList.add("dark-mode");
            const txt = document.getElementById("theme-text"); if(txt) txt.innerText = "Mode Terang";
            localStorage.setItem("theme", "dark");
        }
        function enableLightMode() {
            document.body.classList.remove("dark-mode"); document.body.classList.add("light-mode");
            const txt = document.getElementById("theme-text"); if(txt) txt.innerText = "Mode Gelap";
            localStorage.setItem("theme", "light");
        }

        // LOGIKA SIDEBAR MOBILE
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
    </script>
</head>
<body class="light-mode selection:bg-blue-600 selection:text-white">

    <div class="bg-dark-img"></div>
    <div class="bg-dark-overlay"></div>

    <!-- WRAPPER UTAMA -->
    <div class="flex h-screen w-full overflow-hidden relative">
        
        <!-- OVERLAY UNTUK MOBILE SIDEBAR -->
        <div id="sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 hidden md:hidden transition-opacity"></div>
        
        <!-- SIDEBAR OFF-CANVAS -->
        <aside id="sidebar" class="sidebar-bg w-64 h-full flex flex-col transition-transform duration-300 absolute md:relative z-50 -translate-x-full md:translate-x-0">
            <!-- Header Sidebar -->
            <div class="p-5 md:p-6 border-b border-divider flex items-center justify-between md:justify-start gap-3">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('logo_oki.webp') }}" alt="Logo OKI" class="h-8 w-auto drop-shadow-sm">
                    <div class="h-6 w-px border-l border-slate-300 dark:border-slate-600"></div>
                    <div class="flex items-center gap-2">
                        <img src="{{ asset('logo-simpel-ai.svg') }}" alt="Icon" class="h-6 w-auto drop-shadow-sm">
                        <span class="font-black text-lg tracking-tight text-primary">SIMPEL<span class="text-blue-600">-AI</span></span>
                    </div>
                </div>
                <!-- Tombol Close Sidebar (Mobile) -->
                <button onclick="toggleSidebar()" class="md:hidden text-secondary hover:text-primary transition p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <!-- Navigasi Menu Dinamis Berdasarkan 4 Role -->
            <div class="flex-1 py-6 px-4 space-y-4 overflow-y-auto">
                
                <!-- MENU TETAP: DASHBOARD -->
                <div>
                    <p class="px-2 text-[10px] font-black text-secondary uppercase tracking-widest mb-1.5">Menu Utama</p>
                    @if(Auth::user()->role === 'pegawai')
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors text-secondary font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors text-secondary font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                            Dashboard
                        </a>
                    @endif
                </div>

                <!-- ========================================== -->
                <!-- 1. ROLE: SUPER ADMIN -->
                <!-- ========================================== -->
                @if(Auth::user()->role === 'super_admin')
                    <!-- Kategori 2: Pelaporan Kerja -->
                    <div class="space-y-1">
                        <p class="px-2 text-[10px] font-black text-secondary uppercase tracking-widest mb-1.5">Pelaporan Kerja</p>
                        <a href="{{ route('admin.laporan.create') }}" class="nav-link {{ request()->routeIs('admin.laporan.create') || request()->routeIs('report.create') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors text-secondary font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Buat Laporan
                        </a>
                        <a href="{{ route('report.history') }}" class="nav-link {{ request()->routeIs('report.history') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors text-secondary font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Riwayat Laporan
                        </a>
                    </div>

                    <!-- Kategori 3: Manajemen Akun -->
                    <div>
                        <p class="px-2 text-[10px] font-black text-secondary uppercase tracking-widest mb-1.5">Manajemen Akun</p>
                        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors text-secondary font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            Kelola Semua Akun
                        </a>
                    </div>

                    <!-- Kategori 4: Monitoring & Arsip -->
                    <div class="space-y-1">
                        <p class="px-2 text-[10px] font-black text-secondary uppercase tracking-widest mb-1.5">Monitoring & Arsip</p>
                        <a href="{{ route('admin.monitoring') }}" class="nav-link {{ request()->routeIs('admin.monitoring') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors text-secondary font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            Monitoring Pegawai
                        </a>
                        <a href="{{ route('admin.laporan.history') }}" class="nav-link {{ request()->routeIs('admin.laporan.history') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors text-secondary font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Semua Laporan Masuk
                        </a>
                        <a href="{{ route('report.rekapan') }}" class="nav-link {{ request()->routeIs('report.rekapan') || request()->routeIs('admin.laporan.rekapan') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors text-secondary font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Menu Rekapan
                        </a>
                    </div>

                <!-- ========================================== -->
                <!-- 2. ROLE: ADMIN BIDANG -->
                <!-- ========================================== -->
                @elseif(in_array(Auth::user()->role, ['admin_bidang', 'admin']))
                    <!-- Kategori 2: Pelaporan Kerja -->
                    <div class="space-y-1">
                        <p class="px-2 text-[10px] font-black text-secondary uppercase tracking-widest mb-1.5">Pelaporan Kerja</p>
                        <a href="{{ route('admin.laporan.create') }}" class="nav-link {{ request()->routeIs('admin.laporan.create') || request()->routeIs('report.create') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors text-secondary font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Buat Laporan
                        </a>
                        <a href="{{ route('report.history') }}" class="nav-link {{ request()->routeIs('report.history') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors text-secondary font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Riwayat Laporan
                        </a>
                    </div>

                    <!-- Kategori 3: Manajemen Akun -->
                    <div>
                        <p class="px-2 text-[10px] font-black text-secondary uppercase tracking-widest mb-1.5">Manajemen Akun</p>
                        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors text-secondary font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            Kelola Akun Bidang
                        </a>
                    </div>

                    <!-- Kategori 4: Verifikasi & Arsip -->
                    <div class="space-y-1">
                        <p class="px-2 text-[10px] font-black text-secondary uppercase tracking-widest mb-1.5">Verifikasi & Arsip</p>
                        <a href="{{ route('admin.monitoring') }}" class="nav-link {{ request()->routeIs('admin.monitoring') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors text-secondary font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            Monitoring Pegawai
                        </a>
                        <a href="{{ route('admin.laporan.history') }}" class="nav-link {{ request()->routeIs('admin.laporan.history') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors text-secondary font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Verifikasi Laporan
                        </a>
                        <a href="{{ route('report.rekapan') }}" class="nav-link {{ request()->routeIs('report.rekapan') || request()->routeIs('admin.laporan.rekapan') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors text-secondary font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Menu Rekapan
                        </a>
                    </div>

                <!-- ========================================== -->
                <!-- 3. ROLE: PEGAWAI (STAF) -->
                <!-- ========================================== -->
                @else
                    <!-- Kategori 2: Pelaporan Kerja -->
                    <div class="space-y-1">
                        <p class="px-2 text-[10px] font-black text-secondary uppercase tracking-widest mb-1.5">Pelaporan Kerja</p>
                        <a href="{{ route('report.create') }}" class="nav-link {{ request()->routeIs('report.create') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors text-secondary font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Buat Laporan
                        </a>
                        <a href="{{ route('report.history') }}" class="nav-link {{ request()->routeIs('report.history') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors text-secondary font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Riwayat Laporan
                        </a>
                    </div>

                    <!-- Kategori 4: Arsip & Rekapan -->
                    <div class="space-y-1">
                        <p class="px-2 text-[10px] font-black text-secondary uppercase tracking-widest mb-1.5">Arsip & Rekapan</p>
                        <a href="{{ route('report.rekapan') }}" class="nav-link {{ request()->routeIs('report.rekapan') ? 'active' : '' }} flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors text-secondary font-medium">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            Menu Rekapan
                        </a>
                    </div>
                @endif

            </div>
        </aside>

        <!-- KONTEN UTAMA -->
        <div class="flex-1 flex flex-col h-full overflow-hidden main-bg transition-all duration-300">
            
            <!-- HEADER KONTEN (Navbar Atas) -->
            <header class="glass-card md:border-t-0 md:border-x-0 md:rounded-none h-16 px-4 md:px-6 flex justify-between items-center z-10 sticky top-0">
                <div class="flex items-center gap-3">
                    <!-- Tombol Hamburger Mobile -->
                    <button onclick="toggleSidebar()" class="md:hidden p-2 -ml-2 text-primary hover:bg-slate-500/10 rounded-lg transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h2 class="font-bold text-primary text-base md:text-lg hidden sm:block">@yield('page_title', 'Pusat Kendali')</h2>
                </div>
                
                <div class="flex items-center gap-2 sm:gap-4">
                    <button onclick="toggleTheme()" class="flex items-center justify-center w-9 h-9 rounded-full transition bg-slate-500/5 hover:bg-slate-500/10 text-primary" title="Ganti Tema">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>
                    
                    <div class="flex items-center gap-2 sm:gap-3 bg-slate-500/5 px-2 sm:px-3 py-1.5 rounded-xl border border-divider">
                        <div class="text-right hidden sm:block">
                            <p class="text-[11px] font-bold text-primary leading-tight">{{ Auth::user()->name ?? 'User' }}</p>
                            
                            <!-- Label Dinamis Berdasarkan 3 Role -->
                            @if(Auth::user()->role === 'super_admin')
                                <p class="text-[9px] font-black text-purple-600 uppercase tracking-widest">Super Admin</p>
                            @elseif(in_array(Auth::user()->role, ['admin_bidang', 'admin']))
                                <p class="text-[9px] font-black text-blue-600 uppercase tracking-widest">Admin Bidang {{ Auth::user()->bidang }}</p>
                            @else
                                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Pegawai ({{ Auth::user()->bidang ?? 'Diskominfo' }})</p>
                            @endif
                        </div>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-xs border border-blue-200">
                            {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                        </div>
                    </div>
                    
                    <div class="h-6 w-px border-l border-divider hidden sm:block"></div>

                    @if(Auth::user()->role === 'pegawai')
                        <a href="{{ route('report.history') }}" class="flex items-center justify-center w-9 h-9 sm:w-auto sm:h-auto sm:px-3 sm:py-1.5 rounded-full sm:rounded-lg border border-divider hover:bg-slate-500/5 text-primary transition" title="Riwayat Laporan">
                            <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="hidden sm:block sm:ml-1 text-xs font-bold">Riwayat</span>
                        </a>
                    @else
                        <a href="{{ route('admin.laporan.history') }}" class="flex items-center justify-center w-9 h-9 sm:w-auto sm:h-auto sm:px-3 sm:py-1.5 rounded-full sm:rounded-lg border border-divider hover:bg-slate-500/5 text-primary transition" title="Arsip Dokumen">
                            <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="hidden sm:block sm:ml-1 text-xs font-bold">Arsip</span>
                        </a>
                    @endif
                    
                    <form action="{{ route('logout') }}" method="POST" class="m-0 p-0 flex">
                        @csrf
                        <button type="submit" onclick="return confirm('Yakin ingin keluar dari akun?');" class="flex items-center justify-center w-9 h-9 rounded-full bg-red-500/10 hover:bg-red-500/20 text-red-600 transition" title="Logout">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        </button>
                    </form>
                </div>
            </header>

            <!-- AREA KONTEN -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8">
                @yield('content')
            </main>

        </div>
    </div> <!-- PENUTUP WRAPPER UTAMA -->

    <!-- Script Registrasi PWA -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((registration) => {
                        console.log('PWA Service Worker terdaftar sukses:', registration.scope);
                    })
                    .catch((error) => {
                        console.log('PWA Service Worker gagal mendaftar:', error);
                    });
            });
        }
    </script>
</body>
</html>
