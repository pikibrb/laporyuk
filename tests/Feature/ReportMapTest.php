<?php

namespace Tests\Feature;

use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ReportMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_map_lists_reports_for_guests(): void
    {
        $report = Report::factory()->create(['title' => 'Lampu jalan mati di simpang utama']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Peta Fasilitas Publik')
            ->assertSee('Lampu jalan mati di simpang utama');

        Volt::test('report-map')
            ->assertSee('Lampu jalan mati di simpang utama')
            ->assertSee($report->category->name);
    }

    public function test_map_can_be_filtered_by_status(): void
    {
        Report::factory()->create(['title' => 'Laporan masih menunggu']);
        Report::factory()->resolved()->create(['title' => 'Laporan sudah selesai']);

        Volt::test('report-map')
            ->assertSee('Laporan masih menunggu')
            ->assertSee('Laporan sudah selesai')
            ->set('status', Report::STATUS_RESOLVED)
            ->assertSee('Laporan sudah selesai')
            ->assertDontSee('Laporan masih menunggu');
    }

    public function test_map_can_be_searched_by_title(): void
    {
        Report::factory()->create(['title' => 'Tumpukan sampah di bantaran sungai']);
        Report::factory()->create(['title' => 'Kabel listrik terkelupas']);

        Volt::test('report-map')
            ->set('search', 'sampah')
            ->assertSee('Tumpukan sampah di bantaran sungai')
            ->assertDontSee('Kabel listrik terkelupas');
    }

    public function test_resetting_filters_shows_all_reports_again(): void
    {
        Report::factory()->create(['title' => 'Laporan pertama']);
        Report::factory()->resolved()->create(['title' => 'Laporan kedua']);

        Volt::test('report-map')
            ->set('status', Report::STATUS_RESOLVED)
            ->call('resetFilters')
            ->assertSet('status', '')
            ->assertSee('Laporan pertama')
            ->assertSee('Laporan kedua');
    }
}
