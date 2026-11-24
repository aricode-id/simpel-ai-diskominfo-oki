<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use Illuminate\Support\Facades\File;

class CleanOrphanedFiles extends Command
{
    // Ini nama perintah yang bakal lo ketik di terminal
    protected$signature = 'app:bersihkan-sampah';
    protected $description = 'Menghapus file dokumen dan foto sampah yang tidak terikat di database';

    public function handle()
    {
        $this->info("Memulai proses scanning database SIMPEL-AI...");

        $reports = Report::all();
        $activeDocs = [];
        $activePhotos = [];

        // 1. KUMPULKAN DAFTAR FILE VIP (YANG MASIH AKTIF DI DATABASE)
        foreach ($reports as $report) {
            if (!empty($report->html_hasil)) {
                $activeDocs[] = basename($report->html_hasil);
            }

            $formData = $report->form_data ?? [];
            
            // Cek foto Laporan Harian
            foreach ($formData['kegiatan'] ?? [] as $keg) {
                for ($f = 1; $f <= 3; $f++) {
                    if (!empty($keg["foto_{$f}_path"])) $activePhotos[] = basename($keg["foto_{$f}_path"]);
                }
            }

            // Cek foto Laporan Mingguan
            foreach ($formData['mingguan'] ?? [] as $hari) {
                foreach ($hari['kegiatan'] ?? [] as $keg) {
                    for ($f = 1; $f <= 3; $f++) {
                        if (!empty($keg["foto_{$f}_path"])) $activePhotos[] = basename($keg["foto_{$f}_path"]);
                    }
                }
            }

            // Cek foto Perjalanan Dinas & Kop
            for ($f = 1; $f <= 2; $f++) {
                if (!empty($formData["sppd_foto_{$f}_path"])) $activePhotos[] = basename($formData["sppd_foto_{$f}_path"]);
            }
            if (!empty($formData['logo_kop_path'])) $activePhotos[] = basename($formData['logo_kop_path']);
        }

        $activeDocs = array_unique($activeDocs);
        $activePhotos = array_unique($activePhotos);

        $this->info("Ditemukan " . count($activeDocs) . " dokumen dan " . count($activePhotos) . " foto yang masih AKTIF. Mengamankan file...");

        $deletedDocsCount = 0;
        $deletedPhotosCount = 0;

        // 2. EKSEKUSI FOLDER DOKUMEN (.docx)
        $docsPath = storage_path('app/public/reports');
        if (File::exists($docsPath)) {
            $files = File::files($docsPath);
            foreach ($files as $file) {
                if ($file->getFilename() === '.gitignore') continue;

                // Jika file fisik TIDAK ADA di Daftar VIP, HAPUS!
                if (!in_array($file->getFilename(), $activeDocs)) {
                    File::delete($file);
                    $deletedDocsCount++;
                    $this->line("Dihapus (Doc): " . $file->getFilename());
                }
            }
        }

        // 3. EKSEKUSI FOLDER FOTO (reports_assets)
        $photosPath = storage_path('app/public/reports_assets');
        if (File::exists($photosPath)) {
            $files = File::files($photosPath);
            foreach ($files as $file) {
                if ($file->getFilename() === '.gitignore') continue;

                // Jika foto fisik TIDAK ADA di Daftar VIP, HAPUS!
                if (!in_array($file->getFilename(), $activePhotos)) {
                    File::delete($file);
                    $deletedPhotosCount++;
                    $this->line("Dihapus (Foto): " . $file->getFilename());
                }
            }
        }

        $this->info("BERHASIL! VPS lo udah bersih. Total yang dihapus: {$deletedDocsCount} Dokumen & {$deletedPhotosCount} Foto Sampah.");
    }
}