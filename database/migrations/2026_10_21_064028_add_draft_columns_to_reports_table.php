<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('reports', function (Blueprint $table) {
            // Kolom penanda apakah ini masih 'draft' atau sudah 'final'
            $table->string('status')->default('final')->after('bidang');
            // Kolom penyimpan data input mentah (JSON) untuk di-edit lagi nanti
            $table->json('form_data')->nullable()->after('status');
            
            // Opsional: Bikin html_hasil jadi nullable kalau dulunya required
            // (Karena pas masih draft, HTML final/Word belum terbentuk)
            $table->longText('html_hasil')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['status', 'form_data']);
        });
    }
};