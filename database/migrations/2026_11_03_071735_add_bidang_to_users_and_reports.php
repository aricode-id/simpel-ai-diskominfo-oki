<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Cek dan tambah kolom 'role' jika belum ada
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('user')->after('email');
            });
        }

        // Cek dan tambah kolom 'bidang' di tabel users jika belum ada
        if (!Schema::hasColumn('users', 'bidang')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('bidang', [
                    'Sekretariat', 'Statistik', 'PKP', 'TIK', 'E-Gov', 'Persandian'
                ])->nullable()->after('role');
            });
        }

        // Cek dan tambah kolom 'bidang' di tabel reports jika belum ada
        if (!Schema::hasColumn('reports', 'bidang')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->enum('bidang', [
                    'Sekretariat', 'Statistik', 'PKP', 'TIK', 'E-Gov', 'Persandian'
                ])->nullable()->after('status');
            });
        }
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'bidang']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('bidang');
        });
    }
};