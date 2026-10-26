<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ResidentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_statistics_only_count_their_own_reports(): void
    {
        $resident = User::factory()->create();

        Report::factory()->count(2)->create(['user_id' => $resident->id]);
        Report::factory()->resolved()->create(['user_id' => $resident->id]);

        Report::factory()->count(4)->create();

        $this->actingAs($resident);

        Volt::test('report-stats', ['scope' => 'mine'])
            ->assertSee('Laporan saya')
            ->assertSeeHtml('>3</p>')
            ->assertSeeHtml('>2</p>')
            ->assertSeeHtml('>1</p>')
            ->assertDontSeeHtml('>7</p>');
    }

    public function test_public_statistics_still_count_every_report(): void
    {
        $resident = User::factory()->create();

        Report::factory()->count(2)->create(['user_id' => $resident->id]);
        Report::factory()->count(4)->create();

        Volt::test('report-stats')
            ->assertSee('Laporan masuk')
            ->assertSeeHtml('>6</p>');
    }

    public function test_dashboard_lists_only_the_signed_in_residents_reports(): void
    {
        $resident = User::factory()->create();

        Report::factory()->create(['user_id' => $resident->id, 'title' => 'Laporan milik warga ini']);
        Report::factory()->create(['title' => 'Laporan milik warga lain']);

        $this->actingAs($resident)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Laporan milik warga ini')
            ->assertDontSee('Laporan milik warga lain');
    }

    public function test_dashboard_links_to_the_resident_feedback_inbox(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Masukan Saya')
            ->assertSee(route('masukan.saya'))
            ->assertSee('Lihat tanggapan masukan saya');
    }

    public function test_admin_dashboard_does_not_show_the_resident_feedback_inbox(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('masukan.saya'))
            ->assertDontSee('Lihat masukan saya');
    }
}
