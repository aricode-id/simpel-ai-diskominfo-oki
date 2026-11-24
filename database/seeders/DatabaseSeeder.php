<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 7 Akun Jabatan Utama Sistem (ID Jabatan 111111 & 2221-2226)
        $accounts = [
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
                'name' => 'Admin Bidang Sekretariat',
                'email' => '2221@diskominfo.local',
                'role' => 'admin_bidang',
                'bidang' => 'Sekretariat',
                'jabatan' => 'Admin Bidang Sekretariat',
                'status_pegawai' => 'ASN',
            ],
            [
                'nip' => '2222',
                'name' => 'Admin Bidang Statistik',
                'email' => '2222@diskominfo.local',
                'role' => 'admin_bidang',
                'bidang' => 'Statistik',
                'jabatan' => 'Admin Bidang Statistik',
                'status_pegawai' => 'ASN',
            ],
            [
                'nip' => '2223',
                'name' => 'Admin Bidang PKP',
                'email' => '2223@diskominfo.local',
                'role' => 'admin_bidang',
                'bidang' => 'PKP',
                'jabatan' => 'Admin Bidang PKP',
                'status_pegawai' => 'ASN',
            ],
            [
                'nip' => '2224',
                'name' => 'Admin Bidang TIK',
                'email' => '2224@diskominfo.local',
                'role' => 'admin_bidang',
                'bidang' => 'TIK',
                'jabatan' => 'Admin Bidang TIK',
                'status_pegawai' => 'ASN',
            ],
            [
                'nip' => '2225',
                'name' => 'Admin Bidang E-Gov',
                'email' => '2225@diskominfo.local',
                'role' => 'admin_bidang',
                'bidang' => 'E-Gov',
                'jabatan' => 'Admin Bidang E-Gov',
                'status_pegawai' => 'ASN',
            ],
            [
                'nip' => '2226',
                'name' => 'Admin Bidang Persandian',
                'email' => '2226@diskominfo.local',
                'role' => 'admin_bidang',
                'bidang' => 'Persandian',
                'jabatan' => 'Admin Bidang Persandian',
                'status_pegawai' => 'ASN',
            ],
        ];

        foreach ($accounts as $acc) {
            User::updateOrCreate(
                ['nip' => $acc['nip']],
                [
                    'name' => $acc['name'],
                    'email' => $acc['email'],
                    'password' => Hash::make($acc['nip']),
                    'role' => $acc['role'],
                    'bidang' => $acc['bidang'],
                    'jabatan' => $acc['jabatan'],
                    'status_pegawai' => $acc['status_pegawai'],
                ]
            );
        }
    }
}
