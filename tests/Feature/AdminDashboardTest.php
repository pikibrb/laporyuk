<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_hides_every_create_report_entry_point(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Buat Laporan')
            ->assertDontSee('Buat laporan')
            ->assertDontSee('<span>Lapor</span>', false)
            ->assertDontSee('href="'.route('lapor').'"', false);
    }

    public function test_admin_dashboard_links_to_the_feedback_inbox(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kotak masukan')
            ->assertSee(route('masukan.index'))
            ->assertSee('Masukan Warga');
    }

    public function test_admin_dashboard_summarises_feedback_awaiting_a_response(): void
    {
        Feedback::factory()->create(['message' => 'Tolong tampilkan jumlah laporan per kategori.']);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Masukan warga')
            ->assertSee('1 baru')
            ->assertSee('Tolong tampilkan jumlah laporan per kategori.');
    }

    public function test_citizen_dashboard_still_offers_report_creation(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Buat Laporan')
            ->assertSee('href="'.route('lapor').'"', false)
            ->assertDontSee('Kotak masukan');
    }

    public function test_admin_dashboard_hides_the_feedback_submission_menu(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Masukan &amp; Saran', false)
            ->assertDontSee('Kirim masukan &amp; saran', false)
            ->assertDontSee('href="'.route('feedback').'"', false)
            ->assertDontSee('<span>Masukan</span>', false);
    }

    public function test_citizen_dashboard_keeps_the_feedback_submission_menu(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Masukan &amp; Saran', false)
            ->assertSee('href="'.route('feedback').'"', false);
    }

    public function test_admin_map_does_not_offer_report_creation(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('Buat laporan baru');
    }
}
