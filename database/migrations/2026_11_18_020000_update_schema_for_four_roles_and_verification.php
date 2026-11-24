<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Table users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'nip')) {
                $table->string('nip')->nullable()->unique()->after('name');
            }
            $table->string('email')->nullable()->change();
            $table->string('role')->default('pegawai')->change();
        });

        // 2. Table reports
        Schema::table('reports', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
            
            if (!Schema::hasColumn('reports', 'catatan_revisi')) {
                $table->text('catatan_revisi')->nullable()->after('status');
            }
            if (!Schema::hasColumn('reports', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('catatan_revisi');
            }
            if (!Schema::hasColumn('reports', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('reports', 'is_manual_edit')) {
                $table->boolean('is_manual_edit')->default(false)->after('html_hasil');
            }
        });

        // 3. Migrate existing data
        DB::table('users')->whereIn('role', ['staf', 'user'])->update(['role' => 'pegawai']);
        DB::table('users')->where('role', 'admin_bidang')->update(['role' => 'admin']);
        DB::table('reports')->where('status', 'final')->update(['status' => 'approved']);

        // Set NIP for existing users if null
        $users = DB::table('users')->whereNull('nip')->get();
        foreach ($users as $index => $u) {
            $generatedNip = '19850101' . str_pad((string)$u->id, 10, '0', STR_PAD_LEFT);
            DB::table('users')->where('id', $u->id)->update([
                'nip' => $generatedNip,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'catatan_revisi')) {
                $table->dropColumn('catatan_revisi');
            }
            if (Schema::hasColumn('reports', 'approved_by')) {
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('reports', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('reports', 'is_manual_edit')) {
                $table->dropColumn('is_manual_edit');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'nip')) {
                $table->dropColumn('nip');
            }
        });
    }
};
