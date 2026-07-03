<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tugas;
use App\Models\Presensi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::create([
            'name' => 'Admin Fortasi',
            'email' => 'admin@fortasi.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'nis' => null,
            'kelas' => null,
        ]);

        $siswa = User::create([
            'name' => 'Ahmad Fauzi',
            'email' => 'ahmad@fortasi.test',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'nis' => '2024001',
            'kelas' => 'X-A',
        ]);

        User::create([
            'name' => 'Siti Nurhaliza',
            'email' => 'siti@fortasi.test',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'nis' => '2024002',
            'kelas' => 'X-A',
        ]);

        User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@fortasi.test',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'nis' => '2024003',
            'kelas' => 'X-B',
        ]);

        $tugas1 = Tugas::create([
            'judul' => 'Membuat Essay Perkenalan',
            'deskripsi' => 'Tulis essay tentang diri kamu, mimpi dan tujuan selama bersekolah di sini. Minimal 500 kata.',
            'deadline' => now()->addDays(3),
            'created_by' => 1,
        ]);

        Tugas::create([
            'judul' => 'Membuat Poster Kelas',
            'deskripsi' => 'Buat poster digital tentang kelas impian. Kumpulkan dalam format PDF atau JPG.',
            'deadline' => now()->addDays(7),
            'created_by' => 1,
        ]);

        Presensi::create([
            'user_id' => 2,
            'tanggal' => today(),
            'check_in' => now()->setTime(7, 30),
            'check_out' => now()->setTime(14, 0),
            'foto_check_in' => null,
            'foto_check_out' => null,
        ]);
    }
}
