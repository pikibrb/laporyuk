<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotlinePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_hotline_page_is_open_to_guests_without_login(): void
    {
        $this->get(route('darurat'))
            ->assertOk()
            ->assertSee('Nomor Penting Layanan Warga')
            ->assertSee('href="tel:112"', false)
            ->assertSee('href="tel:113"', false)
            ->assertSee('href="tel:123"', false);
    }

    public function test_dashboard_still_requires_authentication(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_hotline_page_clarifies_that_the_portal_is_not_an_emergency_channel(): void
    {
        $this->get(route('darurat'))
            ->assertOk()
            ->assertSee('Portal ini untuk laporan non-darurat')
            ->assertSee('bukan keadaan darurat')
            ->assertDontSee('Pusat Darurat');
    }

    public function test_legacy_sos_url_is_no_longer_available(): void
    {
        $this->get('/sos')->assertNotFound();
    }
}
