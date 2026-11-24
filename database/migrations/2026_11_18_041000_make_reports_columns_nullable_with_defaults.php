<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('jenis_dokumen')->default('Laporan Harian')->nullable()->change();
            $table->string('judul')->default('Laporan Kegiatan')->nullable()->change();
            $table->date('tanggal')->nullable()->change();
            $table->string('bidang')->default('Sekretariat')->nullable()->change();
            $table->text('html_hasil')->nullable()->change();
            $table->string('status')->default('draft')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            //
        });
    }
};
