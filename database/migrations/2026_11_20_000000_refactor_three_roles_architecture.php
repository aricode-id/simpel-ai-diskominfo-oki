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
        // 1. Migrasi role lama ke skema 3 role: super_admin, admin_bidang, pegawai
        DB::table('users')->where('role', 'admin')->update(['role' => 'admin_bidang']);
        DB::table('users')->where('role', 'kabid')->update(['role' => 'pegawai']);

        // 2. Inisialisasi / Update 7 Akun Jabatan Utama Sistem
        $jabatanAccounts = [
            [
                'nip' => '111111',
                'name' => 'Super Admin',
                'email' => '111111@diskominfo.local',
                'role' => 'super_admin',
                'bidang' => null,
                'jabatan' => 'Administrator Sistem',
                'status_pegawai' => 'ASN',
            ],
            [
                'nip' => '2221',
                'name' => 'Admin Sekretariat',
                'email' => '2221@diskominfo.local',
                'role' => 'admin_bidang',
                'bidang' => 'Sekretariat',
                'jabatan' => 'Admin Bidang Sekretariat',
                'status_pegawai' => 'ASN',
            ],
            [
                'nip' => '2222',
                'name' => 'Admin Statistik',
                'email' => '2222@diskominfo.local',
                'role' => 'admin_bidang',
                'bidang' => 'Statistik',
                'jabatan' => 'Admin Bidang Statistik',
                'status_pegawai' => 'ASN',
            ],
            [
                'nip' => '2223',
                'name' => 'Admin PKP',
                'email' => '2223@diskominfo.local',
                'role' => 'admin_bidang',
                'bidang' => 'PKP',
                'jabatan' => 'Admin Bidang PKP',
                'status_pegawai' => 'ASN',
            ],
            [
                'nip' => '2224',
                'name' => 'Admin TIK',
                'email' => '2224@diskominfo.local',
                'role' => 'admin_bidang',
                'bidang' => 'TIK',
                'jabatan' => 'Admin Bidang TIK',
                'status_pegawai' => 'ASN',
            ],
            [
                'nip' => '2225',
                'name' => 'Admin E-Gov',
                'email' => '2225@diskominfo.local',
                'role' => 'admin_bidang',
                'bidang' => 'E-Gov',
                'jabatan' => 'Admin Bidang E-Gov',
                'status_pegawai' => 'ASN',
            ],
            [
                'nip' => '2226',
                'name' => 'Admin Persandian',
                'email' => '2226@diskominfo.local',
                'role' => 'admin_bidang',
                'bidang' => 'Persandian',
                'jabatan' => 'Admin Bidang Persandian',
                'status_pegawai' => 'ASN',
            ],
        ];

        foreach ($jabatanAccounts as $acc) {
            $existing = DB::table('users')->where('nip', $acc['nip'])->first();
            if ($existing) {
                DB::table('users')->where('id', $existing->id)->update([
                    'role' => $acc['role'],
                    'bidang' => $acc['bidang'],
                    'jabatan' => $acc['jabatan'],
                    'status_pegawai' => $acc['status_pegawai'],
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('users')->insert([
                    'nip' => $acc['nip'],
                    'name' => $acc['name'],
                    'email' => $acc['email'],
                    'password' => Hash::make($acc['nip']),
                    'role' => $acc['role'],
                    'bidang' => $acc['bidang'],
                    'jabatan' => $acc['jabatan'],
                    'status_pegawai' => $acc['status_pegawai'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('role', 'admin_bidang')->update(['role' => 'admin']);
    }
};
