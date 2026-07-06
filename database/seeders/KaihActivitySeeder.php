<?php

namespace Database\Seeders;

use App\Models\KaihActivity;
use Illuminate\Database\Seeder;

class KaihActivitySeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            ['key' => 'bangun_pagi',    'label' => 'Bangun Pagi',    'description' => 'Bangun sebelum pukul 04.30-05.00 WIB, merapikan tempat tidur',                               'group' => 'rutinitas',  'sort_order' => 1],
            ['key' => 'sholat_subuh',   'label' => 'Sholat Subuh',   'description' => 'Melaksanakan sholat subuh tepat waktu',                                                              'group' => 'ibadah',     'sort_order' => 2],
            ['key' => 'sholat_dhuha',   'label' => 'Sholat Dhuha',   'description' => 'Melaksanakan sholat dhuha',                                                                           'group' => 'ibadah',     'sort_order' => 3],
            ['key' => 'sholat_dzuhur',  'label' => 'Sholat Dzuhur',  'description' => 'Melaksanakan sholat dzuhur (khusus Jumat diganti Sholat Jumat untuk putra)',                           'group' => 'ibadah',     'sort_order' => 4],
            ['key' => 'sholat_ashar',   'label' => 'Sholat Ashar',   'description' => 'Melaksanakan sholat ashar tepat waktu',                                                               'group' => 'ibadah',     'sort_order' => 5],
            ['key' => 'sholat_maghrib', 'label' => 'Sholat Maghrib', 'description' => 'Melaksanakan sholat maghrib tepat waktu',                                                             'group' => 'ibadah',     'sort_order' => 6],
            ['key' => 'sholat_isya',    'label' => 'Sholat Isya',    'description' => 'Melaksanakan sholat isya tepat waktu',                                                                'group' => 'ibadah',     'sort_order' => 7],
            ['key' => 'olahraga',       'label' => 'Berolahraga',    'description' => 'Minimal 30 menit (senam, jalan kaki, kegiatan fisik MPLS, dll)',                                      'group' => 'fisik',      'sort_order' => 8],
            ['key' => 'makan_sehat',    'label' => 'Makan Sehat & Bergizi', 'description' => 'Sarapan, makan siang, dan makan malam bergizi seimbang',                                        'group' => 'kesehatan',  'sort_order' => 9],
            ['key' => 'gemar_belajar',  'label' => 'Gemar Belajar',  'description' => 'Membaca buku, mengulang materi, mengerjakan tugas MPLS',                                              'group' => 'belajar',    'sort_order' => 10],
            ['key' => 'bermasyarakat',  'label' => 'Bermasyarakat',  'description' => 'Membantu orang tua, berinteraksi/bekerja sama dengan teman baru',                                      'group' => 'sosial',     'sort_order' => 11],
            ['key' => 'tidur_cepat',    'label' => 'Tidur Cepat',    'description' => 'Tidur sebelum pukul 21.00-22.00 WIB',                                                                 'group' => 'istirahat',  'sort_order' => 12],
        ];

        foreach ($activities as $activity) {
            KaihActivity::create($activity);
        }
    }
}
