<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        if (Report::query()->exists()) {
            return;
        }

        $citizen = User::query()->where('email', 'test@example.com')->first();
        $admin = User::query()->where('role', 'admin')->first();

        $reports = [
            [
                'title' => 'Lampu jalan mati di simpang Gatot Subroto',
                'description' => 'Lampu penerangan jalan mati sejak tiga malam terakhir. Jalur ini ramai dilewati pengendara motor sehingga rawan kecelakaan.',
                'category' => 'Penerangan Jalan Umum',
                'status' => Report::STATUS_PENDING,
                'latitude' => 3.5910,
                'longitude' => 98.6650,
                'hours' => 3,
                'anonymous' => true,
            ],
            [
                'title' => 'Jalan berlubang dekat Pasar Sukaramai',
                'description' => 'Lubang cukup dalam di badan jalan dan sudah mengenai beberapa kendaraan. Mohon segera ditambal sebelum menimbulkan korban.',
                'category' => 'Infrastruktur Jalan & Jembatan Rusak',
                'status' => Report::STATUS_PROCESSING,
                'latitude' => 3.5840,
                'longitude' => 98.6780,
                'hours' => 8,
                'anonymous' => false,
            ],
            [
                'title' => 'Tumpukan sampah di bantaran Sungai Deli',
                'description' => 'Sampah rumah tangga menumpuk dan mulai menyumbat aliran sungai. Bau menyengat saat hujan turun.',
                'category' => 'Sampah & Limbah Liar',
                'status' => Report::STATUS_PENDING,
                'latitude' => 3.5885,
                'longitude' => 98.6815,
                'hours' => 12,
                'anonymous' => true,
            ],
            [
                'title' => 'Asap pembakaran sampah menyelimuti permukiman',
                'description' => 'Pembakaran sampah liar setiap sore menghasilkan asap tebal yang mengganggu pernapasan warga sekitar.',
                'category' => 'Pencemaran Udara',
                'status' => Report::STATUS_PENDING,
                'latitude' => 3.6050,
                'longitude' => 98.6700,
                'hours' => 1,
                'anonymous' => true,
            ],
            [
                'title' => 'Kebocoran pipa air bersih di Jalan Sei Serayu',
                'description' => 'Pipa air bocor dan menggenangi trotoar. Debit air bersih ke rumah warga ikut menurun.',
                'category' => 'Masalah Utilitas (Listrik, Air, Gas)',
                'status' => Report::STATUS_RESOLVED,
                'latitude' => 3.5980,
                'longitude' => 98.6600,
                'hours' => 40,
                'anonymous' => false,
            ],
            [
                'title' => 'Kabel listrik terkelupas di tiang dekat sekolah',
                'description' => 'Kabel terkelupas dan tergantung rendah di atas trotoar yang biasa dilewati siswa pulang sekolah.',
                'category' => 'Masalah Utilitas (Listrik, Air, Gas)',
                'status' => Report::STATUS_PROCESSING,
                'latitude' => 3.6100,
                'longitude' => 98.6750,
                'hours' => 20,
                'anonymous' => true,
            ],
            [
                'title' => 'Taman kota dipenuhi coretan dan kursi rusak',
                'description' => 'Beberapa bangku taman patah dan area bermain anak tidak terawat. Perlu perbaikan fasilitas publik.',
                'category' => 'Fasilitas Umum & Ruang Publik',
                'status' => Report::STATUS_RESOLVED,
                'latitude' => 3.5930,
                'longitude' => 98.6850,
                'hours' => 72,
                'anonymous' => true,
            ],
            [
                'title' => 'Jembatan penyeberangan retak di sisi timur',
                'description' => 'Terlihat retakan memanjang pada lantai jembatan penyeberangan. Pengguna jembatan merasa tidak aman.',
                'category' => 'Infrastruktur Jalan & Jembatan Rusak',
                'status' => Report::STATUS_PENDING,
                'latitude' => 3.5870,
                'longitude' => 98.6690,
                'hours' => 5,
                'anonymous' => true,
            ],
            [
                'title' => 'Penebangan pohon di bantaran jalan tanpa izin',
                'description' => 'Beberapa pohon ditebang tanpa izin sehingga tepi jalan menjadi gundul. Warga khawatir muncul longsor saat hujan deras.',
                'category' => 'Kerusakan Lingkungan & Ruang Terbuka Hijau',
                'status' => Report::STATUS_REJECTED,
                'latitude' => 3.5955,
                'longitude' => 98.6760,
                'hours' => 30,
                'anonymous' => false,
            ],
        ];

        foreach ($reports as $data) {
            $category = Category::query()->where('name', $data['category'])->first();

            if (! $category) {
                continue;
            }

            $createdAt = now()->subHours($data['hours']);

            $report = Report::create([
                'user_id' => $data['anonymous'] ? null : $citizen?->id,
                'category_id' => $category->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'status' => $data['status'],
            ]);

            $report->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();
            $report->statusHistories()->update(['created_at' => $createdAt, 'updated_at' => $createdAt]);

            if ($data['status'] !== Report::STATUS_PENDING) {
                $handledAt = $createdAt->copy()->addHour();

                $report->statusHistories()->create([
                    'user_id' => $admin?->id,
                    'status' => $data['status'],
                    'notes' => match ($data['status']) {
                        Report::STATUS_PROCESSING => 'Laporan diverifikasi dan diteruskan ke petugas lapangan.',
                        Report::STATUS_RESOLVED => 'Perbaikan selesai dilakukan di lokasi.',
                        default => 'Laporan ditolak karena bukan kewenangan petugas.',
                    },
                ])->forceFill(['created_at' => $handledAt, 'updated_at' => $handledAt])->saveQuietly();
            }
        }
    }
}
