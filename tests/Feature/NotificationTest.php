<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_toast_container_is_rendered_on_portal_pages(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('aria-live="polite"', false);
    }

    public function test_flashed_notification_is_rendered_as_an_initial_toast(): void
    {
        $this->withSession(['notify' => ['type' => 'success', 'message' => 'Perubahan berhasil disimpan.']])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Perubahan berhasil disimpan.', false);
    }
}
