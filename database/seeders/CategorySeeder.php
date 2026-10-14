<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Kebakaran', 'urgency_level' => 'high'],
            ['name' => 'Kedaruratan Medis & Kesehatan', 'urgency_level' => 'high'],
            ['name' => 'Tindak Kriminalitas & Keamanan', 'urgency_level' => 'high'],
            ['name' => 'Bencana Alam (Banjir, Tanah Longsor)', 'urgency_level' => 'high'],
            ['name' => 'Gangguan Lalu Lintas & Kecelakaan', 'urgency_level' => 'high'],
            ['name' => 'Infrastruktur Jalan & Jembatan Rusak', 'urgency_level' => 'normal'],
            ['name' => 'Fasilitas Publik Terbengkalai', 'urgency_level' => 'normal'],
            ['name' => 'Pencemaran Lingkungan & Sampah', 'urgency_level' => 'normal'],
            ['name' => 'Masalah Utilitas (Listrik, Air, Gas)', 'urgency_level' => 'normal'],
            ['name' => 'Perlindungan Hewan Liar', 'urgency_level' => 'normal'],
            ['name' => 'Laporan Lainnya', 'urgency_level' => 'normal'],
        ];

        Schema::disableForeignKeyConstraints();
        DB::table('categories')->truncate();
        Schema::enableForeignKeyConstraints();

        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'urgency_level' => $category['urgency_level'],
            ];
        }

        DB::table('categories')->insert($data);
    }
}