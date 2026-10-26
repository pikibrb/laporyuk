<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageSwitchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_indonesian_is_the_default_language(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Peta Fasilitas Publik')
            ->assertSee('Buat Laporan')
            ->assertSee('Masukan');
    }

    public function test_visitors_can_switch_to_english(): void
    {
        $this->get(route('locale.switch', 'en'))
            ->assertRedirect()
            ->assertSessionHas('locale', 'en');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Public Facilities & Environmental Issues Map')
            ->assertSee('Create Report')
            ->assertSee('Feedback');
    }

    public function test_visitors_can_switch_back_to_indonesian(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get(route('locale.switch', 'id'))
            ->assertRedirect()
            ->assertSessionHas('locale', 'id');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Peta Fasilitas Publik')
            ->assertSee('Buat Laporan');
    }

    public function test_unsupported_locales_are_rejected(): void
    {
        $this->get(route('locale.switch', 'fr'))->assertNotFound();
        $this->assertNull(session('locale'));
    }

    public function test_the_selected_language_is_applied_to_authenticated_pages(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Officer Control Center')
            ->assertSee('Quick actions')
            ->assertSee('Citizen feedback');
    }

    public function test_the_switcher_is_rendered_in_the_navigation(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('locale.switch', 'id'))
            ->assertSee(route('locale.switch', 'en'))
            ->assertSee('Pilih bahasa');
    }

    public function test_the_language_switch_redirects_back_to_the_current_page(): void
    {
        $this->from(route('feedback'))
            ->get(route('locale.switch', 'en'))
            ->assertRedirect(route('feedback'));
    }
}
