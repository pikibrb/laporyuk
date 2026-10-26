<?php

namespace Database\Seeders;

use App\Models\Hotline;
use Illuminate\Database\Seeder;

class HotlineSeeder extends Seeder
{
    private array $hotlines = [
        ['number' => '112', 'label' => 'Darurat nasional', 'description' => 'Satu nomor untuk semua keadaan darurat, gratis dan aktif 24 jam.', 'sort_order' => 1],
        ['number' => '110', 'label' => 'Kepolisian', 'description' => 'Tindak kriminalitas, gangguan keamanan, dan kecelakaan lalu lintas.', 'sort_order' => 2],
        ['number' => '113', 'label' => 'Pemadam kebakaran', 'description' => 'Kebakaran bangunan, lahan, serta penyelamatan terjebak.', 'sort_order' => 3],
        ['number' => '119', 'label' => 'Ambulans & medis', 'description' => 'Kedaruratan medis dan permintaan rujukan rumah sakit.', 'sort_order' => 4],
        ['number' => '115', 'label' => 'Basarnas (SAR)', 'description' => 'Pencarian dan pertolongan di darat, laut, maupun udara.', 'sort_order' => 5],
        ['number' => '123', 'label' => 'PLN', 'description' => 'Gangguan listrik, kabel jatuh, dan jaringan yang membahayakan.', 'sort_order' => 6],
    ];

    public function run(): void
    {
        foreach ($this->hotlines as $hotline) {
            Hotline::query()->updateOrCreate(
                ['number' => $hotline['number']],
                $hotline + ['is_active' => true],
            );
        }
    }
}
