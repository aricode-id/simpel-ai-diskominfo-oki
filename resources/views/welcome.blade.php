<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tag PWA -->
    <meta name="theme-color" content="#2563eb">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">
    <title>SIMPEL-AI | Diskominfo Kab. Ogan Komering Ilir</title>
    <link rel="preload" as="image" href="{{ asset('foto_kantor.jpg') }}" fetchpriority="high">
    
    <!-- Favicon pakai Logo SIMPEL-AI -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('logo-simpel-ai.svg') }}">
    
@vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- ASYNC GOOGLE FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .hero-pattern {
            background-color: #f8fafc;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%232563eb' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased selection:bg-blue-600 selection:text-white">

    <!-- NAVIGATION BAR -->
    <nav class="fixed w-full bg-white/90 backdrop-blur-md z-50 border-b border-slate-200 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex items-center gap-4">
                    <!-- Logo OKI -->
                    <img src="{{ asset('logo_oki.webp') }}" alt="Logo OKI" class="h-10 w-auto">
                    
                    <div class="border-l-2 border-slate-300 pl-4">
                        <div class="flex items-center gap-2.5">
                            <!-- Ikon S dari SVG -->
                            <img src="{{ asset('logo-simpel-ai.svg') }}" alt="Ikon SIMPEL-AI" class="h-8 w-auto">
                            <!-- Teks HTML SIMPEL-AI -->
                            <span class="font-black text-2xl tracking-tight text-slate-900 mt-0.5">SIMPEL<span class="text-blue-600">-AI</span></span>
                        </div>
                    </div>
                </div>

                <!-- Menu Tengah -->
                <div class="hidden md:flex space-x-8">
                    <a href="#" class="text-blue-600 font-bold border-b-2 border-blue-600 py-7">Beranda</a>
                    <a href="#fitur" class="text-slate-500 hover:text-blue-600 font-medium py-7 transition">Fitur</a>
                    <a href="#cara-kerja" class="text-slate-500 hover:text-blue-600 font-medium py-7 transition">Panduan</a>
                    <a href="#tentang" class="text-slate-500 hover:text-blue-600 font-medium py-7 transition">Tentang</a>
                </div>

                <!-- Tombol Login -->
                <div>
                    @auth
                        <a href="{{ url('/dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-full font-bold transition shadow-lg shadow-blue-600/30 flex items-center gap-2">
                            Dashboard <span aria-hidden="true">&rarr;</span>
                        </a>
                    @else
                        <a href="{{ url('/login') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-full font-bold transition shadow-lg shadow-blue-600/30 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                            Login
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden bg-slate-50">
        
        <!-- BACKGROUND GAMBAR KANTOR -->
        <div class="absolute inset-0 z-0">
        <img src="{{ asset('foto_kantor.jpg') }}" fetchpriority="high" alt="Background Kantor" class="w-full h-full object-cover object-right opacity-90">
            <div class="absolute inset-y-0 left-0 w-full lg:w-3/4 bg-gradient-to-r from-slate-50 from-20% via-slate-50/70 via-50% to-transparent to-100%"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-slate-50 via-transparent to-transparent"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                
                <div class="text-center lg:text-left">
                    <p class="text-blue-600 font-bold tracking-wider uppercase text-sm mb-3">Sistem Informasi Manajemen Pelaporan, Notulensi & LHPD</p>
                    <h1 class="text-5xl lg:text-7xl font-black text-slate-900 tracking-tight leading-tight mb-6">
                        SIMPEL<span class="text-blue-600">-AI</span>
                    </h1>
                    <h2 class="text-3xl lg:text-4xl font-bold text-slate-800 leading-snug mb-6">
                        Mudah dalam Pelaporan,<br>
                        <span class="text-blue-600">Cepat Susun Notulensi & LHPD.</span>
                    </h2>
                    <p class="text-lg text-slate-700 mb-8 max-w-lg mx-auto lg:mx-0 leading-relaxed font-medium">
                        <strong class="text-slate-900">SIMPEL-AI</strong> adalah sistem digital inovatif untuk menyusun <strong>laporan kegiatan harian/mingguan</strong>, <strong>notulensi rapat</strong>, <strong>laporan hasil perjalanan dinas</strong>, dan <strong>arsip dokumen</strong> di lingkungan Dinas Komunikasi dan Informatika Kabupaten Ogan Komering Ilir.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3.5 rounded-full font-bold transition shadow-xl shadow-blue-600/30 flex justify-center items-center gap-2">
                                Buka Dashboard &rarr;
                            </a>
                        @else
                            <a href="{{ url('/login') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3.5 rounded-full font-bold transition shadow-xl shadow-blue-600/30 flex justify-center items-center gap-2">
                                Mulai Sekarang &rarr;
                            </a>
                        @endauth
                        <a href="#fitur" class="bg-white/80 backdrop-blur-sm hover:bg-white text-blue-600 border border-blue-200 px-8 py-3.5 rounded-full font-bold transition flex justify-center items-center gap-2 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Pelajari Fitur
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FITUR SECTION -->
    <section id="fitur" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <p class="text-blue-600 font-bold tracking-wider uppercase text-sm mb-2">Fitur Utama</p>
                <h2 class="text-3xl font-black text-slate-900 mb-4">Solusi Lengkap Manajemen Dokumen</h2>
                <p class="text-slate-600">Satu aplikasi terintegrasi dengan Kecerdasan Buatan (AI) untuk menyusun format dokumen standar instansi dalam hitungan detik.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- 1. Pelaporan Kegiatan -->
                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-8 hover:shadow-xl hover:shadow-blue-900/5 transition duration-300 group">
                    <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-600 group-hover:text-white transition">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Laporan Kegiatan</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Input dan kelola Laporan Harian/Mingguan dengan slot foto yang rapi dan sesuai format standar.</p>
                </div>

                <!-- 2. Notulensi Rapat -->
                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-8 hover:shadow-xl hover:shadow-purple-900/5 transition duration-300 group">
                    <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-purple-600 group-hover:text-white transition">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Notulensi Rapat</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Susun hasil rapat secara digital, dilengkapi daftar kehadiran, pimpinan rapat, dan notulis.</p>
                </div>

                <!-- 3. Perjalanan Dinas -->
                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-8 hover:shadow-xl hover:shadow-indigo-900/5 transition duration-300 group">
                    <div class="w-14 h-14 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-indigo-600 group-hover:text-white transition">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Laporan Hasil Perjalanan Dinas</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Ubah catatan mentah hasil perjalanan dinas menjadi narasi formal 5W1H secara instan.</p>
                </div>

                <!-- 4. Arsip Dokumen -->
                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-8 hover:shadow-xl hover:shadow-emerald-900/5 transition duration-300 group">
                    <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-emerald-600 group-hover:text-white transition">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Manajemen Arsip</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Fasilitas simpan Draft sementara, sinkronisasi ke Google Drive, dan pencarian dokumen lama.</p>
                </div>

                <!-- 5. Asisten AI -->
                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-8 hover:shadow-xl hover:shadow-cyan-900/5 transition duration-300 group">
                    <div class="w-14 h-14 bg-cyan-100 text-cyan-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-cyan-600 group-hover:text-white transition">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Asisten AI Cerdas</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Auto-generate judul dan tata bahasa laporan otomatis. Tersedia juga fitur Bypass untuk penulisan manual.</p>
                </div>

                <!-- 6. Aman & Terpercaya -->
                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-8 hover:shadow-xl hover:shadow-orange-900/5 transition duration-300 group">
                    <div class="w-14 h-14 bg-orange-100 text-orange-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-orange-600 group-hover:text-white transition">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.956 11.956 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Akses & Keamanan</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">Role akses Staf dan Admin terpisah, mengatur batas wewenang unggah dan kelola pegawai.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CARA KERJA SECTION -->
    <section id="cara-kerja" class="py-20 bg-slate-50 border-y border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-5">
                    <p class="text-blue-600 font-bold tracking-wider uppercase text-sm mb-2">Cara Kerja</p>
                    <h2 class="text-3xl lg:text-4xl font-black text-slate-900 mb-6 leading-tight">Dari Catatan Mentah Menjadi Laporan Formal</h2>
                    <p class="text-slate-600 mb-8 text-lg">Proses yang sederhana dengan hasil yang maksimal. SIMPEL-AI mempercepat penyusunan administrasi Anda dalam 4 langkah.</p>
                    <a href="#" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold transition shadow-md inline-flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        Lihat Panduan Lengkap
                    </a>
                </div>
                <div class="lg:col-span-7">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 relative">
                        <div class="text-center relative z-10">
                            <div class="w-16 h-16 mx-auto bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-black text-xl mb-4 border-4 border-white shadow-sm relative">
                                1
                                <div class="absolute -bottom-2 -right-2 bg-blue-600 text-white w-7 h-7 rounded-full flex items-center justify-center border-2 border-white">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                            </div>
                            <h4 class="font-bold text-slate-900 text-sm mb-1">Pilih Format</h4>
                            <p class="text-xs text-slate-500">Pilih Mingguan, Notulensi, atau Perjalanan Dinas.</p>
                        </div>
                        <div class="text-center relative z-10">
                            <div class="w-16 h-16 mx-auto bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-black text-xl mb-4 border-4 border-white shadow-sm relative">
                                2
                                <div class="absolute -bottom-2 -right-2 bg-blue-600 text-white w-7 h-7 rounded-full flex items-center justify-center border-2 border-white">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                            </div>
                            <h4 class="font-bold text-slate-900 text-sm mb-1">Input Catatan</h4>
                            <p class="text-xs text-slate-500">Ketik poin mentah & unggah foto dokumentasi.</p>
                        </div>
                        <div class="text-center relative z-10">
                            <div class="w-16 h-16 mx-auto bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-black text-xl mb-4 border-4 border-white shadow-sm relative">
                                3
                                <div class="absolute -bottom-2 -right-2 bg-blue-600 text-white w-7 h-7 rounded-full flex items-center justify-center border-2 border-white">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                            </div>
                            <h4 class="font-bold text-slate-900 text-sm mb-1">Proses AI</h4>
                            <p class="text-xs text-slate-500">Kecerdasan Buatan menyusun narasi dokumen.</p>
                        </div>
                        <div class="text-center relative z-10">
                            <div class="w-16 h-16 mx-auto bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-black text-xl mb-4 border-4 border-white shadow-sm relative">
                                4
                                <div class="absolute -bottom-2 -right-2 bg-blue-600 text-white w-7 h-7 rounded-full flex items-center justify-center border-2 border-white">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                            </div>
                            <h4 class="font-bold text-slate-900 text-sm mb-1">Finalisasi Unduh</h4>
                            <p class="text-xs text-slate-500">File Word baku jadi & tersimpan di Drive.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- TENTANG SECTION -->
    <section id="tentang" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="order-2 lg:order-1 relative">
                    <img src="{{ asset('foto_kantor.jpg') }}" alt="Gedung Diskominfo" class="rounded-3xl shadow-2xl object-cover h-[500px] w-full">
                    <div class="absolute -bottom-6 -right-6 bg-slate-900 text-white p-6 rounded-2xl shadow-xl max-w-xs hidden sm:block">
                        <svg class="w-10 h-10 text-blue-500 mb-3" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/></svg>
                        <p class="font-medium text-sm leading-relaxed">"Teknologi untuk pelayanan informasi yang lebih baik dan birokrasi yang lebih cerdas."</p>
                    </div>
                </div>
                <div class="order-1 lg:order-2">
                    <p class="text-blue-600 font-bold tracking-wider uppercase text-sm mb-2">Tentang SIMPEL-AI</p>
                    <h2 class="text-3xl lg:text-4xl font-black text-slate-900 mb-6">Mengenal SIMPEL-AI</h2>
                    <div class="space-y-4 text-slate-600 text-lg leading-relaxed mb-8">
                        <p>SIMPEL-AI merupakan sistem informasi manajemen berbasis web yang dikembangkan untuk membantu pengelolaan <strong>laporan kegiatan</strong>, <strong>notulensi rapat</strong>, <strong>laporan hasil perjalanan dinas (LHPD)</strong>, serta arsip dokumen secara terstruktur di lingkungan Dinas Komunikasi dan Informatika Kabupaten Ogan Komering Ilir.</p>
                        <p>Dengan memanfaatkan teknologi digital dan integrasi kecerdasan buatan, SIMPEL-AI hadir untuk meningkatkan efisiensi kerja, mempercepat proses administrasi, dan memastikan setiap laporan terdokumentasi dengan baik sesuai standar.</p>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-6 border-t border-slate-100">
                        <div class="flex gap-4">
                            <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0 text-blue-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900">Efisien</h4>
                                <p class="text-sm text-slate-500">Menghemat waktu dan tenaga aparatur.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0 text-blue-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900">Terstruktur</h4>
                                <p class="text-sm text-slate-500">Data tersimpan rapi dan mudah dicari.</p>
                            </div>
                        </div>
                        <div class="flex gap-4 sm:col-span-2">
                            <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0 text-blue-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900">Mendukung Kinerja</h4>
                                <p class="text-sm text-slate-500">Memperkuat pelayanan informasi publik melalui pelaporan yang akurat.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-slate-900 text-white pt-16 pb-8 border-t-4 border-blue-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 lg:gap-12 border-b border-slate-700 pb-12 mb-8">
                
                <div class="md:col-span-5">
                    <div class="flex items-center gap-2.5 mb-5">
                        <img src="{{ asset('logo-simpel-ai.svg') }}" alt="Ikon SIMPEL-AI" class="h-8 w-auto">
                        <span class="font-black text-2xl tracking-tight text-white mt-0.5">SIMPEL<span class="text-blue-500">-AI</span></span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed mb-6 max-w-sm">
                        Sistem Informasi Manajemen Pelaporan,<br>Notulensi & LHPD<br>
                        Dinas Komunikasi dan Informatika<br>
                        Kabupaten Ogan Komering Ilir
                    </p>
                </div>

                <div class="md:col-span-2">
                    <h4 class="font-bold text-lg mb-4">Tautan</h4>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><a href="#" class="hover:text-blue-400 transition">Beranda</a></li>
                        <li><a href="#fitur" class="hover:text-blue-400 transition">Fitur</a></li>
                        <li><a href="#cara-kerja" class="hover:text-blue-400 transition">Panduan</a></li>
                        <li><a href="#tentang" class="hover:text-blue-400 transition">Tentang</a></li>
                    </ul>
                </div>

                <div class="md:col-span-3">
                    <h4 class="font-bold text-lg mb-4">Hubungi Kami</h4>
                    <ul class="space-y-3 text-sm text-slate-400">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span>Jalan Letjen Yusuf Singedekane Nomor 01, Jua-jua, Kayuagung<br>Kab. Ogan Komering Ilir, Sumatera Selatan</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span> diskominfooki2@gmail.com </span>
                        </li>
                    </ul>
                </div>

<div class="md:col-span-2">
                    <h4 class="font-bold text-lg mb-4">Ikuti Kami</h4>
                    <div class="flex gap-4">
                        <!-- Instagram -->
                        <a href="https://www.instagram.com/diskominfo.oki?srtk=ZXJkY3llcjR0bG9q" class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-pink-600 hover:text-white transition" title="Instagram">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                        
                        <!-- TikTok -->
                        <a href="https://www.tiktok.com/@diskominfo.oki?_r=1&_t=ZS-9Ax6YfHC2Oi" class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-black hover:text-white transition" title="TikTok">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
                        </a>
                        
                        <!-- Facebook -->
                        <a href="https://www.facebook.com/share/1Ev5VSxmic/?mibextid=wwXIfr" class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-blue-700 hover:text-white transition" title="Facebook">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.293h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z"/></svg>
                        </a>
                        
                        <!-- YouTube -->
                        <a href="https://youtube.com/@kominfo_oki?si=rmWZbujX5v1z4RmU" class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-red-600 hover:text-white transition" title="YouTube">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.5 12 3.5 12 3.5s-7.505 0-9.377.55a3.015 3.015 0 0 0-2.122 2.136C0 8.07 0 12 0 12s0 3.93.501 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.55 9.377.55 9.377.55s7.505 0 9.377-.55a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>
                    </div>
                </div>

            </div>

            <div class="flex flex-col md:flex-row justify-between items-center text-xs text-slate-500">
                <p>&copy; 2026 SIMPEL-AI. Dinas Komunikasi dan Informatika Kab. Ogan Komering Ilir.</p>
                <p class="mt-2 md:mt-0">Versi 1.0 &bull; Bersama Teknologi, Pelayanan Publik Lebih Baik</p>
            </div>
        </div>
    </footer>
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