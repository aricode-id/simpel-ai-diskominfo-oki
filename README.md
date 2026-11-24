# SIMPEL-AI
### Sistem Informasi Manajemen Pelaporan & Notulensi AI
**Dinas Komunikasi dan Informatika (Diskominfo) Kabupaten Ogan Komering Ilir**

---

## 📌 Tentang SIMPEL-AI

**SIMPEL-AI** adalah platform web terpadu berbasis kecerdasan buatan (*Artificial Intelligence*) yang dikembangkan khusus untuk mengotomatisasi, menstandarkan, dan mempercepat penyusunan administrasi kedinasan di lingkungan Dinas Komunikasi dan Informatika Kabupaten Ogan Komering Ilir. 

Aplikasi ini mentransformasikan catatan kerja lapangan aparatur sipil negara (ASN) maupun tenaga ahli yang ringkas dan mentah menjadi naskah dinas pemerintahan formal berstandar EYD dengan kaidah **5W+1H**, serta mengekspornya langsung ke dalam format dokumen Microsoft Word (`.docx`) siap cetak yang selaras dengan Tata Naskah Dinas resmi.

---

## 🚀 Fitur Unggulan

### 1. Penyusunan Narasi Berbasis 5W+1H dengan Google Gemini AI
- Mengonversi poin kegiatan mentah menjadi 1 paragraf narasi dinas formal yang mengalir, komprehensif, dan baku.
- Menjamin kelengkapan unsur **5W+1H** (*What, Who, When, Where, Why, How*) dengan mengintegrasikan metadata agenda (waktu, lokasi, pelaksana, dan tujuan).
- Mekanisme **Multi-Model Auto-Fallback** tangguh: secara cerdas mencoba model secara berurutan (`gemini-3.6-flash` $\rightarrow$ `gemini-3.5-flash` $\rightarrow$ `gemini-flash-lite-latest` $\rightarrow$ `gemma-4-31b-it`) dengan penanganan otomatis jika terkena *rate limit* (HTTP 429/503).
- Pemrosesan batch efisien untuk Laporan Mingguan dalam satu kali pemanggilan API terpadu.

### 2. Manajemen 4 Jenis Instrumen Naskah Kedinasan
- **Laporan Kegiatan Harian:** Bukti dukung individu aparatur untuk e-Kinerja, tanpa sub-judul bidang karena unit kerja telah tercantum pada bagian Identitas Pelaksana.
- **Laporan Mingguan:** Rangkuman kinerja kolektif unit kerja/bidang selama satu pekan kerja (Senin s.d. Jumat).
- **Notulensi Rapat:** Pencatatan hasil agenda rapat, pimpinan sidang, daftar peserta, notulis, pembahasan, serta butir keputusan rapat.
- **Laporan Hasil Perjalanan Dinas (LHPD):** Laporan akuntabilitas perjalanan dinas lengkap dengan daftar petugas pelaksana tugas dan hasil yang dicapai.

### 3. Ekspor Dokumen Word (`.docx`) Presisi Tinggi
- Ditenagai engine **PHPWord** dengan penataan tipografi dinas (Arial 10/11 pt).
- Pilihan penggunaan **Kop Surat Resmi Pemerintah Kabupaten OKI** (vektor/raster jernih) atau mode polos tanpa kop.
- **Blok Tanda Tangan Adaptif:** 100% mengikuti opsi dropdown:
  - *Gunakan TTD Mengetahui:* Layout 2 kolom sejajar simetris (50% Kiri: Mengetahui Atasan/Kepala Bidang, 50% Kanan: Yang Menyusun).
  - *Tanpa TTD Mengetahui:* Layout 1 kolom mandiri di sebelah kanan untuk penyusun.

### 4. Optimalisasi & Kompresi Foto Dokumentasi
- Pengompresan otomatis berkas gambar lapangan ke resolusi optimal (maksimal 1000 piksel, kualitas 72%) untuk menjaga ukuran file tetap ringan dan mencegah *memory limit* server.
- Penataan slot grid foto simetris genap:
  - 4 Slot Foto (Grid 2x2) untuk Laporan Harian dan Laporan Mingguan.
  - 2 Slot Foto (Grid 2 Kolom) untuk Notulensi Rapat dan Perjalanan Dinas (LHPD).

### 5. Arsip Cadangan Cloud Google Drive API
- Terintegrasi dengan **Google OAuth 2.0** dan **Google Drive API** untuk sinkronisasi penyimpanan dokumen hasil verifikasi secara terpusat di cloud dinas.

### 6. Kontrol Akses Berbasis Peran (RBAC) & Monitoring
- **Super Admin (Administrator Global):** Memantau seluruh arsip laporan 6 bidang, mengelola data pengguna dinas, dan menganalisis rekapitulasi kedisiplinan pelaporan seluruh pegawai.
- **Admin Bidang (6 Bidang Fungsional):** Memverifikasi laporan staf (Approve / Reject dengan catatan perbaikan), mengelola akun bidang, dan memonitor pegawai di bidangnya (*Sekretariat, Statistik, PKP, TIK, E-Gov, Persandian*).
- **Pegawai (ASN & Tenaga Ahli):** Dasbor statistik pribadi, pembuatan laporan baru, pratinjau langsung (*Live Web Preview*), tindak lanjut revisi, dan unduh dokumen yang telah disahkan.

---

## 🛠️ Tumpukan Teknologi (Tech Stack)

| Komponen | Teknologi yang Digunakan |
| :--- | :--- |
| **Framework Backend** | [Laravel 11/12](https://laravel.com/) (PHP ^8.3 / PHP 8.5) |
| **Basis Data** | [MySQL](https://www.mysql.com/) / [MariaDB](https://mariadb.org/) |
| **Styling & Tampilan** | [Tailwind CSS v4](https://tailwindcss.com/), PostCSS, Vite |
| **Generasi Dokumen Word** | [PHPOffice / PHPWord](https://github.com/PHPOffice/PHPWord) |
| **Kecerdasan Buatan (AI)** | [Google Gemini Generative AI API](https://ai.google.dev/) |
| **Layanan Cloud & SSO** | [Laravel Socialite](https://laravel.com/docs/socialite), Google Client API (Drive) |

---

## 📦 Panduan Instalasi Lokal

Ikuti langkah-langkah berikut untuk menjalankan SIMPEL-AI di lingkungan pengembangan lokal (*Local Development*):

### 1. Prasyarat Sistem
Pastikan perangkat Anda telah terpasang:
- **PHP** versi 8.3 atau 8.5 (dengan ekstensi `gd`, `zip`, `pdo_mysql`, `curl`, `mbstring`, `fileinfo`)
- **Composer** (v2.x)
- **Node.js** (v18+) & **NPM**
- **MySQL** atau **MariaDB** (dapat menggunakan Laragon, XAMPP, atau Docker)

### 2. Kloning Repository
```bash
git clone https://github.com/username/simpel-ai.git
cd simpel-ai
```

### 3. Pasang Dependensi PHP & JavaScript
```bash
composer install
npm install
```

### 4. Konfigurasi Lingkungan (`.env`)
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
Buka file `.env` dan sesuaikan koneksi database serta kunci API Anda:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simpel_ai
DB_USERNAME=root
DB_PASSWORD=

# Kunci API Google Gemini (Dapatkan di https://aistudio.google.com/)
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=gemini-3.6-flash

# Google OAuth & Drive API (Dapatkan di Google Cloud Console)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI="http://localhost:8000/auth/google/callback"
```

### 5. Generate Application Key & Migrasi Database
```bash
php artisan key:generate
php artisan migrate --seed
```

### 6. Buat Symlink Storage Publik
Pastikan folder storage publik terhubung agar foto lampiran dapat diakses peramban:
```bash
php artisan storage:link
```

### 7. Jalankan Server Pengembangan
Jalankan kompilasi aset frontend dan web server Laravel:
```bash
# Terminal 1: Kompilasi frontend
npm run dev

# Terminal 2: Web Server Laravel
php artisan serve
```
Aplikasi kini dapat diakses melalui peramban di: **`http://localhost:8000`**

---

## 👤 Akun Bawaan untuk Uji Coba (Seeder)

Setelah menjalankan `php artisan migrate --seed`, database akan otomatis terisi akun jabatan dinas dengan password default sama dengan NIP/ID masing-masing:

| Peran / Jabatan | NIP / ID Login | Password Default | Unit Kerja / Bidang |
| :--- | :---: | :---: | :--- |
| **Super Admin** | `111111` | `111111` | Akses Global (Lintas Bidang) |
| **Admin Bidang** | `2221` | `2221` | Sekretariat |
| **Admin Bidang** | `2222` | `2222` | Statistik |
| **Admin Bidang** | `2223` | `2223` | PKP (Pengelolaan Komunikasi Publik) |
| **Admin Bidang** | `2224` | `2224` | TIK (Infrastruktur TIK) |
| **Admin Bidang** | `2225` | `2225` | E-Gov (Layanan E-Government) |
| **Admin Bidang** | `2226` | `2226` | Persandian (Keamanan Informasi & Persandian) |

> **Catatan:** Setelah masuk pertama kali pada mode produksi, disarankan segera mengganti password akun melalui menu profil pengguna.

---

## 📂 Struktur Direktori Utama

```
simpel-ai/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AdminController.php     # Controller Dasbor Manajemen & Monitoring
│   │   │   ├── AuthController.php      # Controller Otentikasi NIP & Google SSO
│   │   │   ├── ReportController.php    # Core Controller (Gemini AI, PHPWord, Laporan)
│   │   │   └── UserController.php      # Controller Manajemen Akun Pengguna
│   │   └── Middleware/                 # AdminMiddleware, CekBidangKosong
│   └── Models/
│       ├── Report.php                  # Model Laporan Kedinasan & Verifikasi
│       └── User.php                    # Model Pengguna & Relasi Hak Akses
├── config/                             # Konfigurasi Layanan & Framework
├── database/
│   ├── migrations/                     # Skema Tabel & Relasi Basis Data
│   └── seeders/                        # Data Awal Akun Jabatan Fungsional
├── resources/
│   ├── views/                          # Antarmuka Pengguna (Blade Templates)
│   │   ├── admin/                      # Tampilan Khusus Verifikator & Admin
│   │   ├── auth/                       # Tampilan Login & Reset Password
│   │   ├── layouts/                    # Layout Utama Naskah Dinas & Dasbor
│   │   └── user/                       # Tampilan Dasbor Pegawai & Form Laporan
│   ├── css/ & js/                      # Aset Styling Tailwind CSS & Skrip
├── routes/
│   └── web.php                         # Definisi Perutean Aplikasi & Hak Akses
├── storage/                            # Penyimpanan Dokumen Word & Foto Lampiran
└── public/                             # Root Direktori Publik & Aset Web
```

---

## 🔒 Keamanan & Kebijakan Repositori Publik

Untuk menjaga keamanan informasi kedinasan dan kepatuhan privasi aparatur:
- **Kunci Rahasia & Token:** File `.env`, `.env.*`, token OAuth Google, dan kunci privat diabaikan secara ketat oleh `.gitignore`.
- **Media Dokumentasi:** Berkas foto kegiatan dan arsip Word `.docx` yang tersimpan pada `storage/` tidak dilacak dalam commit Git.
- **Validasi Identitas:** Validasi NIP/ID Login mendukung format alfanumerik aman (`TA-001` s.d. `TA-010`, ID numerik 4–6 digit, hingga NIP 18–19 digit).

---

## 📄 Lisensi & Hak Cipta

Proyek ini dikembangkan sebagai karya luaran program magang di **Dinas Komunikasi dan Informatika Kabupaten Ogan Komering Ilir**. Didistribusikan di bawah lisensi terbuka [MIT License](LICENSE).
