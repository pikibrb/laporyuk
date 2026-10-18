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
            ['name' => 'Pencemaran Sungai & Air'],
            ['name' => 'Pencemaran Udara'],
            ['name' => 'Sampah & Limbah Liar'],
            ['name' => 'Kerusakan Lingkungan & Ruang Terbuka Hijau'],
            ['name' => 'Infrastruktur Jalan & Jembatan Rusak'],
            ['name' => 'Penerangan Jalan Umum'],
            ['name' => 'Fasilitas Publik Terbengkalai'],
            ['name' => 'Masalah Utilitas (Listrik, Air, Gas)'],
            ['name' => 'Fasilitas Umum & Ruang Publik'],
            ['name' => 'Laporan Lainnya'],
        ];

        Schema::disableForeignKeyConstraints();
        DB::table('categories')->truncate();
        Schema::enableForeignKeyConstraints();

        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
            ];
        }

        DB::table('categories')->insert($data);
    }
}
